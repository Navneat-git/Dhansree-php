@extends('layouts.store')

@section('title', 'My Orders - Dhansree')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">
            My Orders
        </h1>
        <p class="text-xs sm:text-sm text-rich-black/60 mt-1">
            Track fulfillment progress, view tax receipts, and access shipment tracking.
        </p>
    </div>

    @include('store.account.partials.nav')

    @if(empty($items))
        <div class="p-8 sm:p-16 text-center rounded-3xl bg-white border border-light-gold shadow-xs space-y-4">
            <svg class="w-14 h-14 text-gold-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            <div class="space-y-1">
                <p class="font-medium text-base text-rich-black">No orders found</p>
                <p class="text-xs text-rich-black/60 max-w-sm mx-auto">
                    You have not placed any orders with this account yet.
                </p>
            </div>
            <a href="{{ url('/shop') }}" class="inline-block px-6 py-3 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white text-xs font-semibold tracking-wide transition-colors">
                Start Shopping &rarr;
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($items as $order)
                <div class="p-5 sm:p-6 rounded-3xl bg-white border border-light-gold shadow-xs hover:border-gold-400 transition-all space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-light-gold/60">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-bold text-rich-black">{{ $order['orderNumber'] }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[0.6875rem] font-semibold border
                                    @if($order['status'] === 'DELIVERED') bg-emerald-50 text-emerald-800 border-emerald-200
                                    @elseif($order['status'] === 'CANCELLED') bg-rose-50 text-rose-800 border-rose-200
                                    @else bg-amber-50 text-amber-800 border-amber-200 @endif">
                                    {{ $order['statusLabel'] }}
                                </span>
                            </div>
                            <p class="text-xs text-rich-black/50">
                                Placed on {{ \Carbon\Carbon::parse($order['placedAt'])->format('d M Y, h:i A') }} ·
                                Payment: <strong class="text-rich-black/70">{{ $order['paymentMethod'] }} ({{ $order['paymentStatus'] }})</strong>
                            </p>
                        </div>
                        <div class="text-left sm:text-right">
                            <span class="text-xs text-rich-black/50 block">Order Total</span>
                            <span class="font-bold text-base text-rich-black tabular-nums">
                                ₹{{ number_format($order['totalPaise'] / 100, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Items Preview -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($order['items'] as $item)
                            <div class="flex items-center gap-3 p-2 rounded-xl bg-cream/40 border border-light-gold/60">
                                <div class="w-12 h-14 rounded-lg overflow-hidden border border-light-gold bg-cream shrink-0">
                                    @if($item['imageUrl'])
                                        <img src="{{ $item['imageUrl'] }}" alt="{{ $item['productName'] }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-[0.65rem] text-rich-black/40">DS</div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-rich-black truncate">{{ $item['productName'] }}</p>
                                    @if($item['variantTitle'] && $item['variantTitle'] !== 'Default')
                                        <p class="text-[0.6875rem] text-rich-black/50 truncate">{{ $item['variantTitle'] }}</p>
                                    @endif
                                    <p class="text-[0.6875rem] text-rich-black/60">Qty: {{ $item['quantity'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Footer actions -->
                    <div class="pt-2 flex flex-wrap items-center justify-between gap-3">
                        <div class="text-xs text-rich-black/60">
                            @if($order['shipmentStatus'])
                                Shipment Status: <strong class="text-maroon-800">{{ $order['shipmentStatus'] }}</strong>
                            @else
                                Processing fulfillment
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('storefront.account.order-detail', $order['orderNumber']) }}"
                               class="px-4 py-2 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white text-xs font-semibold tracking-wide transition-colors">
                                View Order & Invoices &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($totalPages > 1)
            <div class="mt-8 flex items-center justify-between border-t border-light-gold/80 pt-4 text-xs font-medium">
                <div>
                    @if($currentPage > 1)
                        <a href="{{ route('storefront.account.orders', ['page' => $currentPage - 1]) }}"
                           class="px-3 py-1.5 rounded-lg border border-light-gold bg-white hover:bg-cream text-rich-black">
                            &larr; Previous
                        </a>
                    @endif
                </div>
                <span class="text-rich-black/50">Page {{ $currentPage }} of {{ $totalPages }}</span>
                <div>
                    @if($currentPage < $totalPages)
                        <a href="{{ route('storefront.account.orders', ['page' => $currentPage + 1]) }}"
                           class="px-3 py-1.5 rounded-lg border border-light-gold bg-white hover:bg-cream text-rich-black">
                            Next &rarr;
                        </a>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
