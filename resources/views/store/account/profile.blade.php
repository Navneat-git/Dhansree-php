@extends('layouts.store')

@section('title', 'My Profile - Dhansree')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16 space-y-6">
    <!-- Header -->
    <div>
        <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">
            Personal Profile
        </h1>
        <p class="text-xs sm:text-sm text-rich-black/60 mt-1">
            Update your personal contact details used across your shopping receipts and notifications.
        </p>
    </div>

    @include('store.account.partials.nav')

    <!-- Flash alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm space-y-1">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-3xl bg-white border border-light-gold shadow-xs p-6 sm:p-10 space-y-6">
        <form action="{{ route('storefront.account.profile.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Email (Immutable) -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-rich-black uppercase tracking-wider">
                        E-mail Address
                    </label>
                    <span class="inline-flex items-center gap-1 text-[0.6875rem] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Verified Account ID</span>
                    </span>
                </div>
                <input
                    type="email"
                    value="{{ $customer->email }}"
                    disabled
                    class="w-full px-4 py-3 rounded-xl border border-rich-black/10 bg-cream/50 text-rich-black/70 text-sm cursor-not-allowed font-medium"
                >
                <p class="text-[0.6875rem] text-rich-black/50">
                    Your email address is your permanent store identity where login verification codes are dispatched.
                </p>
            </div>

            <!-- Full Name -->
            <div class="space-y-1.5">
                <label for="name" class="block text-xs font-semibold text-rich-black uppercase tracking-wider">
                    Full Name <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $customer->name) }}"
                    required
                    maxlength="100"
                    placeholder="Enter your name"
                    class="w-full px-4 py-3 rounded-xl border border-rich-black/20 focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 text-sm outline-none transition-all"
                >
            </div>

            <!-- Primary Phone -->
            <div class="space-y-1.5">
                <label for="phone" class="block text-xs font-semibold text-rich-black uppercase tracking-wider">
                    Primary Mobile Number (+91)
                </label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="{{ old('phone', $customer->phone) }}"
                    placeholder="+919876543210"
                    class="w-full px-4 py-3 rounded-xl border border-rich-black/20 focus:border-maroon-700 focus:ring-1 focus:ring-maroon-700 text-sm outline-none transition-all font-mono"
                >
                <p class="text-[0.6875rem] text-rich-black/50">Used for courier delivery SMS & tracking calls.</p>
            </div>

            <div class="pt-4 border-t border-light-gold/60 flex items-center justify-between">
                <span class="text-xs text-rich-black/50">
                    Member since {{ $customer->created_at?->format('d M Y') }}
                </span>
                <button
                    type="submit"
                    class="px-6 py-3 rounded-xl bg-maroon-700 hover:bg-maroon-800 text-white font-medium text-xs tracking-wide shadow-xs hover:shadow transition-all"
                >
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
