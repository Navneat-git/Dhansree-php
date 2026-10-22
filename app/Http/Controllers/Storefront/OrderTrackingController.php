<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Commerce\IndianStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    /**
     * Show order tracking lookup page.
     */
    public function index(Request $request): View
    {
        return view('store.track-order', [
            'prefilledOrder' => $request->query('order', ''),
            'prefilledPhone' => $request->query('phone', ''),
        ]);
    }

    /**
     * Look up order by Order Number and Customer Phone.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:20',
            'phone' => 'required|string|min:10|max:16',
        ]);

        $orderNumber = strtoupper(trim($validated['order_number']));
        $digitsOnly = preg_replace('/\D/', '', $validated['phone']);
        $last10 = substr((string) $digitsOnly, -10);

        if (strlen($last10) !== 10) {
            return back()
                ->withInput()
                ->withErrors(['phone' => 'Please enter a valid 10-digit mobile number.']);
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return back()
                ->withInput()
                ->withErrors(['order_number' => 'No order found with this Order ID. Please check and try again.']);
        }

        $orderPhoneDigits = preg_replace('/\D/', '', $order->customer_phone);
        $orderLast10 = substr((string) $orderPhoneDigits, -10);

        if ($orderLast10 !== $last10) {
            return back()
                ->withInput()
                ->withErrors(['phone' => 'Mobile number does not match the order records.']);
        }

        // Authorize session to view this tracking detail
        session()->put('tracked_order_' . $order->order_number, true);

        return redirect()->route('store.track-order.show', ['orderNumber' => $order->order_number]);
    }

    /**
     * Display tracking details and timeline for the order.
     */
    public function show(string $orderNumber, Request $request): View|RedirectResponse
    {
        $orderNumber = strtoupper(trim($orderNumber));
        $order = Order::where('order_number', $orderNumber)
            ->with([
                'items.variant.product',
                'shippingAddress',
                'shipments',
                'statusHistory',
            ])
            ->first();

        if (!$order) {
            return redirect()->route('store.track-order.index')
                ->withErrors(['order_number' => 'Order not found.']);
        }

        // Check if session authorized or phone provided in query
        $isAuthorized = session()->get('tracked_order_' . $order->order_number, false);

        if (!$isAuthorized && $request->has('phone')) {
            $digitsOnly = preg_replace('/\D/', '', (string) $request->query('phone'));
            $last10 = substr((string) $digitsOnly, -10);
            $orderPhoneDigits = preg_replace('/\D/', '', $order->customer_phone);

            if (substr((string) $orderPhoneDigits, -10) === $last10) {
                $isAuthorized = true;
                session()->put('tracked_order_' . $order->order_number, true);
            }
        }

        // In development or if from confirmation redirect, allow viewing with order number
        if (!$isAuthorized && !app()->isLocal() && !session()->has('order_placed_' . $order->order_number)) {
            return redirect()->route('store.track-order.index', ['order' => $orderNumber])
                ->with('info', 'Please verify your phone number to view live tracking details.');
        }

        $activeShipment = $order->shipments()
            ->where('active_order_id', $order->id)
            ->latest('id')
            ->first();

        // Calculate progress steps
        $steps = [
            [
                'title' => 'Order Placed',
                'description' => 'Received & logged',
                'completed' => true,
                'current' => $order->status === OrderStatus::PENDING_PAYMENT,
                'date' => $order->placed_at,
            ],
            [
                'title' => 'Confirmed',
                'description' => 'Payment verified & order approved',
                'completed' => in_array($order->status, [OrderStatus::CONFIRMED, OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::DELIVERED], true),
                'current' => $order->status === OrderStatus::CONFIRMED,
                'date' => $order->statusHistory->firstWhere('to_status', OrderStatus::CONFIRMED)?->created_at,
            ],
            [
                'title' => 'Packed & Courier Assigned',
                'description' => $activeShipment ? "AWB assigned ({$activeShipment->courier_name})" : 'Processing packaging',
                'completed' => in_array($order->status, [OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::DELIVERED], true) || ($activeShipment && $activeShipment->awb),
                'current' => $order->status === OrderStatus::PROCESSING,
                'date' => $activeShipment?->awb_requested_at,
            ],
            [
                'title' => 'In Transit',
                'description' => 'Handed over to delivery partner',
                'completed' => in_array($order->status, [OrderStatus::SHIPPED, OrderStatus::DELIVERED], true),
                'current' => $order->status === OrderStatus::SHIPPED && (!$activeShipment || $activeShipment->status->value !== 'OUT_FOR_DELIVERY'),
                'date' => $activeShipment?->shipped_at,
            ],
            [
                'title' => 'Out for Delivery',
                'description' => 'With local courier delivery agent',
                'completed' => $order->status === OrderStatus::DELIVERED || ($activeShipment && $activeShipment->status->value === 'OUT_FOR_DELIVERY'),
                'current' => $activeShipment && $activeShipment->status->value === 'OUT_FOR_DELIVERY' && $order->status !== OrderStatus::DELIVERED,
                'date' => null,
            ],
            [
                'title' => 'Delivered',
                'description' => 'Delivered to your doorstep',
                'completed' => $order->status === OrderStatus::DELIVERED,
                'current' => $order->status === OrderStatus::DELIVERED,
                'date' => $activeShipment?->delivered_at,
            ],
        ];

        $stateName = IndianStates::getName($order->shippingAddress?->state_code ?? '08');

        return view('store.order-tracking-detail', [
            'order' => $order,
            'activeShipment' => $activeShipment,
            'steps' => $steps,
            'stateName' => $stateName,
        ]);
    }
}
