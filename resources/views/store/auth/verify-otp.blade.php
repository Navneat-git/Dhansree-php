@extends('layouts.store')

@section('title', 'Verify Code - Dhansree')

@section('content')
<div class="max-w-md mx-auto px-4 sm:px-6 py-12 md:py-20">
    <div class="bg-white rounded-3xl border border-light-gold shadow-sm p-6 sm:p-10 space-y-6">
        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="inline-block px-3 py-1 rounded-full bg-gold-200/60 border border-gold-400 text-maroon-900 text-xs font-semibold uppercase tracking-wider">
                Step 2 of 2
            </span>
            <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">
                Enter code
            </h1>
            <p class="text-xs sm:text-sm text-rich-black/70 leading-relaxed">
                We sent a 6-digit code to <strong class="text-rich-black">{{ $email }}</strong>
            </p>
        </div>

        @if(session('notice'))
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-950 text-xs sm:text-sm flex items-start gap-3 shadow-2xs">
                <svg class="w-5 h-5 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="space-y-1">
                    <p class="font-medium leading-relaxed">{{ session('notice') }}</p>
                    <p class="text-[0.6875rem] text-amber-800/80">No password required. Enter your 6-digit email code below to access your account securely.</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('storefront.account.verify.submit') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="next" value="{{ $next }}">

            <div class="space-y-1.5">
                <label for="code" class="block text-xs font-semibold text-rich-black uppercase tracking-wider text-center">
                    6-Digit Verification Code
                </label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                    autofocus
                    placeholder="000000"
                    class="w-full text-center tracking-[0.5em] font-mono text-2xl font-bold py-3.5 px-4 rounded-xl border border-rich-black/20 focus:border-maroon-700 focus:ring-2 focus:ring-maroon-700/20 outline-none transition-all placeholder:text-rich-black/20 @error('code') border-rose-500 @enderror"
                >
                @error('code')
                    <p class="text-xs text-rose-600 font-medium text-center">{{ $message }}</p>
                @enderror
                <p class="text-[0.7rem] text-rich-black/50 text-center">Code expires in 10 minutes. 5 attempts maximum.</p>
            </div>

            <button
                type="submit"
                class="w-full py-3.5 px-6 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white font-medium text-sm tracking-wide shadow-sm hover:shadow transition-all duration-200 flex items-center justify-center gap-2 group"
            >
                <span>Sign in to Dhansree</span>
                <svg class="w-4 h-4 text-gold-300 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <div class="pt-4 border-t border-light-gold/60 flex flex-wrap items-center justify-between gap-3 text-xs">
            <form action="{{ route('storefront.account.send-otp') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <input type="hidden" name="next" value="{{ $next }}">
                <button type="submit" class="font-semibold text-maroon-800 hover:text-maroon-900 underline underline-offset-4">
                    Send another code
                </button>
            </form>
            <a href="{{ route('storefront.account.login', ['next' => $next]) }}" class="text-rich-black/60 hover:text-rich-black">
                Use a different email
            </a>
        </div>
    </div>
</div>
@endsection
