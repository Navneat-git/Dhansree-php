@extends('layouts.store')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
    
    <!-- Success Celebration Header -->
    <div class="text-center space-y-4 mb-10">
        <div class="w-20 h-20 mx-auto rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center text-3xl shadow-sm">
            ✓
        </div>

        <span class="inline-block px-3 py-1 rounded-full bg-gold-200/70 border border-gold-400 text-maroon-900 text-xs font-semibold uppercase tracking-wider">
            Order Confirmed
        </span>

        <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl text-maroon-800 font-normal">
            Thank You for Your Order!
        </h1>

        <p class="text-xs sm:text-sm text-rich-black/70 max-w-md mx-auto leading-relaxed">
            We've sent an order confirmation to <strong class="text-rich-black">{{ $order->customer_email }}</strong>. We'll notify you as soon as your parcel is dispatched.
        </p>
    </div>

    <!-- Order Meta Card -->
    <div class="bg-white rounded-2xl border border-light-gold shadow-sm p-6 sm:p-8 space-y-6">
        
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pb-6 border-b border-light-gold/60 text-xs">
            <div>
                <span class="text-rich-black/50 block font-semibold uppercase tracking-wider text-[0.625rem]">Order Number</span>
                <strong class="font-mono text-sm sm:text-base text-maroon-800">{{ $order->order_number }}</strong>
            </div>

            <div>
                <span class="text-rich-black/50 block font-semibold uppercase tracking-wider text-[0.625rem]">Date Placed</span>
                <span class="font-medium text-rich-black">{{ $order->placed_at ? $order->placed_at->format('M d, Y') : now()->format('M d, Y') }}</span>
            </div>

            <div>
                <span class="text-rich-black/50 block font-semibold uppercase tracking-wider text-[0.625rem]">Payment Method</span>
                <span class="font-semibold text-rich-black">
                    {{ $order->payment_method->value === 'COD' ? 'Cash on Delivery' : 'Online (Razorpay)' }}
                </span>
            </div>

            <div>
                <span class="text-rich-black/50 block font-semibold uppercase tracking-wider text-[0.625rem]">Payment Status</span>
                <span class="inline-block px-2 py-0.5 rounded text-[0.6875rem] font-bold uppercase {{ $order->payment_status->value === 'PAID' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $order->payment_status->value === 'PAID' ? 'PAID' : 'DUE ON DELIVERY' }}
                </span>
            </div>
        </div>

        <!-- Two Columns: Shipping Address + Items -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-xs">
            
            <!-- Shipping Address -->
            <div class="space-y-3">
                <h3 class="font-serif text-base font-bold text-rich-black">Delivery Details</h3>
                <div class="p-4 rounded-xl bg-warm-beige/30 border border-light-gold space-y-1 text-rich-black/80 leading-relaxed">
                    <p class="font-bold text-sm text-rich-black">{{ $address->recipient_name ?? $order->customer_name }}</p>
                    <p>{{ $address->line1 }}</p>
                    @if($address->line2)
                        <p>{{ $address->line2 }}</p>
                    @endif
                    @if($address->landmark)
                        <p>Landmark: {{ $address->landmark }}</p>
                    @endif
                    <p>{{ $address->city }}, {{ $address->state_name ?? $address->state_code }} — {{ $address->postal_code }}</p>
                    <p class="pt-1"><strong>Phone:</strong> {{ $address->phone ?? $order->customer_phone }}</p>
                </div>
            </div>

            <!-- Items Breakdown -->
            <div class="space-y-3">
                <h3 class="font-serif text-base font-bold text-rich-black">Items Ordered</h3>
                <div class="space-y-3 divide-y divide-light-gold/50 max-h-64 overflow-y-auto pr-1">
                    @foreach($order->items as $item)
                        <div class="pt-3 first:pt-0 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h4 class="font-semibold text-rich-black truncate">{{ $item->product_name }}</h4>
                                <p class="text-[0.6875rem] text-rich-black/60">{{ $item->variant_title }} • Qty: {{ $item->quantity }}</p>
                            </div>
                            <span class="font-bold text-rich-black shrink-0">₹{{ number_format($item->line_total_paise / 100, 0) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Totals Summary -->
        <div class="pt-6 border-t border-light-gold/60 max-w-sm ml-auto space-y-2 text-xs">
            <div class="flex justify-between text-rich-black/70">
                <span>Items Subtotal</span>
                <span class="font-semibold text-rich-black">₹{{ number_format($order->subtotal_paise / 100, 0) }}</span>
            </div>

            @if($order->discount_paise > 0)
                <div class="flex justify-between text-emerald-700 font-semibold">
                    <span>Discount ({{ $order->coupon_code }})</span>
                    <span>−₹{{ number_format($order->discount_paise / 100, 0) }}</span>
                </div>
            @endif

            <div class="flex justify-between text-rich-black/70">
                <span>Shipping</span>
                <span>{{ $order->shipping_paise === 0 ? 'FREE' : '₹' . number_format($order->shipping_paise / 100, 0) }}</span>
            </div>

            <div class="flex justify-between pt-2 border-t border-light-gold/60 text-sm font-bold text-rich-black">
                <span>Total Amount</span>
                <span class="text-base text-maroon-800">₹{{ number_format($order->total_paise / 100, 0) }}</span>
            </div>
        </div>

    </div>

    <!-- Action CTAs -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-8">
        <a href="{{ route('store.track-order.show', ['orderNumber' => $order->order_number, 'phone' => $order->customer_phone]) }}"
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-700 text-white text-xs font-bold uppercase tracking-wider hover:from-amber-500 hover:to-amber-600 transition-all shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
            Track Your Order
        </a>
        <a href="{{ url('/') }}"
           class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-xl bg-maroon-800 text-white text-xs font-semibold uppercase tracking-wider hover:bg-maroon-900 transition-colors shadow-md">
            Continue Shopping ➔
        </a>
    </div>


</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.dhansreeTrack === 'function') {
            window.dhansreeTrack('purchase', {
                transaction_id: '{{ $order->order_number }}',
                order_id: '{{ $order->order_number }}',
                value: {{ round($order->total_paise / 100, 2) }},
                currency: 'INR'
            });
        }
    });
</script>
@endsection
