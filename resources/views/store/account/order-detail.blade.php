@extends('layouts.store')

@section('title', 'Order ' . $order->order_number . ' - Dhansree')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16 space-y-8">
    <!-- Breadcrumb / Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-light-gold">
        <div>
            <div class="flex items-center gap-2 text-xs text-rich-black/60 mb-1">
                <a href="{{ route('storefront.account.orders') }}" class="hover:text-maroon-700">&larr; Back to all orders</a>
            </div>
            <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">
                Order <span class="font-mono font-bold">{{ $order->order_number }}</span>
            </h1>
            <p class="text-xs text-rich-black/60 mt-1">
                Placed on {{ $order->placed_at?->format('d F Y \a\t h:i A') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-semibold border
                @if($order->status->value === 'DELIVERED') bg-emerald-50 text-emerald-800 border-emerald-300
                @elseif($order->status->value === 'CANCELLED') bg-rose-50 text-rose-800 border-rose-300
                @else bg-amber-50 text-amber-800 border-amber-300 @endif">
                {{ $order->status->label() }}
            </span>
            <span class="px-3 py-1 rounded-full text-xs font-semibold border
                @if($order->payment_status->value === 'PAID') bg-emerald-50 text-emerald-800 border-emerald-300
                @else bg-slate-50 text-slate-800 border-slate-300 @endif">
                Payment: {{ $order->payment_status->label() }}
            </span>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-cream border border-light-gold text-rich-black">
                {{ $order->payment_method->label() }}
            </span>
        </div>
    </div>

    @include('store.account.partials.nav')

    <!-- Order Tracking Banner (if shipment exists) -->
    @if($order->shipments->isNotEmpty())
        @php $latestShipment = $order->shipments->last(); @endphp
        <div class="p-6 rounded-3xl bg-white border border-light-gold shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-light-gold/60">
                <div class="flex items-center gap-3">
                    <span class="p-2.5 rounded-xl bg-gold-200/50 text-maroon-800 border border-gold-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-rich-black">
                            Shipment Status: {{ $latestShipment->status->label() }}
                        </h3>
                        <p class="text-xs text-rich-black/60">
                            Courier: <strong class="text-rich-black">{{ $latestShipment->courier_name ?: 'Shiprocket Express' }}</strong>
                            @if($latestShipment->awb)
                                · AWB: <strong class="font-mono text-rich-black">{{ $latestShipment->awb }}</strong>
                            @endif
                        </p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('store.order.track.public', $order->order_number) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white text-xs font-semibold tracking-wide transition-colors">
                        <span>Live Visual Tracker</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main: Ordered Items -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-3xl bg-white border border-light-gold shadow-xs p-6 space-y-4">
                <h2 class="text-base font-serif text-maroon-800 font-semibold pb-3 border-b border-light-gold/60">
                    Items in this Order ({{ $order->item_count }})
                </h2>

                <div class="divide-y divide-light-gold/60">
                    @foreach($order->items as $item)
                        <div class="py-4 first:pt-0 last:pb-0 flex items-start gap-4">
                            <div class="w-16 h-20 rounded-xl overflow-hidden border border-light-gold bg-cream shrink-0">
                                @if($item->product && $item->product->images->isNotEmpty())
                                    <img src="/storage/{{ ltrim($item->product->images->first()->storage_key, '/') }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-xs text-rich-black/30 font-serif">DS</div>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0 space-y-1">
                                <h4 class="text-sm font-semibold text-rich-black truncate">
                                    {{ $item->product_name }}
                                </h4>
                                @if($item->variant_title && $item->variant_title !== 'Default')
                                    <p class="text-xs text-rich-black/60">Variant: {{ $item->variant_title }}</p>
                                @endif
                                <p class="text-xs text-rich-black/50 font-mono">SKU: {{ $item->sku }}</p>
                                <p class="text-xs text-rich-black/70">
                                    Quantity: <strong class="text-rich-black">{{ $item->quantity }}</strong> &times; ₹{{ number_format($item->unit_price_paise / 100, 2) }}
                                    @if($item->discount_paise > 0)
                                        <span class="text-emerald-700 ml-1">(Saved ₹{{ number_format($item->discount_paise / 100, 2) }})</span>
                                    @endif
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="font-bold text-sm text-rich-black tabular-nums">
                                    ₹{{ number_format($item->line_total_paise / 100, 2) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Customer Note -->
            @if($order->customer_note)
                <div class="rounded-3xl bg-white border border-light-gold p-6 space-y-2">
                    <h3 class="text-xs font-semibold text-rich-black uppercase tracking-wider">Your Order Instructions</h3>
                    <p class="text-xs text-rich-black/70 italic bg-cream/50 p-3 rounded-xl border border-light-gold/60">
                        "{{ $order->customer_note }}"
                    </p>
                </div>
            @endif
        </div>

        <!-- Sidebar: Price Breakdown & Delivery Address -->
        <div class="space-y-6">
            <!-- Price Summary -->
            <div class="rounded-3xl bg-white border border-light-gold shadow-xs p-6 space-y-4">
                <h3 class="text-sm font-serif text-maroon-800 font-semibold pb-3 border-b border-light-gold/60">
                    Payment Summary
                </h3>

                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between text-rich-black/70">
                        <dt>Subtotal</dt>
                        <dd class="tabular-nums font-medium text-rich-black">₹{{ number_format($order->subtotal_paise / 100, 2) }}</dd>
                    </div>

                    @if($order->discount_paise > 0)
                        <div class="flex justify-between text-emerald-700">
                            <dt>Discount {{ $order->coupon_code ? "({$order->coupon_code})" : '' }}</dt>
                            <dd class="tabular-nums font-medium">-₹{{ number_format($order->discount_paise / 100, 2) }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between text-rich-black/70">
                        <dt>Shipping</dt>
                        <dd class="tabular-nums font-medium text-rich-black">
                            {{ $order->shipping_paise > 0 ? '₹' . number_format($order->shipping_paise / 100, 2) : 'FREE' }}
                        </dd>
                    </div>

                    @if($order->tax_paise > 0)
                        <div class="flex justify-between text-rich-black/60 pt-1 border-t border-light-gold/40">
                            <dt>Applicable GST</dt>
                            <dd class="tabular-nums font-medium">₹{{ number_format($order->tax_paise / 100, 2) }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between text-sm font-bold text-rich-black pt-3 border-t border-light-gold">
                        <dt>Total Paid</dt>
                        <dd class="tabular-nums text-maroon-800">₹{{ number_format($order->total_paise / 100, 2) }}</dd>
                    </div>
                </dl>
                @if($order->prices_include_tax)
                    <p class="text-[0.6875rem] text-rich-black/50 text-center">Prices are inclusive of all GST taxes.</p>
                @endif
            </div>

            <!-- Delivery Address Card -->
            @if($order->shippingAddress)
                @php $addr = $order->shippingAddress; @endphp
                <div class="rounded-3xl bg-white border border-light-gold shadow-xs p-6 space-y-3">
                    <h3 class="text-sm font-serif text-maroon-800 font-semibold pb-2 border-b border-light-gold/60">
                        Delivered To
                    </h3>
                    <div class="text-xs text-rich-black/80 space-y-1">
                        <p class="font-bold text-rich-black text-sm">{{ $addr->recipient_name }}</p>
                        <p>{{ $addr->line1 }}</p>
                        @if($addr->line2)<p>{{ $addr->line2 }}</p>@endif
                        @if($addr->landmark)<p class="text-rich-black/60">Near: {{ $addr->landmark }}</p>@endif
                        <p>{{ $addr->city }}, {{ $addr->state_name }} - <span class="font-mono">{{ $addr->postal_code }}</span></p>
                        <p class="pt-2 text-rich-black/60">Phone: <strong class="text-rich-black font-mono">{{ $addr->phone }}</strong></p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
