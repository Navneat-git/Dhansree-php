<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Enums\InventoryReservationStatus;
use App\Enums\OrderActorType;
use App\Enums\OrderStatus;
use App\Enums\PaymentConfirmationSource;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentEventSource;
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
use App\Models\ShipmentTrackingEvent;
use App\Models\StoreSettings;
use App\Services\Email\EmailService;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Shipping\DelhiveryService;
use App\Services\Shipping\ShiprocketService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderFulfillmentService
{
    public function __construct(
        protected ShiprocketService $shiprocketService,
        protected DelhiveryService $delhiveryService,
        protected InventoryReservationService $reservationService,
        protected EmailService $emailService,
    ) {}

    /**
     * Create shipment and book courier for an order (Delhivery or Shiprocket).
     */
    public function createShipmentForOrder(
        Order $order,
        int|string|null $courierId = null,
        ?int $adminUserId = null,
        ?string $provider = null
    ): Shipment {
        return DB::transaction(function () use ($order, $courierId, $adminUserId, $provider) {
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
            $now = now();

            // Determine Provider (Delhivery or Shiprocket)
            if ($provider === 'delhivery') {
                $isDelhivery = true;
            } elseif ($provider === 'shiprocket') {
                $isDelhivery = false;
            } elseif (is_string($courierId) && str_starts_with($courierId, 'delhivery')) {
                $isDelhivery = true;
            } elseif (is_numeric($courierId)) {
                // Numeric courier IDs are Shiprocket company IDs
                $isDelhivery = false;
            } else {
                // Default: prefer Delhivery if enabled, else Shiprocket
                $isDelhivery = $this->delhiveryService->isEnabled();
            }

            if ($isDelhivery) {
                $shippingMode = ($courierId === 'delhivery_express') ? 'Express' : 'Surface';
                $pickupLocation = $this->delhiveryService->getPickupLocation();

                // 1. Create Shipment Record
                $shipment = Shipment::create([
                    'order_id' => $order->id,
                    'active_order_id' => $order->id,
                    'provider' => 'delhivery',
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

                // 3. Book on Delhivery API
                $delhiveryData = $this->delhiveryService->createShipment($order, $shipment, [
                    'shipping_mode' => $shippingMode,
                ]);

                $shipment->update([
                    'status' => ShipmentStatus::AWB_ASSIGNED,
                    'awb' => $delhiveryData['awb'],
                    'provider_order_id' => (string) ($delhiveryData['order_id'] ?? $shipmentRef),
                    'provider_shipment_id' => (string) $delhiveryData['awb'],
                    'courier_name' => $delhiveryData['courier_name'],
                    'courier_id' => is_string($courierId) ? $courierId : 'delhivery_' . strtolower($shippingMode),
                    'tracking_url' => $delhiveryData['tracking_url'],
                    'awb_requested_at' => $now,
                ]);

                // 4. Automatically schedule Delhivery courier pickup
                $pickupRes = $this->delhiveryService->schedulePickup(
                    pickupDate: null,
                    pickupTime: null,
                    packageCount: 1,
                    pickupLocationName: $pickupLocation
                );

                $pickupNote = '';
                if ($pickupRes['success']) {
                    $shipment->update([
                        'pickup_scheduled_at' => $now,
                        'provider_status' => "Pickup scheduled (ID: {$pickupRes['pickup_id']}) for {$pickupRes['pickup_date']}",
                    ]);
                    $pickupNote = " | Delhivery pickup scheduled for {$pickupRes['pickup_date']} at {$pickupRes['pickup_time']} (PR ID: {$pickupRes['pickup_id']})";
                }

                // 5. Update Order status to PROCESSING if currently CONFIRMED
                if ($order->status === OrderStatus::CONFIRMED) {
                    $order->update(['status' => OrderStatus::PROCESSING]);
                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'from_status' => OrderStatus::CONFIRMED,
                        'to_status' => OrderStatus::PROCESSING,
                        'actor_type' => $adminUserId ? OrderActorType::ADMIN : OrderActorType::SYSTEM,
                        'actor_admin_user_id' => $adminUserId,
                        'note' => "Courier booked with {$delhiveryData['courier_name']} (AWB: {$delhiveryData['awb']}){$pickupNote}",
                        'created_at' => $now,
                    ]);
                }

                return $shipment;
            }

            $pickupLocation = !empty($store->shiprocket_pickup_location) ? $store->shiprocket_pickup_location : 'Jaipur';

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
            $srCourierId = is_numeric($courierId) ? (int) $courierId : null;
            $awbData = $this->shiprocketService->assignAwb($providerShipmentId, $srCourierId);

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
     * Explicitly schedule or reschedule Delhivery pickup for a shipment.
     */
    public function scheduleDelhiveryPickup(
        Shipment $shipment,
        ?string $pickupDate = null,
        ?string $pickupTime = null,
        ?int $adminUserId = null
    ): array {
        $location = $shipment->pickup_location ?: $this->delhiveryService->getPickupLocation();
        $result = $this->delhiveryService->schedulePickup(
            $pickupDate,
            $pickupTime,
            1,
            $location
        );

        if ($result['success']) {
            $now = now();
            $shipment->update([
                'pickup_scheduled_at' => $now,
                'provider_status' => "Pickup scheduled (ID: {$result['pickup_id']}) for {$result['pickup_date']}",
            ]);

            OrderStatusHistory::create([
                'order_id' => $shipment->order_id,
                'from_status' => $shipment->order->status,
                'to_status' => $shipment->order->status,
                'actor_type' => $adminUserId ? OrderActorType::ADMIN : OrderActorType::SYSTEM,
                'actor_admin_user_id' => $adminUserId,
                'note' => "Delhivery pickup scheduled for {$result['pickup_date']} at {$result['pickup_time']} (PR ID: {$result['pickup_id']})",
                'created_at' => $now,
            ]);
        }

        return $result;
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

    /**
     * Synchronize live courier tracking status with provider (Delhivery or Shiprocket)
     * and update Shipment and Order status accordingly.
     *
     * @return array{success: bool, status: string, action?: string, message?: string}
     */
    public function syncShipmentTracking(Shipment $shipment): array
    {
        if (empty($shipment->awb)) {
            return [
                'success' => false,
                'status' => $shipment->status->value,
                'message' => 'Shipment does not have an AWB assigned yet.',
            ];
        }

        if ($shipment->provider === 'delhivery') {
            return $this->syncDelhiveryTracking($shipment);
        }

        if ($shipment->provider === 'shiprocket') {
            return [
                'success' => true,
                'status' => $shipment->status->value,
                'message' => 'Shiprocket tracking is synchronized via automated webhooks.',
            ];
        }

        return [
            'success' => false,
            'status' => $shipment->status->value,
            'message' => "Automatic tracking sync not supported for provider '{$shipment->provider}'.",
        ];
    }

    /**
     * Fetch live Delhivery tracking, record scans, and update shipment & order statuses.
     *
     * @return array{success: bool, status: string, action?: string, message?: string}
     */
    public function syncDelhiveryTracking(Shipment $shipment): array
    {
        $awb = trim((string) $shipment->awb);
        $res = $this->delhiveryService->trackPackage($awb);

        if (empty($res) || !isset($res['ShipmentData']) || empty($res['ShipmentData'])) {
            $error = $res['error'] ?? 'No tracking data returned by Delhivery';
            Log::warning("Delhivery tracking sync: No data for AWB {$awb}", ['response' => $res]);
            return [
                'success' => false,
                'status' => $shipment->status->value,
                'message' => is_string($error) ? $error : 'No tracking data found for AWB.',
            ];
        }

        $shipmentInfo = $res['ShipmentData'][0]['Shipment'] ?? null;
        if (!$shipmentInfo) {
            return [
                'success' => false,
                'status' => $shipment->status->value,
                'message' => 'Malformed tracking response from Delhivery.',
            ];
        }

        $statusObj = $shipmentInfo['Status'] ?? [];
        $rawStatus = strtoupper(trim((string) ($statusObj['Status'] ?? '')));
        $statusType = strtoupper(trim((string) ($statusObj['StatusType'] ?? '')));
        $instructions = trim((string) ($statusObj['Instructions'] ?? ''));
        $statusDateTimeStr = $statusObj['StatusDateTime'] ?? null;
        $statusLocation = trim((string) ($statusObj['StatusLocation'] ?? ''));
        $statusCode = trim((string) ($statusObj['StatusCode'] ?? ''));
        $scans = $shipmentInfo['Scans'] ?? [];

        $order = $shipment->order;
        $now = now();
        $occurredAt = $statusDateTimeStr ? Carbon::parse($statusDateTimeStr) : $now;

        $displayStatus = $statusObj['Status'] ?? 'In Transit';
        if ($instructions && !str_contains($displayStatus, $instructions)) {
            $displayStatus .= " ({$instructions})";
        }

        $shipment->update([
            'provider_status' => $displayStatus,
            'last_synced_at' => $now,
        ]);

        // Process Scan History into shipment_tracking_events
        foreach ($scans as $scan) {
            $detail = $scan['ScanDetail'] ?? [];
            if (empty($detail)) {
                continue;
            }
            $sTimeStr = $detail['ScanDateTime'] ?? $detail['StatusDateTime'] ?? null;
            $sTime = $sTimeStr ? Carbon::parse($sTimeStr) : $now;
            $sInstruction = trim((string) ($detail['Instructions'] ?? ''));
            $sLocation = trim((string) ($detail['ScannedLocation'] ?? ''));
            $sCode = trim((string) ($detail['StatusCode'] ?? ''));
            $sScan = trim((string) ($detail['Scan'] ?? ''));

            $hash = hash('sha256', $shipment->id . '_' . $sTime->toIso8601String() . '_' . $sCode . '_' . $sInstruction);

            ShipmentTrackingEvent::firstOrCreate(
                [
                    'shipment_id' => $shipment->id,
                    'dedupe_hash' => $hash,
                ],
                [
                    'source' => ShipmentEventSource::SYNC,
                    'occurred_at' => $sTime,
                    'provider_status_code' => $sCode ?: null,
                    'provider_status' => $sScan ?: $sInstruction,
                    'mapped_status' => $this->mapDelhiveryStatusToShipmentStatus($sScan, $sInstruction, $detail['ScanType'] ?? null),
                    'activity' => $sInstruction ?: $sScan,
                    'location' => $sLocation ?: null,
                    'created_at' => $now,
                ]
            );
        }

        // 1. DELIVERED Check
        // Delhivery uses StatusType='DL', Status='Delivered', or Instructions='Delivered to consignee'
        $isDelivered = ($statusType === 'DL')
            || (str_contains($rawStatus, 'DELIVERED') && !str_contains($rawStatus, 'RTO'))
            || (str_contains(strtoupper($instructions), 'DELIVERED') && !str_contains(strtoupper($instructions), 'RTO'));

        if ($isDelivered) {
            $shipment->update([
                'status' => ShipmentStatus::DELIVERED,
                'delivered_at' => $occurredAt,
            ]);

            // Transition Order to DELIVERED
            if ($order && $order->status !== OrderStatus::DELIVERED) {
                $this->markDelivered($order, $shipment, null);
            }

            return [
                'success' => true,
                'status' => 'DELIVERED',
                'action' => 'marked_delivered',
                'message' => "Shipment marked as DELIVERED (Delivered on {$occurredAt->timezone('Asia/Kolkata')->format('d M Y, h:i A')}). Order status updated to DELIVERED.",
            ];
        }

        // 2. OUT FOR DELIVERY Check
        if (str_contains(strtoupper($instructions), 'OUT FOR DELIVERY') || str_contains($rawStatus, 'OUT FOR DELIVERY') || str_contains($rawStatus, 'OUT_FOR_DELIVERY') || str_starts_with($statusCode, 'X-DDD')) {
            $shipment->update([
                'status' => ShipmentStatus::OUT_FOR_DELIVERY,
            ]);

            return [
                'success' => true,
                'status' => 'OUT_FOR_DELIVERY',
                'action' => 'out_for_delivery',
                'message' => "Shipment is OUT FOR DELIVERY ({$displayStatus}).",
            ];
        }

        // 3. RTO / Return Check
        if ($statusType === 'RT' || str_contains($rawStatus, 'RTO') || str_contains(strtoupper($instructions), 'RTO') || str_contains(strtoupper($instructions), 'RETURN')) {
            $targetStatus = str_contains($rawStatus, 'DELIVERED') ? ShipmentStatus::RTO_DELIVERED : ShipmentStatus::RTO_IN_TRANSIT;
            $shipment->update([
                'status' => $targetStatus,
            ]);

            return [
                'success' => true,
                'status' => $targetStatus->value,
                'action' => 'rto',
                'message' => "Shipment in Return to Origin ({$displayStatus}).",
            ];
        }

        // 4. IN TRANSIT / DISPATCHED Check
        if (str_contains($rawStatus, 'IN TRANSIT') || str_contains($rawStatus, 'DISPATCHED') || str_contains($rawStatus, 'PENDING') || str_contains($rawStatus, 'PICKED UP') || $statusType === 'UD') {
            // If shipment was still in PENDING/CREATED/AWB_ASSIGNED, advance it to SHIPPED
            if (in_array($shipment->status, [ShipmentStatus::PENDING, ShipmentStatus::CREATED, ShipmentStatus::AWB_ASSIGNED, ShipmentStatus::PICKUP_SCHEDULED], true)) {
                $this->dispatchShipment($shipment, null);
            }

            $shipment->update([
                'status' => ShipmentStatus::IN_TRANSIT,
            ]);

            return [
                'success' => true,
                'status' => 'IN_TRANSIT',
                'action' => 'in_transit',
                'message' => "Shipment is IN TRANSIT ({$displayStatus}).",
            ];
        }

        return [
            'success' => true,
            'status' => $shipment->status->value,
            'action' => 'status_updated',
            'message' => "Shipment status refreshed: {$displayStatus}.",
        ];
    }

    /**
     * Map Delhivery scan/status text to ShipmentStatus enum.
     */
    protected function mapDelhiveryStatusToShipmentStatus(?string $scan, ?string $instruction, ?string $scanType): ShipmentStatus
    {
        $raw = strtoupper(trim(($scan ?? '') . ' ' . ($instruction ?? '')));
        $type = strtoupper(trim($scanType ?? ''));

        if ($type === 'DL' || (str_contains($raw, 'DELIVERED') && !str_contains($raw, 'RTO'))) {
            return ShipmentStatus::DELIVERED;
        }
        if (str_contains($raw, 'OUT FOR DELIVERY')) {
            return ShipmentStatus::OUT_FOR_DELIVERY;
        }
        if ($type === 'RT' || str_contains($raw, 'RTO') || str_contains($raw, 'RETURN')) {
            return str_contains($raw, 'DELIVERED') ? ShipmentStatus::RTO_DELIVERED : ShipmentStatus::RTO_IN_TRANSIT;
        }
        if (str_contains($raw, 'IN TRANSIT') || str_contains($raw, 'DISPATCHED') || str_contains($raw, 'ARRIVED') || str_contains($raw, 'RECEIVED') || $type === 'UD') {
            return ShipmentStatus::IN_TRANSIT;
        }

        return ShipmentStatus::SHIPPED;
    }

    /**
     * Handle incoming Delhivery tracking webhooks.
     *
     * @param array<string, mixed> $payload
     * @return array{status: string, action?: string, reason?: string, message?: string}
     */
    public function handleDelhiveryWebhook(array $payload): array
    {
        $awb = $payload['waybill'] ?? $payload['awb'] ?? $payload['wbn'] ?? null;
        if (!$awb && isset($payload['Shipment']['AWB'])) {
            $awb = $payload['Shipment']['AWB'];
        }
        if (!$awb && isset($payload['ShipmentData'][0]['Shipment']['AWB'])) {
            $awb = $payload['ShipmentData'][0]['Shipment']['AWB'];
        }

        if (!$awb) {
            return ['status' => 'ignored', 'reason' => 'No AWB found in webhook payload'];
        }

        $shipment = Shipment::where('awb', trim((string) $awb))->first();
        if (!$shipment) {
            return ['status' => 'ignored', 'reason' => "Shipment not found for AWB {$awb}"];
        }

        $syncResult = $this->syncDelhiveryTracking($shipment);

        return [
            'status' => $syncResult['success'] ? 'success' : 'failed',
            'action' => $syncResult['action'] ?? 'status_synced',
            'message' => $syncResult['message'] ?? '',
        ];
    }
}

