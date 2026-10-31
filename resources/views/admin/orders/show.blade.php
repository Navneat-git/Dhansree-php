@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)

@section('content')
@php
    $rawPhone = preg_replace('/\D/', '', (string) ($order->customer_phone ?? ''));
    if (strlen($rawPhone) === 10) {
        $cleanPhone = '91' . $rawPhone;
    } elseif (strlen($rawPhone) === 11 && str_starts_with($rawPhone, '0')) {
        $cleanPhone = '91' . substr($rawPhone, 1);
    } else {
        $cleanPhone = $rawPhone;
    }

    $isCod = $order->payment_method->value === 'COD';
    $isPaid = $order->payment_status->value === 'PAID';

    // Format item list for WhatsApp message
    $itemsList = $order->items->map(function ($it) {
        $variantStr = $it->variant_title ? " ({$it->variant_title})" : '';
        return "• {$it->product_name}{$variantStr} × {$it->quantity} (₹" . number_format($it->subtotal_rupees, 2) . ")";
    })->implode("\n");

    $shipping = $order->shippingAddress;
    $destAddress = $shipping
        ? "{$shipping->recipient_name}, {$shipping->line1}" . ($shipping->line2 ? ", {$shipping->line2}" : "") . ", {$shipping->city}, {$shipping->state_code} - {$shipping->postal_code}"
        : 'Address on file';

    $trackUrl = route('store.track-order.show', ['orderNumber' => $order->order_number, 'phone' => $order->customer_phone]);

    // Professional Template 1: Order Confirmation (Prepaid vs COD)
    if ($isCod) {
        $confirmMsg = "Namaste *" . $order->customer_name . "*,\n\n"
            . "Thank you for choosing *Dhansree*! 🌸\n\n"
            . "Your order *#" . $order->order_number . "* has been confirmed and is being prepared for dispatch.\n\n"
            . "📦 *Order Details:*\n"
            . $itemsList . "\n\n"
            . "💵 *Amount to Pay on Delivery:* ₹" . number_format($order->total_rupees, 2) . " (Cash on Delivery 📦)\n"
            . "📍 *Delivery Address:* " . $destAddress . "\n\n"
            . "Please ensure someone is available at this address with the exact cash amount when the delivery partner arrives.\n\n"
            . "Track your order live here:\n"
            . "🔗 " . $trackUrl . "\n\n"
            . "If you need any assistance, feel free to reply to this message. We are happy to help!\n\n"
            . "Warm regards,\n*Team Dhansree*\nJaipur, Rajasthan";
    } else {
        $confirmMsg = "Namaste *" . $order->customer_name . "*,\n\n"
            . "Thank you for choosing *Dhansree*! ✨\n\n"
            . "We are delighted to confirm that your order *#" . $order->order_number . "* has been placed successfully.\n\n"
            . "📦 *Order Details:*\n"
            . $itemsList . "\n\n"
            . "💰 *Total Amount:* ₹" . number_format($order->total_rupees, 2) . " (Paid Online via Razorpay ✅)\n"
            . "📍 *Shipping Address:* " . $destAddress . "\n\n"
            . "We are carefully preparing your handcrafted pieces. You can track live progress anytime here:\n"
            . "🔗 " . $trackUrl . "\n\n"
            . "If you have any questions or customization requests, simply reply to this message.\n\n"
            . "Warm regards,\n*Team Dhansree*\nJaipur, Rajasthan";
    }

    // Professional Template 2: Out for Delivery / Dispatched
    $deliveryCourier = $activeShipment?->courier_name ?? ($activeShipment?->provider === 'by_hand' ? 'Direct By-Hand Delivery' : 'Courier Partner');
    $dispatchMsg = "Namaste *" . $order->customer_name . "*,\n\n"
        . "Great news! Your Dhansree order *#" . $order->order_number . "* is now out for delivery! 🚀\n\n"
        . "📦 *Delivery Details:*\n"
        . "• Delivery Mode: " . $deliveryCourier . "\n"
        . ($activeShipment?->awb ? "• Tracking / AWB: " . $activeShipment->awb . "\n" : "")
        . ($isCod ? "• Amount to Collect: ₹" . number_format($order->total_rupees, 2) . " (Cash on Delivery)\n" : "• Payment: Paid Online ✅\n")
        . "• Destination: " . ($shipping?->city ?? 'Delivery Address') . "\n\n"
        . "Track your parcel live here:\n"
        . "🔗 " . $trackUrl . "\n\n"
        . "Please keep your phone handy to receive the package.\n\n"
        . "Warm regards,\n*Team Dhansree*";

    // Professional Template 3: Delivered & Thank You
    $deliveredMsg = "Namaste *" . $order->customer_name . "*,\n\n"
        . "Your Dhansree order *#" . $order->order_number . "* has been delivered! 🎉\n\n"
        . "We hope you love your purchase. Your trust means everything to us at Dhansree.\n\n"
        . "If you loved your experience, we would really appreciate your review on our website:\n"
        . "🔗 https://dhansree.com\n\n"
        . "Thank you for choosing Dhansree! ✨\n\n"
        . "Warm regards,\n*Team Dhansree*\nJaipur, Rajasthan";

    $defaultMsg = match($order->status->value) {
        'SHIPPED' => $dispatchMsg,
        'DELIVERED' => $deliveredMsg,
        default => $confirmMsg,
    };
    $defaultBtnLabel = match($order->status->value) {
        'SHIPPED' => 'Send Dispatch Update',
        'DELIVERED' => 'Send Delivery Thank You',
        default => 'Send Order Confirmation',
    };
