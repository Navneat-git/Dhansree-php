@extends('layouts.store')

@section('title', 'Create Account - Dhansree')

@section('content')
<div class="max-w-md mx-auto px-4 sm:px-6 py-12 md:py-20">
    <div class="bg-white rounded-3xl border border-light-gold shadow-sm p-6 sm:p-10 space-y-6">
        <!-- Header -->
        <div class="text-center space-y-2">
            <span class="inline-block px-3 py-1 rounded-full bg-gold-200/60 border border-gold-400 text-maroon-900 text-xs font-semibold uppercase tracking-wider">
                Festive Privileges
            </span>
            <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">
                Create Account
            </h1>
            <p class="text-xs sm:text-sm text-rich-black/70 leading-relaxed">
                Join Dhansree for instant order tracking, saved delivery addresses, and seamless shopping.
            </p>
        </div>

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('notice'))
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs sm:text-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('notice') }}</span>
            </div>
        @endif

        <!-- Quick Social Sign-Up -->
        <div class="space-y-2.5">
            <a href="{{ route('storefront.account.social', ['provider' => 'google', 'next' => $next]) }}"
               class="w-full py-2.5 px-4 rounded-xl border border-rich-black/15 hover:border-rich-black/30 bg-white hover:bg-neutral-50 text-rich-black font-medium text-xs sm:text-sm flex items-center justify-center gap-3 transition-colors shadow-2xs">
                <svg class="w-4 h-4" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Continue with Google</span>
            </a>

            <a href="{{ route('storefront.account.social', ['provider' => 'facebook', 'next' => $next]) }}"
               class="w-full py-2.5 px-4 rounded-xl border border-[#1877F2]/20 hover:border-[#1877F2]/40 bg-[#1877F2]/5 hover:bg-[#1877F2]/10 text-[#1877F2] font-medium text-xs sm:text-sm flex items-center justify-center gap-3 transition-colors shadow-2xs">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span>Continue with Facebook</span>
            </a>
        </div>

        <!-- Divider -->
        <div class="relative flex items-center justify-center">
            <div class="border-t border-light-gold/80 w-full"></div>
            <span class="bg-white px-3 text-[0.6875rem] font-semibold text-rich-black/40 uppercase tracking-widest">
                Or with details
            </span>
        </div>

        <form action="{{ route('storefront.account.register.submit') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="next" value="{{ $next }}">

            <!-- Full Name -->
            <div class="space-y-1">
                <label for="name" class="block text-xs font-semibold text-rich-black uppercase tracking-wider">
                    Full Name <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    placeholder="e.g. Priya Sharma"
                    class="w-full px-4 py-2.5 rounded-xl border border-rich-black/20 focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 text-sm outline-none transition-all placeholder:text-rich-black/30 @error('name') border-rose-500 @enderror"
                >
                @error('name')
                    <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div class="space-y-1">
                <label for="email" class="block text-xs font-semibold text-rich-black uppercase tracking-wider">
                    E-mail address <span class="text-rose-500">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="w-full px-4 py-2.5 rounded-xl border border-rich-black/20 focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 text-sm outline-none transition-all placeholder:text-rich-black/30 @error('email') border-rose-500 @enderror"
                >
                @error('email')
                    <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Mobile Phone -->
            <div class="space-y-1">
                <label for="phone" class="block text-xs font-semibold text-rich-black uppercase tracking-wider">
                    Mobile Number <span class="text-rich-black/40 font-normal">(Optional)</span>
                </label>
                <div class="flex items-center">
                    <span class="px-3 py-2.5 bg-neutral-100 border border-r-0 border-rich-black/20 rounded-l-xl text-rich-black/70 font-mono text-xs">+91</span>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="{{ old('phone') }}"
                        maxlength="10"
                        placeholder="9876543210"
                        class="w-full px-4 py-2.5 rounded-r-xl border border-rich-black/20 focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 text-sm outline-none transition-all placeholder:text-rich-black/30 font-mono @error('phone') border-rose-500 @enderror"
                    >
                </div>
                @error('phone')
                    <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full py-3.5 px-6 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white font-medium text-sm tracking-wide shadow-sm hover:shadow transition-all duration-200 flex items-center justify-center gap-2 group mt-2"
            >
                <span>Create Account</span>
                <svg class="w-4 h-4 text-gold-300 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <div class="pt-4 border-t border-light-gold/60 text-center space-y-2">
            <p class="text-xs text-rich-black/70">
                Already have an account?
                <a href="{{ route('storefront.account.login', ['next' => $next]) }}" class="font-semibold text-maroon-800 hover:text-maroon-900 underline underline-offset-4 ml-1">
                    Sign in here &rarr;
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
