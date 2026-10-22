@extends('layouts.store')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
    <!-- Header -->
    <div class="text-center space-y-3 mb-10">
        <span class="inline-block px-3 py-1 rounded-full bg-gold-200/60 border border-gold-400 text-maroon-900 text-xs font-semibold uppercase tracking-wider">
            Live Shipment Tracker
        </span>
        <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl text-maroon-800 font-normal">
            Track Your Order
        </h1>
        <p class="text-xs sm:text-sm text-rich-black/70 max-w-lg mx-auto leading-relaxed">
            Enter your Order ID and registered 10-digit mobile number to view real-time shipping status and delivery updates.
        </p>
    </div>

    <!-- Lookup Form Card -->
    <div class="bg-white rounded-3xl border border-light-gold shadow-sm p-6 sm:p-10 space-y-6">
        @if(session('info'))
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs sm:text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('store.track-order.lookup') }}" class="space-y-6">
            @csrf

            <!-- Order Number Field -->
            <div class="space-y-2">
                <label for="order_number" class="block text-xs font-bold uppercase tracking-wider text-rich-black/80">
                    Order ID <span class="text-rose-600">*</span>
                </label>
                <div class="relative">
                    <input type="text"
                           id="order_number"
                           name="order_number"
                           required
                           value="{{ old('order_number', $prefilledOrder) }}"
                           placeholder="e.g. DS-2ABC4EFG"
                           class="w-full px-4 py-3.5 rounded-xl border {{ $errors->has('order_number') ? 'border-rose-400 focus:ring-rose-400' : 'border-light-gold focus:border-maroon-800 focus:ring-maroon-800/20' }} font-mono text-sm sm:text-base uppercase tracking-wider text-rich-black bg-ivory/40 focus:outline-none focus:ring-2">
                </div>
                @error('order_number')
                    <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
                <p class="text-[11px] text-rich-black/50">Found on your order confirmation SMS, email, or invoice.</p>
            </div>

            <!-- Phone Number Field -->
            <div class="space-y-2">
                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-rich-black/80">
                    Registered Mobile Number <span class="text-rose-600">*</span>
                </label>
                <div class="relative flex items-center">
                    <span class="absolute left-4 text-xs font-semibold text-rich-black/50 select-none">+91</span>
                    <input type="tel"
                           id="phone"
                           name="phone"
                           required
                           maxlength="10"
                           value="{{ old('phone', $prefilledPhone) }}"
                           placeholder="9876543210"
                           class="w-full pl-14 pr-4 py-3.5 rounded-xl border {{ $errors->has('phone') ? 'border-rose-400 focus:ring-rose-400' : 'border-light-gold focus:border-maroon-800 focus:ring-maroon-800/20' }} text-sm sm:text-base font-medium tracking-wide text-rich-black bg-ivory/40 focus:outline-none focus:ring-2">
                </div>
                @error('phone')
                    <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
                <p class="text-[11px] text-rich-black/50">The 10-digit phone number provided during checkout.</p>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit"
                        class="w-full py-4 rounded-xl bg-gradient-to-r from-maroon-800 to-maroon-900 hover:from-maroon-700 hover:to-maroon-800 text-white font-semibold text-sm sm:text-base uppercase tracking-wider transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Track Shipment
                </button>
            </div>
        </form>

        <!-- Information / Trust Highlights -->
        <div class="pt-8 border-t border-light-gold/60 grid grid-cols-1 sm:grid-cols-3 gap-6 text-center text-xs">
            <div class="space-y-1">
                <span class="inline-block p-2 rounded-full bg-warm-beige/60 text-maroon-800 font-bold">🚚</span>
                <h4 class="font-bold text-rich-black">Fast Courier Partners</h4>
                <p class="text-rich-black/60 text-[11px]">Bluedart, Delhivery, DTDC & Shadowfax express delivery.</p>
            </div>
            <div class="space-y-1">
                <span class="inline-block p-2 rounded-full bg-warm-beige/60 text-maroon-800 font-bold">📲</span>
                <h4 class="font-bold text-rich-black">Live SMS Updates</h4>
                <p class="text-rich-black/60 text-[11px]">Real-time milestone notifications at every transit step.</p>
            </div>
            <div class="space-y-1">
                <span class="inline-block p-2 rounded-full bg-warm-beige/60 text-maroon-800 font-bold">🛡️</span>
                <h4 class="font-bold text-rich-black">100% Insured Transit</h4>
                <p class="text-rich-black/60 text-[11px]">Tamper-proof sealed packaging guaranteed.</p>
            </div>
        </div>
    </div>
</div>
@endsection