@endphp

<div class="space-y-6" x-data="{
    cancelModalOpen: false,
    courierModalOpen: false,
    localDeliveryModalOpen: false,
    waModalOpen: false,
    activeWaTab: '{{ in_array($order->status->value, ['SHIPPED', 'DELIVERED']) ? strtolower($order->status->value) : 'confirm' }}',
    waText: '',
    templates: {},
    init() {
        this.templates = {
            confirm: @js($confirmMsg),
            shipped: @js($dispatchMsg),
            delivered: @js($deliveredMsg)
        };
        this.waText = this.templates[this.activeWaTab] || this.templates.confirm;
    },
    switchWaTab(tab) {
        this.activeWaTab = tab;
        this.waText = this.templates[tab] || '';
    },
    copyWaText() {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(this.waText);
            alert('WhatsApp message copied to clipboard!');
        }
    },
    openWhatsApp() {
        const phone = '{{ $cleanPhone }}';
        const url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(this.waText);
        window.open(url, '_blank');
    }
}">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.index') }}" class="p-2 rounded-lg bg-white border border-slate-300 text-slate-500 hover:text-slate-900 transition-colors shadow-2xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold font-mono tracking-tight text-slate-900">{{ $order->order_number }}</h1>
                    @php
                        $statusClasses = match($order->status->value) {
                            'CONFIRMED' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'PROCESSING' => 'bg-amber-50 text-amber-800 border-amber-200',
                            'SHIPPED' => 'bg-purple-50 text-purple-700 border-purple-200',
                            'DELIVERED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusClasses }}">
                        {{ $order->status->value }}
                    </span>
                    @if($order->payment_status->value === 'PAID')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            PAID ({{ $order->payment_method->value }})
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                            {{ $order->payment_status->value }} ({{ $order->payment_method->value }})
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">Placed on {{ $order->placed_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
            </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Tax Invoice -->
            <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:text-slate-900 hover:bg-slate-50 text-xs font-semibold transition-colors shadow-2xs">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Invoice
            </a>

            <!-- Public Tracking Link -->
            <a href="{{ route('store.track-order.show', ['orderNumber' => $order->order_number, 'phone' => $order->customer_phone]) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:text-slate-900 hover:bg-slate-50 text-xs font-semibold transition-colors shadow-2xs">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Tracking Page
            </a>

            <!-- Fulfillment Buttons (if eligible) -->
            @if(!$activeShipment && in_array($order->status->value, ['CONFIRMED', 'PROCESSING'], true))
                <!-- Deliver By-Hand (Self) -->
                <button type="button" @click="localDeliveryModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs shadow-xs transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    Deliver By-Hand (Self)
                </button>

                <!-- Book Courier Button -->
                <button type="button" @click="courierModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    Book Courier (Shiprocket)
                </button>
            @endif

            <!-- Dispatch Parcel Button (if AWB assigned and not yet shipped) -->
            @if($activeShipment && in_array($activeShipment->status->value, ['AWB_ASSIGNED', 'CREATED'], true))
                <form method="POST" action="{{ route('admin.orders.dispatch', ['order' => $order, 'shipment' => $activeShipment]) }}"
                      onsubmit="return confirm('Confirm parcel dispatch? This will deduct physical inventory under lock.');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Dispatch Parcel (Deduct Stock)
                    </button>
                </form>
            @endif

            <!-- Mark Delivered Button (if SHIPPED) -->
            @if($order->status->value === 'SHIPPED')
                <form method="POST" action="{{ route('admin.orders.status', $order) }}"
                      onsubmit="return confirm('Mark this order as DELIVERED? If COD, payment will also be reconciled.');">
                    @csrf
                    <input type="hidden" name="status" value="DELIVERED">
                    <input type="hidden" name="note" value="Marked as delivered by admin.">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Mark Delivered
                    </button>
                </form>
            @endif

            <!-- Cancel Button -->
            @if(!in_array($order->status->value, ['DELIVERED', 'CANCELLED'], true))
                <button type="button" @click="cancelModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition-colors">
                    Cancel Order
                </button>
            @endif
        </div>
    </div>

    <!-- Main 2-Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 1. Items Card -->
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                        Order Items
                        <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-xs font-normal">
                            {{ $order->items->count() }} items
                        </span>
                    </h2>
                </div>

                <div class="divide-y divide-slate-200">
                    @foreach($order->items as $item)
                        <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/60 transition-colors">
                            <div class="flex items-center gap-4">
                                @if($item->thumbnail_url)
                                    <img src="{{ $item->thumbnail_url }}" alt="{{ $item->product_name }}"
                                         class="w-16 h-16 rounded-lg object-cover border border-slate-200 shrink-0 bg-slate-100">
                                @else
                                    <div class="w-16 h-16 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 shrink-0">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div>
                                    <div class="font-semibold text-slate-900 text-sm">{{ $item->product_name }}</div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mt-1">
                                        @if($item->variant_title)
                                            <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 font-medium">{{ $item->variant_title }}</span>
                                        @endif
                                        <span class="font-mono text-slate-500">SKU: {{ $item->sku }}</span>
                                        @if($item->hsn_code)
                                            <span class="text-slate-400">&bull;</span>
                                            <span class="font-mono text-slate-500">HSN: {{ $item->hsn_code }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex sm:flex-col items-end justify-between sm:justify-center border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200">
                                <div class="text-sm font-bold text-slate-900">₹{{ number_format($item->subtotal_rupees, 2) }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    ₹{{ number_format($item->unit_price_rupees, 2) }} &times; {{ $item->quantity }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    GST ({{ $item->tax_rate_bps / 100 }}%): ₹{{ number_format($item->tax_rupees, 2) }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Financial Breakdown -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Items Subtotal:</span>
                        <span class="text-slate-900 font-medium">₹{{ number_format($order->subtotal_rupees, 2) }}</span>
                    </div>
                    @if($order->coupon_discount_rupees > 0)
                        <div class="flex justify-between text-emerald-700">
                            <span>Coupon Discount ({{ $order->coupon_code }}):</span>
                            <span>-₹{{ number_format($order->coupon_discount_rupees, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-slate-600">
                        <span>Shipping & Delivery:</span>
                        <span class="text-slate-900 font-medium">₹{{ number_format($order->shipping_rupees, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Total GST Tax (Included in price):</span>
                        <span class="text-slate-900 font-medium">₹{{ number_format($order->tax_rupees, 2) }}</span>
                    </div>
                    @if($order->is_interstate)
                        <div class="pl-4 flex justify-between text-[11px] text-slate-500">
                            <span>IGST:</span>
                            <span>₹{{ number_format($order->igst_rupees, 2) }}</span>
                        </div>
                    @else
                        <div class="pl-4 flex justify-between text-[11px] text-slate-500">
                            <span>CGST + SGST:</span>
                            <span>₹{{ number_format($order->cgst_rupees + $order->sgst_rupees, 2) }}</span>
                        </div>
                    @endif
                    <div class="pt-2 border-t border-slate-200 flex justify-between text-sm font-bold text-slate-900">
                        <span>Grand Total:</span>
                        <span class="text-amber-700 text-base">₹{{ number_format($order->total_rupees, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. Shipment & Courier Card -->
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Fulfillment & Shipping</h2>
                    @if($activeShipment)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $activeShipment->provider === 'by_hand' ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-purple-50 text-purple-700 border-purple-200' }} border">
                            {{ $activeShipment->provider === 'by_hand' ? 'BY-HAND DELIVERY' : $activeShipment->status->value }}
                        </span>
                    @endif
                </div>

                <div class="p-6">
                    @if($activeShipment)
                        @if($activeShipment->provider === 'by_hand')
                            <!-- By-Hand Local Fulfillment Info -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                <div class="p-3 rounded-lg bg-teal-50/60 border border-teal-200">
                                    <span class="text-teal-700 uppercase tracking-wider text-[10px] font-bold">Fulfillment Method</span>
                                    <div class="font-semibold text-slate-900 text-sm mt-1 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-teal-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        {{ $activeShipment->courier_name ?: 'By-Hand / Self Delivery' }}
                                    </div>
                                </div>
                                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                    <span class="text-slate-500 uppercase tracking-wider text-[10px]">Reference / Handover Code</span>
                                    <div class="font-mono font-bold text-teal-700 text-sm mt-1">{{ $activeShipment->awb ?: $activeShipment->reference }}</div>
                                </div>
                                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                    <span class="text-slate-500 uppercase tracking-wider text-[10px]">Status</span>
                                    <div class="text-slate-900 font-semibold text-sm mt-1">
                                        {{ $activeShipment->status->value }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 p-4 rounded-lg bg-teal-50/30 border border-teal-200">
                                <div>
                                    <span class="text-xs text-slate-600 font-medium">Local Hand Delivery:</span>
                                    <span class="ml-1 text-xs font-semibold text-teal-800">{{ $activeShipment->provider_status ?? $activeShipment->status->value }}</span>
                                    @if($activeShipment->delivered_at)
                                        <div class="text-[11px] text-emerald-600 font-medium mt-0.5">Delivered on {{ $activeShipment->delivered_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                                    @elseif($activeShipment->shipped_at)
                                        <div class="text-[11px] text-slate-500 mt-0.5">Dispatched on {{ $activeShipment->shipped_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                                    @endif
                                </div>
                                @if($activeShipment->status->value !== 'DELIVERED')
                                    <form method="POST" action="{{ route('admin.orders.status', $order) }}"
                                          onsubmit="return confirm('Mark this order as DELIVERED? If COD, payment will also be reconciled.');">
                                        @csrf
                                        <input type="hidden" name="status" value="DELIVERED">
                                        <input type="hidden" name="note" value="Delivered by hand to customer.">
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors shadow-2xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Mark Delivered Now
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @else
                            <!-- Shiprocket courier info -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                    <span class="text-slate-500 uppercase tracking-wider text-[10px]">Courier Partner</span>
                                    <div class="font-semibold text-slate-900 text-sm mt-1">{{ $activeShipment->courier_name ?: 'Shiprocket' }}</div>
                                </div>
                                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                    <span class="text-slate-500 uppercase tracking-wider text-[10px]">AWB Tracking Code</span>
                                    <div class="font-mono font-bold text-amber-700 text-sm mt-1">{{ $activeShipment->awb ?: 'Pending AWB' }}</div>
                                </div>
                                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                    <span class="text-slate-500 uppercase tracking-wider text-[10px]">Package Weight & Dims</span>
                                    <div class="text-slate-900 font-medium text-sm mt-1">
                                        {{ $activeShipment->weight_grams ?? 250 }}g &bull; {{ $activeShipment->length_cm ?? 15 }}x{{ $activeShipment->breadth_cm ?? 10 }}x{{ $activeShipment->height_cm ?? 5 }} cm
                                    </div>
                                </div>
                            </div>

                            <!-- Status & Tracking Link -->
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 p-4 rounded-lg bg-slate-50 border border-slate-200">
                                <div>
                                    <span class="text-xs text-slate-500">Live Carrier Status:</span>
                                    <span class="ml-1 text-xs font-semibold text-slate-900">{{ $activeShipment->provider_status ?? $activeShipment->status->value }}</span>
                                    @if($activeShipment->shipped_at)
                                        <div class="text-[11px] text-slate-500 mt-0.5">Dispatched on {{ $activeShipment->shipped_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($activeShipment->tracking_url)
                                        <a href="{{ $activeShipment->tracking_url }}" target="_blank"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition-colors shadow-2xs">
                                            Track on Carrier
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- No shipment yet -->
                        <div class="text-center py-6">
                            <svg class="w-10 h-10 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="text-sm text-slate-700 font-semibold">No shipment booked yet</p>
                            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">Choose to deliver personally by hand for local customers, or book a courier partner via Shiprocket.</p>

                            @if(in_array($order->status->value, ['CONFIRMED', 'PROCESSING'], true))
                                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                                    <button type="button" @click="localDeliveryModalOpen = true"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs transition-colors shadow-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                        Deliver By-Hand (Self / Local)
                                    </button>
                                    <button type="button" @click="courierModalOpen = true"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs transition-colors shadow-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Book Shiprocket Courier
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- 3. Audit & Status History Timeline -->
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Order Audit & Lifecycle History</h2>
                </div>
                <div class="p-6">
                    <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                        @forelse($order->statusHistory as $history)
                            <div class="relative">
                                <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-amber-500 ring-4 ring-white"></span>
                                <div class="text-xs font-semibold text-slate-900">
                                    {{ $history->from_status ? $history->from_status->value . ' → ' : '' }}{{ $history->to_status->value }}
                                </div>
                                <div class="text-xs text-slate-600 mt-0.5">
                                    {{ $history->note }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-1">
                                    By {{ $history->actor_type->value }}{{ $history->actorAdminUser ? " ({$history->actorAdminUser->name})" : '' }} &bull;
                                    {{ $history->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">No status transitions recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column (1/3) -->
        <div class="space-y-6">
            <!-- 1. Customer Information & WhatsApp Actions Card -->
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer Details</h2>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium {{ $isPaid ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                        {{ $isPaid ? 'Payment Verified' : 'Payment Due' }}
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center font-bold text-sm shrink-0">
                        {{ strtoupper(substr($order->customer_name, 0, 2)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-slate-900 text-sm truncate">{{ $order->customer_name }}</div>
                        <div class="text-xs text-slate-500 truncate">{{ $order->customer_email }}</div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Phone:</span>
                        <a href="tel:{{ $order->customer_phone }}" class="font-mono font-medium text-slate-900 hover:text-amber-700">{{ $order->customer_phone }}</a>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Payment Mode:</span>
                        <span class="font-semibold text-slate-900">{{ $order->payment_method->value }} ({{ $order->payment_status->value }})</span>
                    </div>

                    <!-- WhatsApp Notification Actions -->
                    <div class="pt-3 border-t border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">WhatsApp Messaging</span>
                            <span class="text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded font-medium border border-emerald-200">Live Customer Chat</span>
                        </div>

                        <!-- Primary Contextual WhatsApp Button -->
                        <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode($defaultMsg) }}"
                           target="_blank"
                           class="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-all shadow-xs">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            {{ $defaultBtnLabel }}
                        </a>

                        <!-- Customize / Switch Template Modal Button -->
                        <button type="button" @click="waModalOpen = true"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-800 hover:bg-emerald-100 text-xs font-semibold transition-colors">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Choose &amp; Edit WhatsApp Template
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Shipping Address Card -->
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Shipping Address</h2>
                @if($order->shippingAddress)
                    <div class="text-xs text-slate-700 space-y-1">
                        <div class="font-semibold text-slate-900">{{ $order->shippingAddress->recipient_name }}</div>
                        <div>{{ $order->shippingAddress->line1 }}</div>
                        @if($order->shippingAddress->line2)
                            <div>{{ $order->shippingAddress->line2 }}</div>
                        @endif
                        <div>{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->postal_code }}</div>
                        <div class="text-slate-500">State: {{ $order->shippingAddress->state_code }}</div>
                        <div class="pt-2 text-slate-500">Contact: {{ $order->shippingAddress->phone }}</div>
                    </div>
                @else
                    <p class="text-xs text-slate-500">No shipping address recorded.</p>
                @endif
            </div>

            <!-- 3. Internal Staff Notes Card -->
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Internal Staff Notes</h2>

                <!-- Notes List -->
                <div class="space-y-3 max-h-60 overflow-y-auto scrollbar-thin">
                    @forelse($order->notes as $note)
                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                            <p class="text-slate-800 whitespace-pre-wrap">{{ $note->body }}</p>
                            <div class="text-[11px] text-slate-400 mt-2 flex items-center justify-between">
                                <span>{{ $note->authorAdminUser?->name ?? 'Staff' }}</span>
                                <span>{{ $note->created_at->timezone('Asia/Kolkata')->format('d M, h:i A') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No staff notes added yet.</p>
                    @endforelse
                </div>

                <!-- Add Note Form -->
                <form method="POST" action="{{ route('admin.orders.notes.store', $order) }}" class="space-y-2 pt-2 border-t border-slate-200">
                    @csrf
                    <textarea name="body" rows="2" required placeholder="Add private note for store staff..."
                              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-2xs"></textarea>
                    <button type="submit"
                            class="w-full px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition-colors shadow-2xs">
                        Add Note
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Deliver By-Hand (Self / Local) -->
    <div x-show="localDeliveryModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75">
        <div @click.away="localDeliveryModalOpen = false"
             class="w-full max-w-lg rounded-2xl bg-white border border-slate-200 p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Deliver By-Hand (Self / Local)</h3>
                        <p class="text-xs text-slate-500">Order {{ $order->order_number }}</p>
                    </div>
                </div>
                <button type="button" @click="localDeliveryModalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <p class="text-xs text-slate-600">
                Fulfill this order directly without a third-party courier. Physical inventory stock will be deducted under lock upon confirmation.
            </p>

            <form method="POST" action="{{ route('admin.orders.local-delivery', $order) }}" class="space-y-4">
                @csrf

                <!-- Delivery Person Name -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Delivered By (Name / Staff) <span class="text-rose-500">*</span></label>
                    <input type="text" name="delivery_person" required
                           value="{{ auth('admin')->user()->name ?? 'Self Delivery' }}"
                           placeholder="e.g. Navneet Bhardwaj / Store Staff"
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-teal-600 shadow-2xs">
                </div>

                <!-- Delivery Contact Phone -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Delivery Person Contact Phone <span class="text-slate-400 font-normal">(Optional)</span></label>
                    <input type="text" name="delivery_phone"
                           placeholder="e.g. +91 98765 43210"
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-teal-600 shadow-2xs">
                </div>

                <!-- Remarks / Notes -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Local Delivery Remarks <span class="text-slate-400 font-normal">(Optional)</span></label>
                    <textarea name="note" rows="2"
                              placeholder="e.g. Delivered to customer at Mansarovar shop / Handed over personally"
                              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-teal-600 shadow-2xs"></textarea>
                </div>

                <!-- Action Choice (Dispatch vs Deliver Now) -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Fulfillment Status Action</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-teal-50/50 cursor-pointer flex items-start gap-2.5 transition-colors">
                            <input type="radio" name="delivery_action" value="deliver_now" checked class="mt-0.5 text-teal-600 focus:ring-teal-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-900">Delivered Directly Now</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Order marked DELIVERED immediately. @if($isCod) Reconciles COD payment. @endif</span>
                            </div>
                        </label>
                        <label class="p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-teal-50/50 cursor-pointer flex items-start gap-2.5 transition-colors">
                            <input type="radio" name="delivery_action" value="dispatch" class="mt-0.5 text-teal-600 focus:ring-teal-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-900">Out for Local Delivery</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">Order marked SHIPPED. Can mark delivered later.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Summary Info Box -->
                <div class="p-3 rounded-xl bg-teal-50/50 border border-teal-200 text-xs text-slate-700 space-y-1">
                    <div class="flex justify-between">
                        <span>Payment Method:</span>
                        <span class="font-semibold text-slate-900">{{ $order->payment_method->value }}</span>
                    </div>
                    @if($isCod)
                        <div class="flex justify-between">
                            <span>Cash to Collect from Customer:</span>
                            <span class="font-bold text-amber-700">₹{{ number_format($order->total_rupees, 2) }}</span>
                        </div>
                    @else
                        <div class="flex justify-between">
                            <span>Payment Status:</span>
                            <span class="font-semibold text-emerald-700">Paid Online (₹{{ number_format($order->total_rupees, 2) }})</span>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="localDeliveryModalOpen = false"
                            class="px-4 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition-all shadow-xs">
                        Confirm Local By-Hand Delivery
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: WhatsApp Message Templates & Editor -->
    <div x-show="waModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75">
        <div @click.away="waModalOpen = false"
             class="w-full max-w-xl rounded-2xl bg-white border border-slate-200 p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">WhatsApp Message Templates</h3>
                        <p class="text-xs text-slate-500">To {{ $order->customer_name }} ({{ $order->customer_phone }})</p>
                    </div>
                </div>
                <button type="button" @click="waModalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Template Tabs -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl text-xs">
                <button type="button" @click="switchWaTab('confirm')"
                        :class="activeWaTab === 'confirm' ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-center transition-colors">
                    Order Confirmation
                </button>
                <button type="button" @click="switchWaTab('shipped')"
                        :class="activeWaTab === 'shipped' ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-center transition-colors">
                    Out for Delivery
                </button>
                <button type="button" @click="switchWaTab('delivered')"
                        :class="activeWaTab === 'delivered' ? 'bg-white font-bold text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex-1 py-1.5 px-2 rounded-lg text-center transition-colors">
                    Delivered & Thanks
                </button>
            </div>

            <!-- Editable Message Box -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center justify-between">
                    <span>Message Preview & Editor:</span>
                    <span class="text-[11px] text-slate-400 font-normal">You can edit the text before sending</span>
                </label>
                <textarea x-model="waText" rows="11"
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-mono text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-500 shadow-2xs leading-relaxed"></textarea>
            </div>

            <!-- Bottom Actions -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <button type="button" @click="copyWaText()"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors shadow-2xs">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Copy Text
                </button>

                <div class="flex items-center gap-2">
                    <button type="button" @click="waModalOpen = false"
                            class="px-4 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors shadow-2xs">
                        Close
                    </button>
                    <button type="button" @click="openWhatsApp()"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-all shadow-xs">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        Send on WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Book Courier -->
    <div x-show="courierModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75">
        <div @click.away="courierModalOpen = false"
             class="w-full max-w-lg rounded-2xl bg-white border border-slate-200 p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-900">Book Shiprocket Courier</h3>
                <button type="button" @click="courierModalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <p class="text-xs text-slate-600">
                Generate an AWB and register pickup with Shiprocket for order <strong class="text-slate-900">{{ $order->order_number }}</strong>.
                Destination postal code: <strong class="text-slate-900">{{ $order->shippingAddress?->postal_code }}</strong>.
            </p>

            <form method="POST" action="{{ route('admin.orders.shipment.store', $order) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">Select Courier Partner</label>
                    <select name="courier_id" class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
                        <option value="">Recommended / Auto-Select Fastest</option>
                        @foreach($availableCouriers as $courier)
                            <option value="{{ $courier['courier_id'] }}">
                                {{ $courier['courier_name'] }} — ₹{{ number_format($courier['rate_paise'] / 100, 2) }} (Est: {{ $courier['estimated_days'] }} days)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1">
                    <div class="flex justify-between">
                        <span>Payment Mode:</span>
                        <span class="font-semibold text-slate-900">{{ $order->payment_method->value }}</span>
                    </div>
                    @if($order->payment_method->value === 'COD')
                        <div class="flex justify-between">
                            <span>Collect from Customer:</span>
                            <span class="font-bold text-amber-700">₹{{ number_format($order->total_rupees, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <button type="button" @click="courierModalOpen = false"
                            class="px-4 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold transition-all shadow-xs">
                        Confirm & Generate AWB
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Cancel Order -->
    <div x-show="cancelModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75">
        <div @click.away="cancelModalOpen = false"
             class="w-full max-w-md rounded-2xl bg-white border border-slate-200 p-6 space-y-4 shadow-2xl">
            <h3 class="text-lg font-bold text-slate-900">Cancel Order {{ $order->order_number }}</h3>
            <p class="text-xs text-slate-600">
                Are you sure you want to cancel this order? This will release reserved inventory stock and cancel any pending courier pickup.
            </p>

            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Cancellation</label>
                    <textarea name="reason" rows="3" required placeholder="e.g. Customer requested cancellation, out of stock, payment rejected..."
                              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-rose-500 shadow-2xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="cancelModalOpen = false"
                            class="px-4 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors shadow-2xs">
                        Keep Order
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition-colors shadow-xs">
                        Yes, Cancel Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
