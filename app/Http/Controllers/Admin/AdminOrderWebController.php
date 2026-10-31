<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\OrderStatusHistory;
use App\Models\Shipment;
use App\Models\StoreSettings;
use App\Services\Orders\OrderFulfillmentService;
use App\Services\Shipping\ShiprocketService;
use App\Support\Commerce\IndianStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AdminOrderWebController extends Controller
{
    public function __construct(
        protected OrderFulfillmentService $fulfillmentService,
        protected ShiprocketService $shiprocketService,
    ) {}

    /**
     * Orders listing with search and filters.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $paymentStatus = $request->query('payment_status');
        $search = trim((string) $request->query('search', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Order::query()
            ->with(['shippingAddress', 'payments', 'shipments', 'items'])
            ->latest('placed_at');

        if (!empty($status) && $status !== 'ALL') {
            $query->where('status', $status);
        }

        if (!empty($paymentStatus) && $paymentStatus !== 'ALL') {
            $query->where('payment_status', $paymentStatus);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if (!empty($dateFrom)) {
            $query->where('placed_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if (!empty($dateTo)) {
            $query->where('placed_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        $orders = $query->paginate(20)->withQueryString();

        $counts = [
            'ALL' => Order::count(),
            'CONFIRMED' => Order::where('status', OrderStatus::CONFIRMED)->count(),
            'PROCESSING' => Order::where('status', OrderStatus::PROCESSING)->count(),
            'SHIPPED' => Order::where('status', OrderStatus::SHIPPED)->count(),
            'DELIVERED' => Order::where('status', OrderStatus::DELIVERED)->count(),
            'CANCELLED' => Order::where('status', OrderStatus::CANCELLED)->count(),
            'PENDING_PAYMENT' => Order::where('status', OrderStatus::PENDING_PAYMENT)->count(),
        ];

        return view('admin.orders.index', [
            'orders' => $orders,
            'counts' => $counts,
            'currentStatus' => $status ?? 'ALL',
            'currentPaymentStatus' => $paymentStatus ?? 'ALL',
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /**
     * Show single order detail with fulfillment controls.
     */
    public function show(Order $order): View
    {
        $order->load([
            'items.variant.product',
            'shippingAddress',
            'billingAddress',
            'payments',
            'shipments.items',
            'notes.authorAdminUser',
            'statusHistory.actorAdminUser',
        ]);

        $activeShipment = $order->shipments()
            ->where('active_order_id', $order->id)
            ->latest('id')
            ->first();

        $availableCouriers = [];
        if (!$activeShipment && in_array($order->status, [OrderStatus::CONFIRMED, OrderStatus::PROCESSING], true)) {
            try {
                $destPin = $order->shippingAddress?->postal_code ?? '302001';
                $availableCouriers = $this->shiprocketService->checkServiceability(
                    pickupPincode: '302001',
                    deliveryPincode: $destPin,
                    weightGrams: max(100, $order->shipping_weight_grams ?? 500),
                    isCod: $order->payment_method->value === 'COD'
                );
            } catch (Throwable $e) {
                Log::warning('Could not fetch couriers for order preview', ['error' => $e->getMessage()]);
            }
        }

        return view('admin.orders.show', [
            'order' => $order,
            'activeShipment' => $activeShipment,
            'availableCouriers' => $availableCouriers,
        ]);
    }

    /**
     * Update order status manually.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', OrderStatus::values()),
            'note' => 'nullable|string|max:500',
        ]);

        $targetStatus = OrderStatus::from($validated['status']);
        $admin = auth('admin')->user();
        $adminId = $admin?->id;
        $note = $validated['note'] ?? "Status updated to {$targetStatus->value} by admin";

        try {
            if ($targetStatus === OrderStatus::CANCELLED) {
                $this->fulfillmentService->cancelOrder($order, $note, $adminId);
                return back()->with('success', "Order {$order->order_number} cancelled successfully and reservations released.");
            }

            if ($targetStatus === OrderStatus::DELIVERED) {
                $this->fulfillmentService->markDelivered($order, null, $adminId);
                return back()->with('success', "Order {$order->order_number} marked as DELIVERED.");
            }

            DB::transaction(function () use ($order, $targetStatus, $adminId, $note) {
                $prevStatus = $order->status;
                $order->update(['status' => $targetStatus]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => $prevStatus,
                    'to_status' => $targetStatus,
                    'actor_type' => \App\Enums\OrderActorType::ADMIN,
                    'actor_admin_user_id' => $adminId,
                    'note' => $note,
                    'created_at' => now(),
                ]);
            });

            return back()->with('success', "Order status successfully updated to {$targetStatus->value}.");
        } catch (Throwable $e) {
            Log::error('Order status transition failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "Failed to update status: {$e->getMessage()}");
        }
    }

    /**
     * Add staff note to order.
     */
    public function addNote(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $admin = auth('admin')->user();

        OrderNote::create([
            'order_id' => $order->id,
            'author_admin_user_id' => $admin?->id,
            'body' => trim($validated['body']),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Staff note added to order.');
    }

    /**
     * Book courier and generate AWB via Shiprocket.
     */
    public function createShipment(Request $request, Order $order): RedirectResponse
    {
        $admin = auth('admin')->user();
        $courierId = $request->input('courier_id') ? (int) $request->input('courier_id') : null;

        try {
            $shipment = $this->fulfillmentService->createShipmentForOrder(
                $order,
                $courierId,
                $admin?->id
            );

            return back()->with('success', "Courier booked! AWB: {$shipment->awb} ({$shipment->courier_name}).");
        } catch (Throwable $e) {
            Log::error('Shipment booking failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "Booking failed: {$e->getMessage()}");
        }
    }

    /**
     * Dispatch parcel and deduct inventory under locks.
     */
    public function dispatch(Order $order, Shipment $shipment): RedirectResponse
    {
        $admin = auth('admin')->user();

        try {
            $this->fulfillmentService->dispatchShipment($shipment, $admin?->id);

            return back()->with('success', "Order {$order->order_number} marked as SHIPPED. Inventory deducted.");
        } catch (Throwable $e) {
            Log::error('Dispatch shipment failed', [
                'shipment' => $shipment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "Dispatch failed: {$e->getMessage()}");
        }
    }

    /**
     * Fulfill order via local by-hand delivery.
     */
    public function createLocalDelivery(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_person' => 'required|string|max:100',
            'delivery_phone' => 'nullable|string|max:20',
            'note' => 'nullable|string|max:500',
            'delivery_action' => 'required|string|in:dispatch,deliver_now',
        ]);

        $admin = auth('admin')->user();
        $markDelivered = $validated['delivery_action'] === 'deliver_now';

        try {
            $shipment = $this->fulfillmentService->createLocalDeliveryShipment(
                order: $order,
                deliveryPerson: trim($validated['delivery_person']),
                deliveryPhone: !empty($validated['delivery_phone']) ? trim($validated['delivery_phone']) : null,
                note: !empty($validated['note']) ? trim($validated['note']) : null,
                adminUserId: $admin?->id,
                markDeliveredImmediately: $markDelivered
            );

            $msg = $markDelivered
                ? "Order {$order->order_number} marked as DELIVERED by hand ({$validated['delivery_person']})."
                : "Order {$order->order_number} marked as OUT FOR LOCAL DELIVERY by {$validated['delivery_person']}.";

            return back()->with('success', $msg);
        } catch (Throwable $e) {
            Log::error('Local delivery failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "Local delivery failed: {$e->getMessage()}");
        }
    }

    /**
     * Cancel order with reason.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:255',
        ]);

        $admin = auth('admin')->user();

        try {
            $this->fulfillmentService->cancelOrder($order, $validated['reason'], $admin?->id);

            return back()->with('success', "Order {$order->order_number} has been cancelled.");
        } catch (Throwable $e) {
            return back()->with('error', "Cancellation failed: {$e->getMessage()}");
        }
    }

    /**
     * Printable GST Tax Invoice.
     */
    public function invoice(Order $order): View
    {
        $order->load([
            'items.variant',
            'shippingAddress',
            'billingAddress',
            'payments',
        ]);

        // Auto-assign invoice number if absent
        if (empty($order->invoice_number)) {
            $invNum = 'INV-' . substr($order->order_number, 3);
            $order->update([
                'invoice_number' => $invNum,
                'invoice_issued_at' => now(),
            ]);
        }

        $store = StoreSettings::current();
        $placeOfSupplyState = IndianStates::getName($order->place_of_supply_state_code);
        $storeState = IndianStates::getName($store->state_code ?? '08');

        return view('admin.orders.invoice', [
            'order' => $order,
            'store' => $store,
            'placeOfSupplyState' => $placeOfSupplyState,
            'storeState' => $storeState,
        ]);
    }
}
