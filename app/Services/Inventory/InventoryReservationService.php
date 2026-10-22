<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\InventoryReservationStatus;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryReservationService
{
    /**
     * Reserve stock for order lines within a database transaction.
     *
     * @param array<int, array{variant_id: int, quantity: int}> $lines
     */
    public function reserveForOrder(Order $order, array $lines, ?int $locationId = null): void
    {
        if (!$locationId) {
            $locationId = InventoryLocation::where('is_active', true)->orderBy('id')->value('id') ?? 1;
        }

        foreach ($lines as $line) {
            $variantId = (int) $line['variant_id'];
            $qty = (int) $line['quantity'];

            if ($qty <= 0) {
                continue;
            }

            // Lock inventory level row
            $level = InventoryLevel::where('variant_id', $variantId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            if (!$level) {
                $variant = ProductVariant::find($variantId);
                throw new InvalidArgumentException("No inventory level found for item SKU '{$variant?->sku}'.");
            }

            $available = $level->on_hand - $level->reserved;
            if ($available < $qty) {
                $variant = ProductVariant::find($variantId);
                throw new InvalidArgumentException("Insufficient stock for item '{$variant?->sku}'. Available: {$available}, requested: {$qty}.");
            }

            // Create active reservation
            InventoryReservation::create([
                'variant_id' => $variantId,
                'location_id' => $locationId,
                'quantity' => $qty,
                'status' => InventoryReservationStatus::ACTIVE,
                'source_type' => 'ORDER',
                'source_id' => (string) $order->id,
            ]);

            // Increase reserved counter
            $level->increment('reserved', $qty);
        }
    }

    /**
     * Release active reservations for an order (e.g. cancelled or unpaid expired order).
     */
    public function releaseOrderReservations(Order $order): void
    {
        $reservations = InventoryReservation::where('source_type', 'ORDER')
            ->where('source_id', (string) $order->id)
            ->where('status', InventoryReservationStatus::ACTIVE)
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            $level = InventoryLevel::where('variant_id', $reservation->variant_id)
                ->where('location_id', $reservation->location_id)
                ->lockForUpdate()
                ->first();

            if ($level) {
                $level->decrement('reserved', min($reservation->quantity, $level->reserved));
            }

            $reservation->update([
                'status' => InventoryReservationStatus::RELEASED,
                'resolved_at' => now(),
            ]);
        }
    }
}
