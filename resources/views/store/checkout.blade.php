@extends('layouts.store')

@push('head')
@if($razorpayEnabled)
    <!-- Razorpay Checkout JS SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endif
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-14"
     x-data="checkoutApp()">
    
    <div class="mb-8">
        <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">Express Checkout</h1>
        <p class="text-xs sm:text-sm text-rich-black/60 mt-1">Please enter your shipping and delivery details to complete your order.</p>
    </div>

    <!-- Error Alert Banner -->
    <template x-if="errorMessage">
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>⚠️</span>
                <span x-text="errorMessage"></span>
            </div>
            <button type="button" @click="errorMessage = ''" class="font-bold text-rose-800">✕</button>
        </div>
    </template>

    <form @submit.prevent="submitOrder">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- Left Column: Form Fields -->
            <div class="lg:col-span-7 space-y-8">
                
                <!-- 1. Customer Information -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-light-gold shadow-xs space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-light-gold/60">
                        <span class="w-6 h-6 rounded-full bg-maroon-800 text-white text-xs font-bold flex items-center justify-center">1</span>
                        <h2 class="font-serif text-lg sm:text-xl font-bold text-rich-black">Contact Information</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label for="name" class="block font-semibold text-rich-black/80 mb-1">Full Name *</label>
                            <input type="text"
                                   id="name"
                                   x-model="form.name"
                                   required
                                   placeholder="e.g. Priya Sharma"
                                   class="w-full px-3.5 py-2.5 rounded-lg border border-light-gold bg-ivory/30 text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                        </div>

                        <div>
                            <label for="email" class="block font-semibold text-rich-black/80 mb-1">Email Address *</label>
                            <input type="email"
                                   id="email"
                                   x-model="form.email"
                                   required
                                   placeholder="priya@example.com"
                                   class="w-full px-3.5 py-2.5 rounded-lg border border-light-gold bg-ivory/30 text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                            <span class="text-[0.625rem] text-rich-black/50">For order confirmation &amp; live tracking.</span>
                        </div>

                        <div>
                            <label for="phone" class="block font-semibold text-rich-black/80 mb-1">Mobile Number (10 digits) *</label>
                            <div class="flex items-center">
                                <span class="px-3 py-2.5 bg-warm-beige border border-r-0 border-light-gold rounded-l-lg text-rich-black/70 font-semibold">+91</span>
                                <input type="tel"
                                       id="phone"
                                       x-model="form.phone"
                                       required
                                       maxlength="10"
                                       placeholder="9876543210"
                                       class="w-full px-3.5 py-2.5 rounded-r-lg border border-light-gold bg-ivory/30 text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                            </div>
                            <span class="text-[0.625rem] text-rich-black/50">Required for courier delivery coordination.</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Shipping Address -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-light-gold shadow-xs space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-light-gold/60">
                        <span class="w-6 h-6 rounded-full bg-maroon-800 text-white text-xs font-bold flex items-center justify-center">2</span>
                        <h2 class="font-serif text-lg sm:text-xl font-bold text-rich-black">Delivery Address</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label for="line1" class="block font-semibold text-rich-black/80 mb-1">House / Flat / Street Address *</label>
                            <input type="text"
                                   id="line1"
                                   x-model="form.line1"
                                   required
                                   placeholder="House No., Building Name, Street"
                                   class="w-full px-3.5 py-2.5 rounded-lg border border-light-gold bg-ivory/30 text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                        </div>

                        <div>
                            <label for="line2" class="block font-semibold text-rich-black/80 mb-1">Area / Colony (Optional)</label>
                            <input type="text"
                                   id="line2"
                                   x-model="form.line2"
                                   placeholder="Area, Sector, Colony"
                                   class="w-full px-3.5 py-2.5 rounded-lg border border-light-gold bg-ivory/30 text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                        </div>

                        <div>
                            <label for="landmark" class="block font-semibold text-rich-black/80 mb-1">Landmark (Optional)</label>
                            <input type="text"
                                   id="landmark"
                                   x-model="form.landmark"
                                   placeholder="Near Temple, Opposite School..."
                                   class="w-full px-3.5 py-2.5 rounded-lg border border-light-gold bg-ivory/30 text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                        </div>

                        <div>
                            <label for="city" class="block font-semibold text-rich-black/80 mb-1">City / Town *</label>
                            <input type="text"
                                   id="city"
                                   x-model="form.city"
                                   required
                                   placeholder="City"
                                   class="w-full px-3.5 py-2.5 rounded-lg border border-light-gold bg-ivory/30 text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                        </div>

                        <div>
                            <label for="postal_code" class="block font-semibold text-rich-black/80 mb-1">PIN Code (6 digits) *</label>
                            <input type="text"
                                   id="postal_code"
                                   x-model="form.postal_code"
                                   @blur="recalculateQuote"
                                   required
                                   maxlength="6"
                                   placeholder="302001"
                                   class="w-full px-3.5 py-2.5 rounded-lg border border-light-gold bg-ivory/30 text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="state_code" class="block font-semibold text-rich-black/80 mb-1">State / Union Territory *</label>
                            <select id="state_code"
                                    x-model="form.state_code"
                                    @change="recalculateQuote"
                                    required
                                    class="w-full px-3.5 py-2.5 rounded-lg border border-light-gold bg-ivory/30 text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                                @foreach($states as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. Payment Method -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl border border-light-gold shadow-xs space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-light-gold/60">
                        <span class="w-6 h-6 rounded-full bg-maroon-800 text-white text-xs font-bold flex items-center justify-center">3</span>
                        <h2 class="font-serif text-lg sm:text-xl font-bold text-rich-black">Payment Method</h2>
                    </div>

                    <div class="space-y-3">
                        <!-- Cash on Delivery (COD) -->
                        <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all"
                               :class="form.payment_method === 'COD' ? 'border-maroon-800 bg-maroon-50/40 ring-1 ring-maroon-800' : 'border-light-gold hover:bg-slate-50'">
                            <input type="radio"
                                   name="payment_method"
                                   value="COD"
                                   x-model="form.payment_method"
                                   @change="recalculateQuote"
                                   class="mt-1 text-maroon-800 focus:ring-maroon-800">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 font-bold text-xs text-rich-black">
                                    <span>💵 Cash on Delivery (COD)</span>
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[0.625rem] font-semibold">Available</span>
                                </div>
                                <p class="text-[0.6875rem] text-rich-black/60">Pay in cash or UPI when your parcel arrives at your doorstep.</p>
                            </div>
                        </label>

                        <!-- Online Payment (Razorpay) -->
                        <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition-all"
                               :class="form.payment_method === 'ONLINE' ? 'border-maroon-800 bg-maroon-50/40 ring-1 ring-maroon-800' : 'border-light-gold hover:bg-slate-50'">
                            <input type="radio"
                                   name="payment_method"
                                   value="ONLINE"
                                   x-model="form.payment_method"
                                   @change="recalculateQuote"
                                   class="mt-1 text-maroon-800 focus:ring-maroon-800">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 font-bold text-xs text-rich-black">
                                    <span>⚡ Online Payment (UPI, Cards, NetBanking)</span>
                                </div>
                                <p class="text-[0.6875rem] text-rich-black/60">Instant verification via Razorpay • Google Pay, PhonePe, Paytm, Cards &amp; NetBanking.</p>
                                <div class="pt-2 flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded border border-light-gold/70 bg-white shadow-2xs">
                                        <img src="{{ asset('payments/upi.png') }}" alt="UPI" class="h-3 w-auto object-contain">
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded border border-light-gold/70 bg-white shadow-2xs">
                                        <img src="{{ asset('payments/google-pay.png') }}" alt="Google Pay" class="h-3.5 w-auto object-contain">
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded border border-light-gold/70 bg-white shadow-2xs">
                                        <img src="{{ asset('payments/phonepe.png') }}" alt="PhonePe" class="h-3 w-auto object-contain">
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded border border-light-gold/70 bg-white shadow-2xs">
                                        <img src="{{ asset('payments/paytm.png') }}" alt="Paytm" class="h-3 w-auto object-contain">
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded border border-light-gold/70 bg-white shadow-2xs">
                                        <img src="{{ asset('payments/visa.png') }}" alt="Visa" class="h-2.5 w-auto object-contain">
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded border border-light-gold/70 bg-white shadow-2xs">
                                        <img src="{{ asset('payments/mastercard.png') }}" alt="Mastercard" class="h-3.5 w-auto object-contain">
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded border border-light-gold/70 bg-white shadow-2xs">
                                        <img src="{{ asset('payments/rupay.png') }}" alt="RuPay" class="h-2.5 w-auto object-contain">
                                    </span>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Customer delivery note -->
                    <div class="pt-2">
                        <label for="customer_note" class="block font-semibold text-xs text-rich-black/70 mb-1">Order Notes (Optional)</label>
                        <textarea id="customer_note"
                                  x-model="form.customer_note"
                                  rows="2"
                                  placeholder="Special delivery instructions or gate code..."
                                  class="w-full px-3.5 py-2 rounded-lg border border-light-gold bg-ivory/30 text-xs text-rich-black focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none"></textarea>
                    </div>
                </div>

            </div>

            <!-- Right Column: Order Summary Sidebar -->
            <div class="lg:col-span-5 bg-white p-6 sm:p-8 rounded-2xl border border-light-gold shadow-md space-y-6 sticky top-28">
                <div class="flex items-center justify-between pb-3 border-b border-light-gold">
                    <h2 class="font-serif text-xl font-bold text-rich-black">Order Summary</h2>
                    <span class="text-xs text-rich-black/60 font-medium" x-text="quote.item_count + ' items'"></span>
                </div>

                <!-- Item thumbnails row -->
                <div class="max-h-60 overflow-y-auto space-y-3 pr-1 divide-y divide-light-gold/50">
                    <template x-for="item in quote.items" :key="item.variant.id">
                        <div class="pt-3 first:pt-0 flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-14 rounded-lg bg-warm-beige overflow-hidden shrink-0 border border-light-gold">
                                    <img :src="item.image_url || item.variant?.product?.primary_image_url || (item.variant?.product?.images && item.variant.product.images[0] ? item.variant.product.images[0].url : '')"
                                         class="w-full h-full object-cover"
                                         alt="">
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-semibold text-rich-black truncate" x-text="item.variant.product.name"></h4>
                                    <p class="text-[0.6875rem] text-rich-black/50" x-text="'Qty: ' + item.quantity"></p>
                                </div>
                            </div>
                            <span class="font-bold text-rich-black shrink-0" x-text="'₹' + (item.line_total_paise / 100).toFixed(0)"></span>
                        </div>
                    </template>
                </div>

                <!-- Coupon Input -->
                <div class="pt-4 border-t border-light-gold/60 space-y-2">
                    <div class="flex gap-2">
                        <input type="text"
                               x-model="form.coupon_code"
                               placeholder="Have a coupon? e.g. {{ $store->featured_coupon_code ?? 'FESTIVE10' }}"
                               class="flex-1 px-3 py-2 rounded-lg border border-light-gold bg-ivory/40 text-xs uppercase font-mono tracking-wider text-rich-black focus:border-maroon-800 outline-none">
                        <button type="button"
                                @click="recalculateQuote"
                                :disabled="calculating"
                                class="px-4 py-2 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors disabled:opacity-50">
                            Apply
                        </button>
                    </div>
                    <template x-if="couponMessage">
                        <p class="text-xs" :class="quote.discount_paise > 0 ? 'text-emerald-700 font-semibold' : 'text-rose-600'" x-text="couponMessage"></p>
                    </template>
                </div>

                <!-- Calculation details -->
                <div class="space-y-2.5 text-xs pt-4 border-t border-light-gold/60 divide-y divide-light-gold/40">
                    <div class="flex justify-between py-1 text-rich-black/70">
                        <span>Items Subtotal</span>
                        <strong class="text-rich-black" x-text="'₹' + quote.subtotal_rupees"></strong>
                    </div>

                    <template x-if="quote.discount_paise > 0">
                        <div class="flex justify-between py-1 text-emerald-700 font-semibold">
                            <span>Coupon Discount</span>
                            <span x-text="'−₹' + quote.discount_rupees"></span>
                        </div>
                    </template>

                    <div class="flex justify-between py-1 text-rich-black/70">
                        <span>Express Delivery</span>
                        <span class="font-bold" :class="quote.is_free_shipping ? 'text-emerald-700' : 'text-rich-black'"
                              x-text="quote.is_free_shipping ? 'FREE' : '₹' + quote.shipping_rupees"></span>
                    </div>

                    <div class="flex justify-between pt-3 text-base font-bold text-rich-black">
                        <span>Total Payable</span>
                        <span class="text-xl text-maroon-800" x-text="'₹' + quote.total_rupees"></span>
                    </div>
                </div>

                <p class="text-[0.6875rem] text-rich-black/50 text-center">
                    All prices inclusive of applicable GST taxes.
                </p>

                <!-- Place Order Button -->
                <button type="submit"
                        :disabled="submitting || calculating"
                        class="w-full py-4 rounded-xl bg-maroon-800 text-white text-xs font-bold uppercase tracking-wider hover:bg-maroon-900 transition-all shadow-md disabled:opacity-60 flex items-center justify-center gap-2">
                    <span x-show="!submitting">
                        <span x-text="form.payment_method === 'COD' ? 'Confirm Order (Cash on Delivery) ➔' : 'Proceed to Pay with Razorpay ➔'"></span>
                    </span>
                    <span x-show="submitting">Processing your order...</span>
                </button>

                <!-- Trust Badges & Supported Payment Marks -->
                <div class="pt-4 border-t border-light-gold/60 space-y-3 text-[0.6875rem] text-rich-black/60 text-center">
                    <p>🔒 256-Bit SSL Encrypted Checkout • 100% Authentic Quality Guaranteed</p>
                    <div class="flex flex-wrap items-center justify-center gap-2 pt-1 opacity-80">
                        <img src="{{ asset('payments/upi.png') }}" alt="UPI" class="h-3 w-auto object-contain">
                        <img src="{{ asset('payments/google-pay.png') }}" alt="Google Pay" class="h-3.5 w-auto object-contain">
                        <img src="{{ asset('payments/phonepe.png') }}" alt="PhonePe" class="h-3.5 w-auto object-contain">
                        <img src="{{ asset('payments/paytm.png') }}" alt="Paytm" class="h-3.5 w-auto object-contain">
                        <img src="{{ asset('payments/visa.png') }}" alt="Visa" class="h-2.5 w-auto object-contain">
                        <img src="{{ asset('payments/mastercard.png') }}" alt="Mastercard" class="h-3.5 w-auto object-contain">
                        <img src="{{ asset('payments/rupay.png') }}" alt="RuPay" class="h-2.5 w-auto object-contain">
                    </div>
                </div>

            </div>

        </div>
    </form>
