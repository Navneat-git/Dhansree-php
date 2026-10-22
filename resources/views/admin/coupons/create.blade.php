@extends('layouts.admin')

@section('title', 'Create Coupon')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{
    type: '{{ old('type', 'PERCENTAGE') }}',
    isPercentage() { return this.type === 'PERCENTAGE'; }
}">
    <!-- Breadcrumb / Back -->
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.coupons.index') }}" class="p-2 rounded-lg bg-white border border-slate-300 text-slate-500 hover:text-slate-900 transition-colors shadow-2xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create New Coupon</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Define promotional discount code and redemption parameters.</p>
        </div>
    </div>

    <!-- Errors -->
    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm space-y-1">
            <div class="font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Please fix the following errors:
            </div>
            <ul class="list-disc list-inside text-xs text-rose-700 pl-4 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.coupons.store') }}" class="p-6 rounded-xl bg-white border border-slate-200/80 shadow-xs space-y-6">
        @csrf

        <!-- Code & Description -->
        <div class="space-y-4">
            <div>
                <label for="code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Coupon Code <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    value="{{ old('code') }}"
                    required
                    placeholder="e.g. WELCOME10, FESTIVE-500"
                    class="w-full px-4 py-2.5 text-sm font-mono uppercase bg-white border border-slate-300 rounded-lg text-amber-800 font-bold placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs"
                >
                <p class="text-[11px] text-slate-500 mt-1">Must contain only uppercase alphanumeric characters and hyphens. Code cannot be changed once created.</p>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Description (Optional)
                </label>
                <input
                    type="text"
                    id="description"
                    name="description"
                    value="{{ old('description') }}"
                    placeholder="e.g. 10% off on first order for new customers"
                    class="w-full px-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs"
                >
            </div>
        </div>

        <hr class="border-slate-200">

        <!-- Discount Parameters -->
        <div class="space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Discount Mechanics</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-xs font-semibold text-slate-700 mb-1">
                        Discount Type <span class="text-rose-500">*</span>
                    </label>
                    <select
                        id="type"
                        name="type"
                        x-model="type"
                        class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs"
                    >
                        <option value="PERCENTAGE">Percentage Discount (%)</option>
                        <option value="FIXED_AMOUNT">Fixed Amount Discount (₹)</option>
                    </select>
                </div>

                <div>
                    <label for="value" class="block text-xs font-semibold text-slate-700 mb-1">
                        <span x-text="isPercentage() ? 'Discount Percentage (%)' : 'Discount Amount (₹)'"></span> <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="any"
                            id="value"
                            name="value"
                            value="{{ old('value') }}"
                            required
                            placeholder="e.g. 10 for 10%, or 200 for ₹200"
                            class="w-full pl-8 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs" x-text="isPercentage() ? '%' : '₹'"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div x-show="isPercentage()" x-cloak>
                    <label for="max_discount_rupees" class="block text-xs font-semibold text-slate-700 mb-1">
                        Max Discount Cap (₹) (Optional)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="any"
                            id="max_discount_rupees"
                            name="max_discount_rupees"
                            value="{{ old('max_discount_rupees') }}"
                            placeholder="e.g. 500"
                            class="w-full pl-8 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₹</div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Limits maximum savings on large carts.</p>
                </div>

                <div>
                    <label for="min_order_rupees" class="block text-xs font-semibold text-slate-700 mb-1">
                        Minimum Cart Value (₹) (Optional)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="any"
                            id="min_order_rupees"
                            name="min_order_rupees"
                            value="{{ old('min_order_rupees') }}"
                            placeholder="e.g. 999"
                            class="w-full pl-8 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₹</div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Minimum subtotal before tax to qualify.</p>
                </div>
            </div>
        </div>

        <hr class="border-slate-200">

        <!-- Usage & Validity Limits -->
        <div class="space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Usage & Timing Limits</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="usage_limit" class="block text-xs font-semibold text-slate-700 mb-1">
                        Total Usage Limit (Optional)
                    </label>
                    <input
                        type="number"
                        min="1"
                        id="usage_limit"
                        name="usage_limit"
                        value="{{ old('usage_limit') }}"
                        placeholder="e.g. 100 uses across all customers"
                        class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-2xs"
                    >
                </div>

                <div>
                    <label for="per_customer_limit" class="block text-xs font-semibold text-slate-700 mb-1">
                        Per-Customer Usage Limit (Optional)
                    </label>
                    <input
                        type="number"
                        min="1"
                        id="per_customer_limit"
                        name="per_customer_limit"
                        value="{{ old('per_customer_limit') }}"
                        placeholder="e.g. 1 use per customer"
                        class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-2xs"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="starts_at" class="block text-xs font-semibold text-slate-700 mb-1">
                        Start Date & Time (Optional)
                    </label>
                    <input
                        type="datetime-local"
                        id="starts_at"
                        name="starts_at"
                        value="{{ old('starts_at') }}"
                        class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs"
                    >
                </div>

                <div>
                    <label for="expires_at" class="block text-xs font-semibold text-slate-700 mb-1">
                        Expiration Date & Time (Optional)
                    </label>
                    <input
                        type="datetime-local"
                        id="expires_at"
                        name="expires_at"
                        value="{{ old('expires_at') }}"
                        class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs"
                    >
                </div>
            </div>

            <div class="pt-2">
                <label class="relative flex items-center gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', '1') ? 'checked' : '' }}
                        class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 size-4"
                    >
                    <span class="text-sm font-semibold text-slate-800">Activate coupon immediately</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <a href="{{ route('admin.coupons.index') }}" class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                Cancel
            </a>
            <button
                type="submit"
                class="px-6 py-2.5 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-xs"
            >
                Create Coupon
            </button>
        </div>
    </form>
</div>
@endsection
