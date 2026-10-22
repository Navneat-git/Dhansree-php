@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="space-y-6" x-data="{ cancelModalOpen: false, courierModalOpen: false }">
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

            <!-- Book Courier Button (if eligible) -->
            @if(!$activeShipment && in_array($order->status->value, ['CONFIRMED', 'PROCESSING'], true))
                <button type="button" @click="courierModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    Book Shiprocket Courier
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
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                            {{ $activeShipment->status->value }}
                        </span>
                    @endif
                </div>

                <div class="p-6">
                    @if($activeShipment)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                                <span class="text-slate-500 uppercase tracking-wider text-[10px]">Courier Partner</span>
                                <div class="font-semibold text-slate-900 text-sm mt-1">{{ $activeShipment->carrier_name ?: 'Shiprocket' }}</div>
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
                    @else
                        <!-- No shipment yet -->
                        <div class="text-center py-6">
                            <svg class="w-10 h-10 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="text-sm text-slate-700 font-semibold">No shipment booked yet</p>
                            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">Book a courier via Shiprocket to assign an AWB code and schedule pickup for this order.</p>

                            @if(in_array($order->status->value, ['CONFIRMED', 'PROCESSING'], true))
                                <button type="button" @click="courierModalOpen = true"
                                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs transition-colors shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Book Courier Now
                                </button>
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
            <!-- 1. Customer Information Card -->
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer Details</h2>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(substr($order->customer_name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="font-semibold text-slate-900 text-sm">{{ $order->customer_name }}</div>
                        <div class="text-xs text-slate-500">{{ $order->customer_email }}</div>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-200 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Phone:</span>
                        <a href="tel:{{ $order->customer_phone }}" class="font-mono text-slate-900 hover:text-amber-700">{{ $order->customer_phone }}</a>
                    </div>
                    @php
                        $cleanPhone = preg_replace('/\D/', '', $order->customer_phone);
                    @endphp
                    <div class="pt-2">
                        <a href="https://wa.me/{{ $cleanPhone }}?text=Hi%20{{ urlencode($order->customer_name) }},%20regarding%20your%20Dhansree%20order%20{{ $order->order_number }}..."
                           target="_blank"
                           class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-300 text-emerald-800 hover:bg-emerald-100 text-xs font-semibold transition-colors shadow-2xs">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            Message on WhatsApp
                        </a>
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

    <!-- Modal: Book Courier -->
    <div x-show="courierModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
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
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
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