</div>

<script>
    function checkoutApp() {
        return {
            submitting: false,
            calculating: false,
            errorMessage: '',
            couponMessage: '',

            form: {
                name: '',
                email: '',
                phone: '',
                line1: '',
                line2: '',
                landmark: '',
                city: '',
                state_code: '08',
                postal_code: '',
                payment_method: 'COD',
                coupon_code: '',
                customer_note: ''
            },

            quote: @json($quote),

            init() {
                if (typeof window.dhansreeTrack === 'function') {
                    window.dhansreeTrack('begin_checkout', {
                        value: this.quote ? (this.quote.total_paise / 100) : 0,
                        currency: 'INR'
                    });
                }
            },

            async recalculateQuote() {
                if (this.calculating) return;
                this.calculating = true;
                this.errorMessage = '';
                this.couponMessage = '';

                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch('{{ url('/checkout/quote') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            postal_code: this.form.postal_code,
                            state_code: this.form.state_code,
                            coupon_code: this.form.coupon_code,
                            payment_method: this.form.payment_method
                        })
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.quote = data.quote;
                        if (this.form.coupon_code && this.quote.discount_paise > 0) {
                            this.couponMessage = 'Coupon applied successfully!';
                        }
                    } else {
                        if (this.form.coupon_code) {
                            this.couponMessage = data.message || 'Invalid coupon code.';
                        }
                    }
                } catch (e) {
                    console.error('Quote recalculation failed', e);
                } finally {
                    this.calculating = false;
                }
            },

            async submitOrder() {
                if (this.submitting) return;
                this.submitting = true;
                this.errorMessage = '';

                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch('{{ url('/checkout/place') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify(this.form)
                    });

                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        this.errorMessage = data.message || 'Unable to place order. Please check your details.';
                        this.submitting = false;
                        return;
                    }

                    // Cash on Delivery -> Redirect directly to confirmation
                    if (!data.is_online) {
                        window.location.href = data.redirect_url;
                        return;
                    }

                    // Online Payment -> Trigger Razorpay Checkout Modal
                    if (data.is_online && typeof Razorpay !== 'undefined') {
                        const options = {
                            key: data.key_id,
                            amount: data.amount_paise,
                            currency: data.currency,
                            name: 'Dhansree Trends',
                            description: 'Order #' + data.order_number,
                            order_id: data.razorpay_order.id,
                            prefill: {
                                name: data.customer.name,
                                email: data.customer.email,
                                contact: data.customer.phone
                            },
                            theme: {
                                color: '#7B1C1C'
                            },
                            handler: async (response) => {
                                // Payment completed, verify on server
                                const verifyRes = await fetch('{{ url('/checkout/razorpay/verify') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': token
                                    },
                                    body: JSON.stringify({
                                        order_number: data.order_number,
                                        razorpay_order_id: response.razorpay_order_id,
                                        razorpay_payment_id: response.razorpay_payment_id,
                                        razorpay_signature: response.razorpay_signature
                                    })
                                });

                                const verifyData = await verifyRes.json();
                                if (verifyRes.ok && verifyData.success) {
                                    window.location.href = verifyData.redirect_url;
                                } else {
                                    alert('Payment verification failed. Please contact support with Payment ID: ' + response.razorpay_payment_id);
                                    window.location.href = data.redirect_url;
                                }
                            },
                            modal: {
                                ondismiss: () => {
                                    this.submitting = false;
                                    alert('Payment was cancelled. You can retry from your bag.');
                                }
                            }
                        };

                        const rzp = new Razorpay(options);
                        rzp.open();
                    } else {
                        // Fallback in test environment or if Razorpay script is not present
                        window.location.href = data.redirect_url;
                    }

                } catch (e) {
                    console.error('Order placement failed', e);
                    this.errorMessage = 'A network error occurred. Please try again.';
                    this.submitting = false;
                }
            }
        };
    }
</script>
@endsection
