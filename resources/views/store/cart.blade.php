@extends('layouts.store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-14">
    
    <div class="mb-8">
        <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">Your Shopping Bag</h1>
        <p class="text-xs sm:text-sm text-rich-black/60 mt-1">Review your selected items and proceed to secure checkout.</p>
    </div>

    @if(empty($cart['items']))
        <!-- Empty Cart State -->
        <div class="bg-white rounded-2xl border border-light-gold p-12 sm:p-16 text-center space-y-6 shadow-xs max-w-2xl mx-auto my-8">
            <div class="w-20 h-20 mx-auto rounded-full bg-gold-200/50 flex items-center justify-center text-maroon-800">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25c-.669 0-1.189-.578-1.119-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                </svg>
            </div>
            
            <div class="space-y-2">
                <h2 class="font-serif text-2xl sm:text-3xl text-rich-black font-medium">A little room for something lovely</h2>
                <p class="text-xs sm:text-sm text-rich-black/60 max-w-sm mx-auto">
                    Your bag is empty. Explore our latest handcrafted ethnic styles or smart living gadgets.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <a href="{{ $continueShoppingUrl ?? url('/') }}"
                   @click="
                       const ref = document.referrer;
                       const isSameOrigin = ref && ref.startsWith(window.location.origin);
                       const isCartOrCheckout = ref && (ref.includes('/cart') || ref.includes('/checkout'));
                       if (window.history.length > 1 && isSameOrigin && !isCartOrCheckout) {
                           $event.preventDefault();
                           window.history.back();
                       }
                   "
                   class="w-full sm:w-auto px-6 py-3 rounded-lg bg-maroon-800 text-white text-xs font-semibold uppercase tracking-wider hover:bg-maroon-900 transition-colors shadow-sm flex items-center justify-center gap-1.5">
                    <span>←</span>
                    <span>Continue Shopping</span>
                </a>
                <a href="{{ url('/') }}"
                   class="w-full sm:w-auto px-6 py-3 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-semibold uppercase tracking-wider hover:bg-maroon-50 transition-colors">
                    🌸 Shop Women's Fashion
                </a>
                <a href="{{ url('/smart-living') }}"
                   class="w-full sm:w-auto px-6 py-3 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-semibold uppercase tracking-wider hover:bg-maroon-50 transition-colors">
                    ⚡ Shop Smart Living
                </a>
            </div>
        </div>
    @else
        <!-- Filled Cart View -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- Left: Line items & shipping tracker -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Free Shipping Progress Banner -->
                <div class="p-4 sm:p-5 rounded-2xl bg-warm-beige/40 border border-light-gold space-y-2">
                    @if($cart['is_free_shipping'])
                        <div class="flex items-center gap-2 text-emerald-800 text-xs sm:text-sm font-semibold">
                            <span>🎉</span>
                            <span>Congratulations! Your order qualifies for <strong>FREE Express Shipping</strong>!</span>
                        </div>
                    @else
                        <div class="flex items-center justify-between text-xs sm:text-sm">
                            <span class="text-rich-black/80 font-medium">
                                Add <strong class="text-maroon-800">₹{{ number_format($cart['remaining_for_free_shipping_rupees'], 0) }}</strong> more to unlock <strong>FREE Express Delivery</strong>!
                            </span>
                            <span class="font-bold text-gold-600">{{ $cart['free_shipping_progress_percentage'] }}%</span>
                        </div>
                        <div class="w-full h-2 bg-light-gold/60 rounded-full overflow-hidden">
                            <div class="h-full bg-gold-500 rounded-full transition-all duration-300"
                                 style="width: {{ $cart['free_shipping_progress_percentage'] }}%"></div>
                        </div>
                    @endif
                </div>

                <!-- Items list -->
                <div class="bg-white rounded-2xl border border-light-gold divide-y divide-light-gold/60 shadow-xs overflow-hidden">
                    @foreach($cart['items'] as $item)
                        <div class="p-4 sm:p-6 flex flex-col sm:flex-row gap-4 sm:gap-6 items-start sm:items-center justify-between">
                            
                            <div class="flex gap-4 items-center">
                                <div class="w-20 h-24 rounded-lg overflow-hidden bg-warm-beige shrink-0 border border-light-gold">
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['product_name'] }}" class="w-full h-full object-cover">
                                </div>

                                <div class="space-y-1">
                                    <h3 class="text-sm font-semibold text-rich-black leading-snug">
                                        <a href="{{ url('/product/' . $item['product_slug']) }}" class="hover:text-maroon-800 transition-colors">
                                            {{ $item['product_name'] }}
                                        </a>
                                    </h3>
                                    <p class="text-xs text-rich-black/60">
                                        Option: <span class="font-semibold text-rich-black">{{ $item['variant_title'] }}</span>
                                    </p>
                                    <div class="flex items-baseline gap-2 pt-0.5 flex-wrap">
                                        <span class="text-xs text-rich-black/60 font-medium">Unit:</span>
                                        @if(($item['discounted_price_rupees'] ?? $item['price_rupees']) < $item['price_rupees'])
                                            <span class="font-bold text-maroon-800 text-xs sm:text-sm">₹{{ number_format($item['discounted_price_rupees'], 0) }}</span>
                                            <del class="text-[11px] text-rich-black/40 line-through">₹{{ number_format($item['price_rupees'], 0) }}</del>
                                            @if(!empty($item['mrp_rupees']) && $item['mrp_rupees'] > $item['price_rupees'])
                                                <del class="text-[10px] text-rich-black/30 line-through">₹{{ number_format($item['mrp_rupees'], 0) }}</del>
                                            @endif
                                        @elseif(!empty($item['mrp_rupees']) && $item['mrp_rupees'] > $item['price_rupees'])
                                            <span class="font-bold text-maroon-800 text-xs sm:text-sm">₹{{ number_format($item['price_rupees'], 0) }}</span>
                                            <del class="text-[11px] text-rich-black/40 line-through">₹{{ number_format($item['mrp_rupees'], 0) }}</del>
                                        @else
                                            <span class="font-semibold text-rich-black text-xs sm:text-sm">₹{{ number_format($item['price_rupees'], 0) }}</span>
                                        @endif

                                        @if(!empty($item['discount_percentage']))
                                            <span class="font-bold text-emerald-700 text-[11px]">
                                                {{ $item['discount_percentage'] }}% OFF
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between w-full sm:w-auto sm:gap-8 pt-3 sm:pt-0 border-t sm:border-t-0 border-light-gold/40">
                                <!-- Stepper -->
                                <div class="flex items-center border border-light-gold rounded-lg p-0.5">
                                    <button type="button"
                                            @click="updateCartQty({{ $item['variant_id'] }}, {{ $item['quantity'] - 1 }}); window.location.reload();"
                                            class="w-7 h-7 flex items-center justify-center text-xs font-bold text-rich-black/60 hover:text-maroon-800">
                                        −
                                    </button>
                                    <span class="w-8 text-center text-xs font-bold text-rich-black">{{ $item['quantity'] }}</span>
                                    <button type="button"
                                            @click="updateCartQty({{ $item['variant_id'] }}, {{ $item['quantity'] + 1 }}); window.location.reload();"
                                            class="w-7 h-7 flex items-center justify-center text-xs font-bold text-rich-black/60 hover:text-maroon-800">
                                        +
                                    </button>
                                </div>

                                <!-- Line total -->
                                <div class="text-right shrink-0 min-w-[80px]">
                                    <div class="flex flex-col items-end">
                                        @if(($item['line_discounted_total_rupees'] ?? $item['line_total_rupees']) < $item['line_total_rupees'])
                                            <span class="text-base sm:text-lg font-bold text-maroon-800">
                                                ₹{{ number_format($item['line_discounted_total_rupees'], 0) }}
                                            </span>
                                            <del class="text-xs sm:text-sm text-rich-black/40 line-through">
                                                ₹{{ number_format($item['line_total_rupees'], 0) }}
                                            </del>
                                            @if(!empty($item['discount_percentage']))
                                                <span class="text-[10px] sm:text-[11px] font-bold text-emerald-700">
                                                    {{ $item['discount_percentage'] }}% OFF
                                                </span>
                                            @endif
                                        @elseif(!empty($item['line_mrp_total_rupees']) && $item['line_mrp_total_rupees'] > $item['line_total_rupees'])
                                            <span class="text-base sm:text-lg font-bold text-maroon-800">
                                                ₹{{ number_format($item['line_total_rupees'], 0) }}
                                            </span>
                                            <del class="text-xs sm:text-sm text-rich-black/40 line-through">
                                                ₹{{ number_format($item['line_mrp_total_rupees'], 0) }}
                                            </del>
                                            @if(!empty($item['discount_percentage']))
                                                <span class="text-[10px] sm:text-[11px] font-bold text-emerald-700">
                                                    {{ $item['discount_percentage'] }}% OFF
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-base sm:text-lg font-bold text-maroon-800">
                                                ₹{{ number_format($item['line_total_rupees'], 0) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Remove button -->
                                <button type="button"
                                        @click="removeCartItem({{ $item['variant_id'] }}); window.location.reload();"
                                        class="p-2 text-rich-black/30 hover:text-rose-600 transition-colors"
                                        aria-label="Remove item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </button>
                            </div>

                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between text-xs text-rich-black/60 pt-2">
                    <a href="{{ $continueShoppingUrl ?? url('/') }}"
                       @click="
                           const ref = document.referrer;
                           const isSameOrigin = ref && ref.startsWith(window.location.origin);
                           const isCartOrCheckout = ref && (ref.includes('/cart') || ref.includes('/checkout'));
                           if (window.history.length > 1 && isSameOrigin && !isCartOrCheckout) {
                               $event.preventDefault();
                               window.history.back();
                           }
                       "
                       class="hover:text-maroon-800 transition-colors flex items-center gap-1 font-semibold">
                        <span>←</span>
                        <span>Continue Shopping</span>
                    </a>
                    <button type="button"
                            @click="fetch('{{ url('/cart/clear') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }).then(() => window.location.reload())"
                            class="hover:text-rose-600 underline">
                        Empty Bag
                    </button>
                </div>
            </div>

            <!-- Right: Order Summary -->
            <div class="lg:col-span-4 bg-white p-6 sm:p-8 rounded-2xl border border-light-gold shadow-md space-y-6 sticky top-28">
                <h2 class="font-serif text-xl font-bold text-rich-black">Order Summary</h2>

                @php
                    $store = \App\Models\StoreSettings::current();
                    $defaultCoupon = $store->getDefaultCoupon();
                    $cartDiscountRupees = $cart['discount_rupees'] ?? 0;
                    $cartCouponCode = $cart['coupon_code'] ?? ($defaultCoupon?->code ?? 'FESTIVE10');
                    $subtotalRupees = $cart['subtotal_rupees'] ?? 0;
                    $effectiveSubtotalRupees = $cart['effective_subtotal_rupees'] ?? ($subtotalRupees - $cartDiscountRupees);
                    $totalPayableRupees = $cart['total_rupees'] ?? ($effectiveSubtotalRupees + ($cart['shipping_rupees'] ?? 0));
                @endphp

                <div class="space-y-3 text-xs sm:text-sm divide-y divide-light-gold/60">
                    <div class="flex justify-between py-2 text-rich-black/70">
                        <span>Items Subtotal ({{ $cart['total_quantity'] }})</span>
                        <div class="text-right">
                            <span class="font-bold text-rich-black">₹{{ number_format($effectiveSubtotalRupees, 0) }}</span>
                            @if($cartDiscountRupees > 0)
                                <del class="block text-xs text-rich-black/40 line-through">₹{{ number_format($subtotalRupees, 0) }}</del>
                            @endif
                        </div>
                    </div>

                    @if($cartDiscountRupees > 0)
                        <div class="flex justify-between py-2 text-emerald-700 font-medium">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                <span>Coupon {{ $cartCouponCode }}</span>
                            </span>
                            <span class="font-semibold">−₹{{ number_format($cartDiscountRupees, 0) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between py-2 text-rich-black/70">
                        <span>Estimated Shipping</span>
                        @if($cart['is_free_shipping'])
                            <span class="font-bold text-emerald-700 uppercase">FREE</span>
                        @else
                            <span class="font-bold text-rich-black">₹{{ number_format($cart['shipping_rupees'], 0) }}</span>
                        @endif
                    </div>

                    <div class="flex justify-between pt-4 text-base font-bold text-rich-black">
                        <span>Total Payable</span>
                        <div class="text-right">
                            <span class="text-xl text-maroon-800">₹{{ number_format($totalPayableRupees, 0) }}</span>
                            @if($cartDiscountRupees > 0)
                                <span class="block text-[10px] text-emerald-700 font-normal">({{ $cartCouponCode }} auto-applied)</span>
                            @endif
                        </div>
                    </div>
                </div>

                <p class="text-[0.6875rem] text-rich-black/50 text-center -mt-2">
                    @if($defaultCoupon)
                        Coupon <strong>{{ $defaultCoupon->code }}</strong> will be applied automatically at checkout.
                    @else
                        Inclusive of all GST taxes. Promo codes applied on checkout.
                    @endif
                </p>

                <!-- Checkout CTA -->
                <a href="{{ url('/checkout') }}"
                   class="w-full py-4 rounded-xl bg-maroon-800 text-white text-xs font-bold uppercase tracking-wider hover:bg-maroon-900 transition-all shadow-md text-center block">
                    Proceed to Checkout ➔
                </a>

                <!-- Security Badges -->
                <div class="pt-4 border-t border-light-gold/60 space-y-2 text-[0.6875rem] text-rich-black/60">
                    <div class="flex items-center gap-2">
                        <span>🔒</span>
                        <span>256-Bit SSL Encrypted Checkout</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>🛡️</span>
                        <span>Razorpay Verified Payment Gateway</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>📦</span>
                        <span>Easy 7-Day Hassle-Free Returns</span>
                    </div>
                </div>

            </div>

        </div>
    @endif

</div>
@endsection
