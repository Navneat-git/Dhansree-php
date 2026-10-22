@extends('layouts.store')

@section('title', 'Sign In - Dhansree')

@section('content')
<div class="max-w-md mx-auto px-4 sm:px-6 py-12 md:py-20">
    <div class="bg-white rounded-3xl border border-light-gold shadow-sm p-6 sm:p-10 space-y-6">
        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="inline-block px-3 py-1 rounded-full bg-gold-200/60 border border-gold-400 text-maroon-900 text-xs font-semibold uppercase tracking-wider">
                Passwordless Access
            </span>
            <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">
                Sign in
            </h1>
            <p class="text-xs sm:text-sm text-rich-black/70 leading-relaxed">
                Enter your e-mail address and we will send you a code. There is no password to remember.
            </p>
        </div>

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('notice'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('notice') }}</span>
            </div>
        @endif

        <form action="{{ route('storefront.account.send-otp') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="next" value="{{ $next }}">

            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-semibold text-rich-black uppercase tracking-wider">
                    E-mail address <span class="text-rose-500">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="w-full px-4 py-3 rounded-xl border border-rich-black/20 focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 text-sm outline-none transition-all placeholder:text-rich-black/30 @error('email') border-rose-500 @enderror"
                >
                @error('email')
                    <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
                <p class="text-[0.7rem] text-rich-black/50">We will send your 6-digit sign-in code here.</p>
            </div>

            <button
                type="submit"
                class="w-full py-3.5 px-6 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white font-medium text-sm tracking-wide shadow-sm hover:shadow transition-all duration-200 flex items-center justify-center gap-2 group"
            >
                <span>Send me a code</span>
                <svg class="w-4 h-4 text-gold-300 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <div class="pt-4 border-t border-light-gold/60 text-center space-y-3">
            <p class="text-xs text-rich-black/60 leading-relaxed">
                You do not need an account to buy anything. Signing in just keeps your orders and addresses in one place.
            </p>
            <div>
                <a href="{{ url('/shop') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-maroon-800 hover:text-maroon-900 underline underline-offset-4">
                    Continue shopping as guest &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
