<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\InventoryReservationStatus;
use App\Enums\OrderActorType;
use App\Enums\OrderStatus;
use App\Enums\PaymentConfirmationSource;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentPaymentMode;
use App\Enums\ShipmentStatus;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\StoreSettings;
use App\Services\Email\EmailService;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderFulfillmentService
{
    public function __construct(
        protected ShiprocketService $shiprocketService,
        protected InventoryReservationService $reservationService,
        protected EmailService $emailService,
    ) {}

    /**
     * Create shipment and book courier for an order.
     */
    public function createShipmentForOrder(
        Order $order,
        ?int $courierId = null,
        ?int $adminUserId = null
    ): Shipment {
        return DB::transaction(function () use ($order, $courierId, $adminUserId) {
            if (!in_array($order->status, [OrderStatus::CONFIRMED, OrderStatus::PROCESSING], true)) {
                throw new InvalidArgumentException("Order {$order->order_number} must be in CONFIRMED or PROCESSING status to book shipment.");
            }

            // Check if active shipment already exists
            $existing = Shipment::where('order_id', $order->id)
                ->where('active_order_id', $order->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $store = StoreSettings::current();
            $locationId = InventoryLocation::where('is_active', true)->orderBy('id')->value('id') ?? 1;
            $shippingAddress = $order->shippingAddress ?? $order->addresses()->where('type', 'SHIPPING')->first();

            if (!$shippingAddress) {
                throw new InvalidArgumentException("Order {$order->order_number} has no valid shipping address.");
            }

            $isCod = $order->payment_method->value === 'COD';
            $paymentMode = $isCod ? ShipmentPaymentMode::COD : ShipmentPaymentMode::PREPAID;
            $codAmountPaise = $isCod ? $order->total_paise : null;

            // Dimensions and weight calculations
            $lengthCm = max(1, min(200, (int) ($store->package_length_cm ?? 25)));
            $breadthCm = max(1, min(200, (int) ($store->package_breadth_cm ?? 20)));
            $heightCm = max(1, min(200, (int) ($store->package_height_cm ?? 5)));
            $packagingWeight = (int) ($store->packaging_weight_grams ?? 200);

            $itemsWeight = 0;
            foreach ($order->items as $item) {
                $variantWeight = $item->variant?->weight_grams ?? 300;
                $itemsWeight += ($variantWeight * $item->quantity);
            }
            $totalWeightGrams = max(100, $itemsWeight + $packagingWeight);

            $shipmentRef = $order->order_number . '-S1';
            $pickupLocation = !empty($store->shiprocket_pickup_location) ? $store->shiprocket_pickup_location : 'Jaipur';

            $now = now();

            // 1. Create Shipment Record
            $shipment = Shipment::create([
                'order_id' => $order->id,
                'active_order_id' => $order->id,
                'provider' => 'shiprocket',
                'reference' => $shipmentRef,
                'status' => ShipmentStatus::PENDING,
                'payment_mode' => $paymentMode,
                'cod_amount_paise' => $codAmountPaise,
                'declared_value_paise' => $order->total_paise,
                'shipping_cost_paise' => $order->shipping_paise,
                'weight_grams' => $totalWeightGrams,
                'length_cm' => $lengthCm,
                'breadth_cm' => $breadthCm,
                'height_cm' => $heightCm,
                'location_id' => $locationId,
                'pickup_location' => $pickupLocation,
                'destination_postal_code' => $shippingAddress->postal_code,
                'created_by_admin_user_id' => $adminUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 2. Create Shipment Items
            foreach ($order->items as $item) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'order_item_id' => $item->id,
                    'variant_id' => $item->variant_id,
                    'sku' => $item->sku,
                    'product_name' => $item->product_name,
                    'variant_title' => $item->variant_title,
                    'quantity' => $item->quantity,
                    'unit_weight_grams' => $item->variant?->weight_grams ?? 300,
                ]);
            }

            // 3. Book on Shiprocket
            $srOrder = $this->shiprocketService->createOrder($order, $shipment);
            $shipment->update([
                'provider_order_id' => (string) ($srOrder['order_id'] ?? ''),
                'provider_shipment_id' => (string) ($srOrder['shipment_id'] ?? ''),
                'status' => ShipmentStatus::CREATED,
            ]);

            // 4. Assign AWB
            $providerShipmentId = $srOrder['shipment_id'] ?? $shipment->id;
            $awbData = $this->shiprocketService->assignAwb($providerShipmentId, $courierId);

            $shipment->update([
                'status' => ShipmentStatus::AWB_ASSIGNED,
                'awb' => $awbData['awb'],
                'courier_id' => (string) $awbData['courier_id'],
                'courier_name' => $awbData['courier_name'],
                'tracking_url' => $awbData['tracking_url'],
                'awb_requested_at' => $now,
            ]);

            // 5. Update Order status to PROCESSING if currently CONFIRMED
            if ($order->status === OrderStatus::CONFIRMED) {
                $order->update(['status' => OrderStatus::PROCESSING]);
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => OrderStatus::CONFIRMED,
                    'to_status' => OrderStatus::PROCESSING,
                    'actor_type' => $adminUserId ? OrderActorType::ADMIN : OrderActorType::SYSTEM,
                    'actor_admin_user_id' => $adminUserId,
                    'note' => "Courier booked with {$awbData['courier_name']} (AWB: {$awbData['awb']})",
                    'created_at' => $now,
                ]);
            }

            return $shipment;
        });
    }

    /**
     * Create local / by-hand delivery shipment and fulfill order.
     */
    public function createLocalDeliveryShipment(
        Order $order,
        string $deliveryPerson = 'Self Delivery',
        ?string $deliveryPhone = null,
        ?string $note = null,
        ?int $adminUserId = null,
        bool $markDeliveredImmediately = false
    ): Shipment {
        $shipment = DB::transaction(function () use ($order, $deliveryPerson, $deliveryPhone, $note, $adminUserId, $markDeliveredImmediately) {
            if (!in_array($order->status, [OrderStatus::CONFIRMED, OrderStatus::PROCESSING], true)) {
                throw new InvalidArgumentException("Order {$order->order_number} must be in CONFIRMED or PROCESSING status for local delivery.");
            }

            // Check if active shipment already exists
            $existing = Shipment::where('order_id', $order->id)
                ->where('active_order_id', $order->id)
                ->first();

            if ($existing) {
                throw new InvalidArgumentException("Order {$order->order_number} already has an active shipment ({$existing->courier_name} - {$existing->reference}).");
            }

            $store = StoreSettings::current();
            $locationId = InventoryLocation::where('is_active', true)->orderBy('id')->value('id') ?? 1;
            $shippingAddress = $order->shippingAddress ?? $order->addresses()->where('type', 'SHIPPING')->first();

            $destPostalCode = '302001';
            if ($shippingAddress && preg_match('/^[1-9][0-9]{5}$/', (string) $shippingAddress->postal_code)) {
                $destPostalCode = (string) $shippingAddress->postal_code;
            }

            $isCod = $order->payment_method->value === 'COD';
            $paymentMode = $isCod ? ShipmentPaymentMode::COD : ShipmentPaymentMode::PREPAID;
            $codAmountPaise = $isCod ? $order->total_paise : null;

            $now = now();
            $status = $markDeliveredImmediately ? ShipmentStatus::DELIVERED : ShipmentStatus::SHIPPED;
            $shippedAt = $now;
            $deliveredAt = $markDeliveredImmediately ? $now : null;

            // Generate unique reference & AWB compliant with DB checks (^[A-Z0-9-]{3,50}$)
            $refSuffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
            $shipmentRef = $order->order_number . '-HAND-' . $refSuffix;
            $awbCode = 'HAND-' . strtoupper(substr($order->order_number, -6)) . '-' . $refSuffix;
            $providerShipmentId = 'LOCAL-' . $order->order_number . '-' . $refSuffix;
            $courierTitle = 'By-Hand (' . mb_substr($deliveryPerson, 0, 45) . ')';

            // 1. Create Shipment Record
            $shipment = Shipment::create([
                'order_id' => $order->id,
                'active_order_id' => $order->id,
                'provider' => 'by_hand',
                'reference' => $shipmentRef,
                'status' => $status,
                'provider_shipment_id' => $providerShipmentId,
                'awb' => $awbCode,
                'courier_name' => $courierTitle,
                'payment_mode' => $paymentMode,
                'cod_amount_paise' => $codAmountPaise,
                'declared_value_paise' => $order->total_paise,
                'shipping_cost_paise' => 0,
                'weight_grams' => 250,
                'length_cm' => 15,
                'breadth_cm' => 10,
                'height_cm' => 5,
                'location_id' => $locationId,
                'pickup_location' => 'Store / By Hand',
                'destination_postal_code' => $destPostalCode,
                'shipped_at' => $shippedAt,
                'delivered_at' => $deliveredAt,
                'stock_deducted_at' => $now,
                'provider_status' => $markDeliveredImmediately ? 'DELIVERED_BY_HAND' : 'OUT_FOR_LOCAL_DELIVERY',
                'created_by_admin_user_id' => $adminUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 2. Create Shipment Items
            foreach ($order->items as $item) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'order_item_id' => $item->id,
                    'variant_id' => $item->variant_id,
                    'sku' => $item->sku,
                    'product_name' => $item->product_name,
                    'variant_title' => $item->variant_title,
                    'quantity' => $item->quantity,
                    'unit_weight_grams' => $item->variant?->weight_grams ?? 250,
                ]);
            }

            // 3. Deduct inventory under locks and record movements
            foreach ($order->items as $item) {
                $level = InventoryLevel::where('variant_id', $item->variant_id)
                    ->where('location_id', $locationId)
                    ->lockForUpdate()
                    ->first();

                if ($level) {
                    $qty = $item->quantity;
                    $onHandAfter = max(0, $level->on_hand - $qty);
                    $reservedAfter = max(0, $level->reserved - $qty);

                    $level->update([
                        'on_hand' => $onHandAfter,
                        'reserved' => $reservedAfter,
                    ]);

                    $reservation = InventoryReservation::where('variant_id', $item->variant_id)
                        ->where('source_type', 'ORDER')
                        ->where('source_id', (string) $order->id)
                        ->where('status', InventoryReservationStatus::ACTIVE)
                        ->first();

                    if ($reservation) {
                        $reservation->update([
                            'status' => InventoryReservationStatus::DEDUCTED,
                            'resolved_at' => $now,
                        ]);
                    }

                    InventoryMovement::create([
                        'variant_id' => $item->variant_id,
                        'location_id' => $locationId,
                        'type' => 'DEDUCTION',
                        'reason' => null,
                        'on_hand_delta' => -$qty,
                        'reserved_delta' => -$qty,
                        'on_hand_after' => $onHandAfter,
                        'reserved_after' => $reservedAfter,
                        'reservation_id' => $reservation?->id,
                        'reference' => $shipment->reference,
                        'note' => "By-Hand Delivery ({$deliveryPerson})" . ($note ? ": {$note}" : ''),
                        'actor_type' => $adminUserId ? 'ADMIN' : 'SYSTEM',
                        'actor_admin_user_id' => $adminUserId,
                        'created_at' => $now,
                    ]);
                }
            }

            // 4. Update Order Status
            $prevStatus = $order->status;
            $newOrderStatus = $markDeliveredImmediately ? OrderStatus::DELIVERED : OrderStatus::SHIPPED;

            $orderUpdates = ['status' => $newOrderStatus];

            if ($markDeliveredImmediately && $isCod && $order->payment_status !== PaymentStatus::PAID) {
                $orderUpdates['payment_status'] = PaymentStatus::PAID;

                Payment::where('order_id', $order->id)->update([
                    'status' => PaymentStatus::PAID,
                    'paid_at' => $now,
                    'confirmed_via' => PaymentConfirmationSource::STAFF,
                    'recorded_by_admin_user_id' => $adminUserId,
                ]);
            }

            $order->update($orderUpdates);

            // 5. Record Order Status History
            $historyNote = $markDeliveredImmediately
                ? "Order delivered directly by hand by {$deliveryPerson}." . ($note ? " Note: {$note}" : '')
                : "Order dispatched for local by-hand delivery with {$deliveryPerson}." . ($note ? " Note: {$note}" : '');

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $prevStatus,
                'to_status' => $newOrderStatus,
                'actor_type' => $adminUserId ? OrderActorType::ADMIN : OrderActorType::SYSTEM,
                'actor_admin_user_id' => $adminUserId,
                'note' => $historyNote,
                'created_at' => $now,
            ]);

            // 6. Record staff note if contact phone or remarks were provided
            if (!empty($deliveryPhone) || !empty($note)) {
                OrderNote::create([
                    'order_id' => $order->id,
                    'author_admin_user_id' => $adminUserId,
                    'body' => "Local By-Hand Delivery Details:\nDelivered By: {$deliveryPerson}" .
                              ($deliveryPhone ? "\nContact Phone: {$deliveryPhone}" : '') .
                              ($note ? "\nRemarks: {$note}" : ''),
                    'created_at' => $now,
                ]);
            }

            return $shipment;
        });

        // Send Shipment Dispatched / Delivered Email notification
        try {
            if ($markDeliveredImmediately) {
                $this->emailService->sendShipmentDelivered($shipment);
            } else {
                $this->emailService->sendShipmentDispatched($shipment);
            }
        } catch (\Throwable $e) {
            Log::warning("Could not send local delivery email for order #{$order->order_number}: {$e->getMessage()}");
        }

        return $shipment;
    }

    /**
     * Dispatch shipment (handover to courier) and deduct inventory under locks.
     */
    public function dispatchShipment(Shipment $shipment, ?int $adminUserId = null): void
    {
        DB::transaction(function () use ($shipment, $adminUserId) {
            $order = $shipment->order;
            $now = now();

            // Deduct inventory if not already deducted
            if (!$shipment->stock_deducted_at) {
                foreach ($shipment->items as $item) {
                    $level = InventoryLevel::where('variant_id', $item->variant_id)
                        ->where('location_id', $shipment->location_id)
                        ->lockForUpdate()
                        ->first();

                    if ($level) {
                        $qty = $item->quantity;
                        $onHandAfter = max(0, $level->on_hand - $qty);
                        $reservedAfter = max(0, $level->reserved - $qty);

                        $level->update([
                            'on_hand' => $onHandAfter,
                            'reserved' => $reservedAfter,
                        ]);

                        // Update reservation to DEDUCTED
                        $reservation = InventoryReservation::where('variant_id', $item->variant_id)
                            ->where('source_type', 'ORDER')
                            ->where('source_id', (string) $order->id)
                            ->where('status', InventoryReservationStatus::ACTIVE)
                            ->first();

                        if ($reservation) {
                            $reservation->update([
                                'status' => InventoryReservationStatus::DEDUCTED,
                                'resolved_at' => $now,
                            ]);
                        }

                        // Record Inventory Movement
                        InventoryMovement::create([
                            'variant_id' => $item->variant_id,
                            'location_id' => $shipment->location_id,
                            'type' => 'DEDUCTION',
                            'reason' => null,
                            'on_hand_delta' => -$qty,
                            'reserved_delta' => -$qty,
                            'on_hand_after' => $onHandAfter,
                            'reserved_after' => $reservedAfter,
                            'reservation_id' => $reservation?->id,
                            'reference' => $shipment->reference,
                            'note' => "Dispatched in shipment {$shipment->reference} (AWB: {$shipment->awb})",
                            'actor_type' => $adminUserId ? 'ADMIN' : 'SYSTEM',
                            'actor_admin_user_id' => $adminUserId,
                            'created_at' => $now,
                        ]);
                    }
                }

            }

            // Update shipment status and deduction timestamp atomically
            $shipment->update([
                'status' => ShipmentStatus::SHIPPED,
                'shipped_at' => $now,
                'stock_deducted_at' => $shipment->stock_deducted_at ?? $now,
            ]);


            // Update order status
            $prevStatus = $order->status;
            $order->update([
                'status' => OrderStatus::SHIPPED,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $prevStatus,
                'to_status' => OrderStatus::SHIPPED,
                'actor_type' => $adminUserId ? OrderActorType::ADMIN : OrderActorType::SYSTEM,
                'actor_admin_user_id' => $adminUserId,
                'note' => "Parcel dispatched via {$shipment->courier_name} (AWB: {$shipment->awb})",
                'created_at' => $now,
            ]);
        });

        // Send Shipment Dispatched Email
        try {
            $this->emailService->sendShipmentDispatched($shipment);
        } catch (\Throwable $e) {
            Log::warning("Could not send shipment dispatched email for shipment #{$shipment->id}: {$e->getMessage()}");
        }
    }

    /**
     * Mark order as DELIVERED.
     */
    public function markDelivered(Order $order, ?Shipment $shipment = null, ?int $adminUserId = null): void
    {
        DB::transaction(function () use ($order, $shipment, $adminUserId) {
            $now = now();
            $prevStatus = $order->status;

            $order->update([
                'status' => OrderStatus::DELIVERED,
            ]);

            // If COD and unpaid, mark payment PAID upon delivery
            if ($order->payment_method->value === 'COD' && $order->payment_status !== PaymentStatus::PAID) {
                $order->update([
                    'payment_status' => PaymentStatus::PAID,
                ]);

                Payment::where('order_id', $order->id)->update([
                    'status' => PaymentStatus::PAID,
                    'paid_at' => $now,
                    'confirmed_via' => PaymentConfirmationSource::RECONCILIATION,
                ]);
            }

            if (!$shipment) {
                $shipment = Shipment::where('order_id', $order->id)
                    ->where('active_order_id', $order->id)
                    ->first();
            }

            if ($shipment) {
                $shipment->update([
                    'status' => ShipmentStatus::DELIVERED,
                    'delivered_at' => $now,
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $prevStatus,
                'to_status' => OrderStatus::DELIVERED,
                'actor_type' => $adminUserId ? OrderActorType::ADMIN : OrderActorType::SYSTEM,
                'actor_admin_user_id' => $adminUserId,
                'note' => 'Order successfully delivered to customer.',
                'created_at' => $now,
            ]);
        });

        // Send Shipment Delivered Email
        try {
            if ($shipment) {
                $this->emailService->sendShipmentDelivered($shipment);
            }
        } catch (\Throwable $e) {
            Log::warning("Could not send shipment delivered email for order #{$order->order_number}: {$e->getMessage()}");
        }
    }

    /**
     * Cancel an order safely, releasing active inventory reservations.
     */
    public function cancelOrder(Order $order, string $reason, ?int $adminUserId = null): void
    {
        $refundDue = false;

        DB::transaction(function () use ($order, $reason, $adminUserId, &$refundDue) {
            if (in_array($order->status, [OrderStatus::DELIVERED, OrderStatus::CANCELLED], true)) {
                throw new InvalidArgumentException("Order {$order->order_number} cannot be cancelled from its current state.");
            }

            $refundDue = ($order->payment_status === PaymentStatus::PAID);
            $now = now();
            $prevStatus = $order->status;

            // 1. Release inventory reservations
            $this->reservationService->releaseOrderReservations($order);

            // 2. Cancel active shipment if present and not yet shipped
            $activeShipment = Shipment::where('order_id', $order->id)
                ->where('active_order_id', $order->id)
                ->first();

            if ($activeShipment && in_array($activeShipment->status, [ShipmentStatus::PENDING, ShipmentStatus::CREATED, ShipmentStatus::AWB_ASSIGNED], true)) {
                $activeShipment->update([
                    'status' => ShipmentStatus::CANCELLED,
                    'active_order_id' => null,
                    'cancelled_at' => $now,
                ]);
            }

            // 3. Update order
            $order->update([
                'status' => OrderStatus::CANCELLED,
                'cancelled_at' => $now,
            ]);

            // 4. Record History
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $prevStatus,
                'to_status' => OrderStatus::CANCELLED,
                'actor_type' => $adminUserId ? OrderActorType::ADMIN : OrderActorType::CUSTOMER,
                'actor_admin_user_id' => $adminUserId,
                'note' => "Order cancelled: {$reason}",
                'created_at' => $now,
            ]);
        });

        // Send Order Cancelled Email
        try {
            $this->emailService->sendOrderCancelled($order, $refundDue, $reason);
        } catch (\Throwable $e) {
            Log::warning("Could not send order cancelled email for order #{$order->order_number}: {$e->getMessage()}");
        }
    }

    /**
     * Handle incoming Shiprocket tracking webhooks.
     *
     * @param array<string, mixed> $payload
     * @return array{status: string, action?: string, reason?: string}
     */
    public function handleShiprocketWebhook(array $payload): array
    {
        $awb = $payload['awb'] ?? $payload['awb_code'] ?? null;
        $shipment = null;

        if ($awb) {
            $shipment = Shipment::where('awb', $awb)->first();
        }
        if (!$shipment && !empty($payload['sr_order_id'])) {
            $shipment = Shipment::where('provider_order_id', (string) $payload['sr_order_id'])->first();
        }
        if (!$shipment && !empty($payload['shipment_id'])) {
            $shipment = Shipment::where('provider_shipment_id', (string) $payload['shipment_id'])->first();
        }

        if (!$shipment) {
            return ['status' => 'ignored', 'reason' => 'Shipment not found for AWB or provider ID'];
        }

        $rawStatus = strtoupper(trim((string) ($payload['current_status'] ?? $payload['shipment_status'] ?? $payload['status'] ?? '')));
        $order = $shipment->order;
        $now = now();

        $shipment->update([
            'provider_status' => $rawStatus ?: $shipment->provider_status,
            'last_synced_at' => $now,
        ]);

        if (str_contains($rawStatus, 'DELIVERED') && !str_contains($rawStatus, 'RTO')) {
            $this->markDelivered($order, $shipment, null);
            return ['status' => 'success', 'action' => 'marked_delivered'];
        }

        if (str_contains($rawStatus, 'OUT FOR DELIVERY') || str_contains($rawStatus, 'OUT_FOR_DELIVERY')) {
            $shipment->update(['status' => ShipmentStatus::OUT_FOR_DELIVERY]);
            return ['status' => 'success', 'action' => 'out_for_delivery'];
        }

        if (str_contains($rawStatus, 'IN TRANSIT') || str_contains($rawStatus, 'PICKED UP') || str_contains($rawStatus, 'SHIPPED')) {
            if ($shipment->status === ShipmentStatus::AWB_ASSIGNED || $shipment->status === ShipmentStatus::CREATED) {
                $this->dispatchShipment($shipment, null);
            } else {
                $shipment->update(['status' => ShipmentStatus::IN_TRANSIT]);
            }
            return ['status' => 'success', 'action' => 'in_transit'];
        }

        return ['status' => 'success', 'action' => 'status_updated'];
    }
}

