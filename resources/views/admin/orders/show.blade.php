@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number)

@section('content')
<div class="space-y-6" x-data="{ cancelModalOpen: false, courierModalOpen: false }">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.index') }}" class="p-2 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-bold font-mono tracking-tight text-white">{{ $order->order_number }}</h1>
                    @php
                        $statusClasses = match($order->status->value) {
                            'CONFIRMED' => 'bg-blue-950/80 text-blue-300 border-blue-800/80',
                            'PROCESSING' => 'bg-amber-950/80 text-amber-300 border-amber-800/80',
                            'SHIPPED' => 'bg-purple-950/80 text-purple-300 border-purple-800/80',
                            'DELIVERED' => 'bg-emerald-950/80 text-emerald-300 border-emerald-800/80',
                            'CANCELLED' => 'bg-rose-950/80 text-rose-300 border-rose-800/80',
                            default => 'bg-zinc-800 text-zinc-400 border-zinc-700',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusClasses }}">
                        {{ $order->status->value }}
                    </span>
                    @if($order->payment_status->value === 'PAID')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/80 text-emerald-300 border border-emerald-800/80">
                            PAID ({{ $order->payment_method->value }})
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950/80 text-amber-300 border border-amber-800/80">
                            {{ $order->payment_status->value }} ({{ $order->payment_method->value }})
                        </span>
                    @endif
                </div>
                <p class="text-xs text-zinc-400 mt-1">Placed on {{ $order->placed_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
            </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Tax Invoice -->
            <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300 hover:text-white hover:border-zinc-700 text-xs font-medium transition-colors">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Invoice
            </a>

            <!-- Public Tracking Link -->
            <a href="{{ route('store.track-order.show', ['orderNumber' => $order->order_number, 'phone' => $order->customer_phone]) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300 hover:text-white hover:border-zinc-700 text-xs font-medium transition-colors">
                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Tracking Page
            </a>

            <!-- Book Courier Button (if eligible) -->
            @if(!$activeShipment && in_array($order->status->value, ['CONFIRMED', 'PROCESSING'], true))
                <button type="button" @click="courierModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-medium text-xs shadow-md transition-all">
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
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs shadow-md transition-all">
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
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs shadow-md transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Mark Delivered
                    </button>
                </form>
            @endif

            <!-- Cancel Button -->
            @if(!in_array($order->status->value, ['DELIVERED', 'CANCELLED'], true))
                <button type="button" @click="cancelModalOpen = true"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-rose-950/40 border border-rose-900/60 text-rose-300 hover:bg-rose-900/60 text-xs font-medium transition-colors">
                    Cancel Order
                </button>
            @endif
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-800/80 text-emerald-200 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-800/80 text-rose-200 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Main Grid: 2 Columns on Large Screens -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left / Main Column (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 1. Ordered Line Items -->
            <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-800/80 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider flex items-center gap-2">
                        <span>Ordered Items</span>
                        <span class="px-2 py-0.5 rounded-full bg-zinc-800 text-zinc-300 text-xs font-normal">
                            {{ $order->item_count }} {{ Str::plural('item', $order->item_count) }}
                        </span>
                    </h2>
                </div>
                <div class="divide-y divide-zinc-800/60">
                    @foreach($order->items as $item)
                        <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-zinc-800/10 transition-colors">
                            <div class="flex items-center gap-4">
                                @if($item->variant?->product?->primary_image_url)
                                    <img src="{{ $item->variant->product->primary_image_url }}" alt="{{ $item->product_name }}"
                                         class="w-16 h-16 rounded-lg object-cover border border-zinc-800 shrink-0 bg-zinc-900">
                                @else
                                    <div class="w-16 h-16 rounded-lg bg-zinc-800 flex items-center justify-center text-zinc-500 shrink-0">
                                        <svg class="w-6 h-6 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div>
                                    <h3 class="text-sm font-semibold text-white">{{ $item->product_name }}</h3>
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-400 mt-1">
                                        @if($item->variant_title)
                                            <span class="px-2 py-0.5 rounded bg-zinc-800 text-zinc-300 font-medium">{{ $item->variant_title }}</span>
                                        @endif
                                        <span class="font-mono text-zinc-400">SKU: {{ $item->sku }}</span>
                                        @if($item->hsn_code)
                                            <span class="text-zinc-500">&bull;</span>
                                            <span class="font-mono text-zinc-400">HSN: {{ $item->hsn_code }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex sm:flex-col items-end justify-between sm:justify-center border-t sm:border-t-0 pt-2 sm:pt-0 border-zinc-800">
                                <div class="text-sm font-bold text-white">₹{{ number_format($item->line_total_paise / 100, 2) }}</div>
                                <div class="text-xs text-zinc-400 mt-0.5">
                                    ₹{{ number_format($item->unit_price_paise / 100, 2) }} &times; {{ $item->quantity }}
                                </div>
                                <div class="text-[11px] text-zinc-500 mt-0.5">
                                    Tax: ₹{{ number_format($item->tax_paise / 100, 2) }} ({{ round($item->gst_rate_bps / 100, 1) }}%)
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Financial Breakdown -->
                <div class="px-6 py-4 bg-zinc-950/40 border-t border-zinc-800/80 space-y-2 text-xs">
                    <div class="flex justify-between text-zinc-400">
                        <span>Items Subtotal</span>
                        <span class="text-zinc-200">₹{{ number_format($order->subtotal_rupees, 2) }}</span>
                    </div>
                    @if($order->discount_paise > 0)
                        <div class="flex justify-between text-emerald-400">
                            <span>Coupon Discount ({{ $order->coupon_code }})</span>
                            <span>-₹{{ number_format($order->discount_rupees, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-zinc-400">
                        <span>Shipping & Handling</span>
                        <span class="text-zinc-200">₹{{ number_format($order->shipping_rupees, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-zinc-400">
                        <span>Applicable GST ({{ $order->supply_type->value }})</span>
                        <span class="text-zinc-200">₹{{ number_format($order->tax_rupees, 2) }}</span>
                    </div>
                    @if($order->cgst_paise > 0 || $order->sgst_paise > 0)
                        <div class="pl-4 flex justify-between text-[11px] text-zinc-500">
                            <span>CGST: ₹{{ number_format($order->cgst_paise / 100, 2) }} | SGST: ₹{{ number_format($order->sgst_paise / 100, 2) }}</span>
                        </div>
                    @endif
                    @if($order->igst_paise > 0)
                        <div class="pl-4 flex justify-between text-[11px] text-zinc-500">
                            <span>IGST: ₹{{ number_format($order->igst_paise / 100, 2) }}</span>
                        </div>
                    @endif
                    <div class="pt-2 border-t border-zinc-800 flex justify-between text-sm font-bold text-white">
                        <span>Total Payable</span>
                        <span class="text-amber-400 text-base">₹{{ number_format($order->total_rupees, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. Shipment & Logistics Card -->
            <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-800/80 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        <span>Fulfillment & Shiprocket Logistics</span>
                    </h2>
                    @if($activeShipment)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-950/80 text-purple-300 border border-purple-800/80">
                            {{ $activeShipment->status->value }}
                        </span>
                    @endif
                </div>

                <div class="p-6">
                    @if($activeShipment)
                        <!-- Active Shipment Overview -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                            <div class="p-3 rounded-lg bg-zinc-900 border border-zinc-800">
                                <span class="text-zinc-500 uppercase tracking-wider text-[10px]">Courier Partner</span>
                                <div class="font-bold text-white text-sm mt-1">{{ $activeShipment->courier_name ?? 'Pending Assignment' }}</div>
                            </div>
                            <div class="p-3 rounded-lg bg-zinc-900 border border-zinc-800">
                                <span class="text-zinc-500 uppercase tracking-wider text-[10px]">AWB Tracking Code</span>
                                <div class="font-mono font-bold text-amber-400 text-sm mt-1">{{ $activeShipment->awb ?? 'Generating...' }}</div>
                            </div>
                            <div class="p-3 rounded-lg bg-zinc-900 border border-zinc-800">
                                <span class="text-zinc-500 uppercase tracking-wider text-[10px]">Package Weight & Dims</span>
                                <div class="font-medium text-white text-sm mt-1">
                                    {{ $activeShipment->weight_grams }}g &bull; {{ $activeShipment->length_cm }}&times;{{ $activeShipment->breadth_cm }}&times;{{ $activeShipment->height_cm }} cm
                                </div>
                            </div>
                        </div>

                        <!-- Status & Tracking Link -->
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 p-4 rounded-lg bg-zinc-950/40 border border-zinc-800/80">
                            <div>
                                <span class="text-xs text-zinc-400">Live Carrier Status:</span>
                                <span class="ml-1 text-xs font-semibold text-white">{{ $activeShipment->provider_status ?? $activeShipment->status->value }}</span>
                                @if($activeShipment->shipped_at)
                                    <div class="text-[11px] text-zinc-500 mt-0.5">Dispatched on {{ $activeShipment->shipped_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if($activeShipment->tracking_url)
                                    <a href="{{ $activeShipment->tracking_url }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-medium transition-colors">
                                        Track on Carrier
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <!-- No shipment yet -->
                        <div class="text-center py-6">
                            <svg class="w-10 h-10 text-zinc-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="text-sm text-zinc-300 font-medium">No shipment booked yet</p>
                            <p class="text-xs text-zinc-500 mt-1 max-w-md mx-auto">Book a courier via Shiprocket to assign an AWB code and schedule pickup for this order.</p>

                            @if(in_array($order->status->value, ['CONFIRMED', 'PROCESSING'], true))
                                <button type="button" @click="courierModalOpen = true"
                                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-medium text-xs transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Book Courier Now
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- 3. Audit & Status History Timeline -->
            <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-zinc-800/80">
                    <h2 class="text-sm font-semibold text-white uppercase tracking-wider">Order Audit & Lifecycle History</h2>
                </div>
                <div class="p-6">
                    <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-zinc-800">
                        @forelse($order->statusHistory as $history)
                            <div class="relative">
                                <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-amber-500 ring-4 ring-zinc-900"></span>
                                <div class="text-xs font-semibold text-white">
                                    {{ $history->from_status ? $history->from_status->value . ' → ' : '' }}{{ $history->to_status->value }}
                                </div>
                                <div class="text-xs text-zinc-400 mt-0.5">
                                    {{ $history->note }}
                                </div>
                                <div class="text-[11px] text-zinc-500 mt-1">
                                    By {{ $history->actor_type->value }}{{ $history->actorAdminUser ? " ({$history->actorAdminUser->name})" : '' }} &bull;
                                    {{ $history->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-zinc-500">No status transitions recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column (1/3) -->
        <div class="space-y-6">
            <!-- 1. Customer Information Card -->
            <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Customer Details</h2>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm">
                        {{ strtoupper(substr($order->customer_name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="font-semibold text-white text-sm">{{ $order->customer_name }}</div>
                        <div class="text-xs text-zinc-400">{{ $order->customer_email }}</div>
                    </div>
                </div>
                <div class="pt-3 border-t border-zinc-800/80 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500">Phone:</span>
                        <a href="tel:{{ $order->customer_phone }}" class="font-mono text-zinc-300 hover:text-white">{{ $order->customer_phone }}</a>
                    </div>
                    @php
                        $cleanPhone = preg_replace('/\D/', '', $order->customer_phone);
                    @endphp
                    <div class="pt-2">
                        <a href="https://wa.me/{{ $cleanPhone }}?text=Hi%20{{ urlencode($order->customer_name) }},%20regarding%20your%20Dhansree%20order%20{{ $order->order_number }}..."
                           target="_blank"
                           class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-emerald-950/60 border border-emerald-800/80 text-emerald-300 hover:bg-emerald-900/60 text-xs font-medium transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            Message on WhatsApp
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. Shipping Address Card -->
            <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Shipping Address</h2>
                @if($order->shippingAddress)
                    <div class="text-xs text-zinc-300 space-y-1">
                        <div class="font-semibold text-white">{{ $order->shippingAddress->recipient_name }}</div>
                        <div>{{ $order->shippingAddress->line1 }}</div>
                        @if($order->shippingAddress->line2)
                            <div>{{ $order->shippingAddress->line2 }}</div>
                        @endif
                        <div>{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->postal_code }}</div>
                        <div class="text-zinc-400">State: {{ $order->shippingAddress->state_code }}</div>
                        <div class="pt-2 text-zinc-400">Contact: {{ $order->shippingAddress->phone }}</div>
                    </div>
                @else
                    <p class="text-xs text-zinc-500">No shipping address recorded.</p>
                @endif
            </div>

            <!-- 3. Internal Staff Notes Card -->
            <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Internal Staff Notes</h2>

                <!-- Notes List -->
                <div class="space-y-3 max-h-60 overflow-y-auto scrollbar-thin">
                    @forelse($order->notes as $note)
                        <div class="p-3 rounded-lg bg-zinc-950/60 border border-zinc-800/80 text-xs">
                            <p class="text-zinc-200 whitespace-pre-wrap">{{ $note->body }}</p>
                            <div class="text-[11px] text-zinc-500 mt-2 flex items-center justify-between">
                                <span>{{ $note->authorAdminUser?->name ?? 'Staff' }}</span>
                                <span>{{ $note->created_at->timezone('Asia/Kolkata')->format('d M, h:i A') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-500">No staff notes added yet.</p>
                    @endforelse
                </div>

                <!-- Add Note Form -->
                <form method="POST" action="{{ route('admin.orders.notes.store', $order) }}" class="space-y-2 pt-2 border-t border-zinc-800/80">
                    @csrf
                    <textarea name="body" rows="2" required placeholder="Add private note for store staff..."
                              class="w-full px-3 py-2 bg-zinc-900 border border-zinc-800 rounded-lg text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500/60"></textarea>
                    <button type="submit"
                            class="w-full px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-medium transition-colors">
                        Add Note
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Book Courier -->
    <div x-show="courierModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs">
        <div @click.away="courierModalOpen = false"
             class="w-full max-w-lg rounded-2xl bg-zinc-900 border border-zinc-800 p-6 space-y-5 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">Book Shiprocket Courier</h3>
                <button type="button" @click="courierModalOpen = false" class="text-zinc-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <p class="text-xs text-zinc-400">
                Generate an AWB and register pickup with Shiprocket for order <strong class="text-white">{{ $order->order_number }}</strong>.
                Destination postal code: <strong class="text-white">{{ $order->shippingAddress?->postal_code }}</strong>.
            </p>

            <form method="POST" action="{{ route('admin.orders.shipment.store', $order) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-2">Select Courier Partner</label>
                    <select name="courier_id" class="w-full px-3 py-2.5 bg-zinc-950 border border-zinc-800 rounded-xl text-xs text-white focus:outline-none focus:border-amber-500">
                        <option value="">Recommended / Auto-Select Fastest</option>
                        @foreach($availableCouriers as $courier)
                            <option value="{{ $courier['courier_id'] }}">
                                {{ $courier['courier_name'] }} — ₹{{ number_format($courier['rate_paise'] / 100, 2) }} (Est: {{ $courier['estimated_days'] }} days)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="p-3 rounded-xl bg-zinc-950/60 border border-zinc-800/80 text-xs text-zinc-400 space-y-1">
                    <div class="flex justify-between">
                        <span>Payment Mode:</span>
                        <span class="font-semibold text-white">{{ $order->payment_method->value }}</span>
                    </div>
                    @if($order->payment_method->value === 'COD')
                        <div class="flex justify-between">
                            <span>Collect from Customer:</span>
                            <span class="font-semibold text-amber-400">₹{{ number_format($order->total_rupees, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <button type="button" @click="courierModalOpen = false"
                            class="px-4 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 text-white text-xs font-medium transition-all shadow-md">
                        Confirm & Generate AWB
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Cancel Order -->
    <div x-show="cancelModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs">
        <div @click.away="cancelModalOpen = false"
             class="w-full max-w-md rounded-2xl bg-zinc-900 border border-zinc-800 p-6 space-y-4 shadow-2xl">
            <h3 class="text-lg font-bold text-white">Cancel Order {{ $order->order_number }}</h3>
            <p class="text-xs text-zinc-400">
                Are you sure you want to cancel this order? This will release reserved inventory stock and cancel any pending courier pickup.
            </p>

            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1">Reason for Cancellation</label>
                    <textarea name="reason" rows="3" required placeholder="e.g. Customer requested cancellation, out of stock, payment rejected..."
                              class="w-full px-3 py-2 bg-zinc-950 border border-zinc-800 rounded-xl text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="cancelModalOpen = false"
                            class="px-4 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-medium transition-colors">
                        Keep Order
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-medium transition-colors">
                        Yes, Cancel Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
