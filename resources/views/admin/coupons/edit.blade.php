@extends('layouts.admin')

@section('title', 'Edit Coupon: ' . $coupon->code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="{
    type: '{{ old('type', $coupon->type->value) }}',
    isPercentage() { return this.type === 'PERCENTAGE'; }
}">
    <!-- Breadcrumb / Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.coupons.index') }}" class="p-2 rounded-lg bg-white border border-slate-300 text-slate-500 hover:text-slate-900 transition-colors shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 font-mono text-amber-800">{{ $coupon->code }}</h1>
                    @php
                        $isExpired = $coupon->expires_at && $coupon->expires_at->isPast();
                        $isScheduled = $coupon->starts_at && $coupon->starts_at->isFuture();
                        $isExhausted = $coupon->usage_limit && $coupon->times_redeemed >= $coupon->usage_limit;
                    @endphp

                    @if(!$coupon->is_active)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            Inactive
                        </span>
                    @elseif($isExpired)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                            Expired
                        </span>
                    @elseif($isExhausted)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">
                            Exhausted
                        </span>
                    @elseif($isScheduled)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Scheduled
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Created {{ $coupon->created_at->format('d M Y, H:i') }}
                    @if($coupon->createdByAdminUser)
                        by {{ $coupon->createdByAdminUser->name }}
                    @endif
                    • {{ number_format($coupon->times_redeemed) }} redemptions
                </p>
            </div>
        </div>

        @if($canDelete && $isDeletable)
            <div>
                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon->id) }}" onsubmit="return confirm('Are you sure you want to permanently delete this unused coupon?');">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-300 rounded-lg transition-colors shadow-2xs"
                    >
                        Delete Coupon
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Scheduled Coupon Banner -->
    @if($isScheduled)
        <div class="p-4 rounded-xl bg-sky-50 border border-sky-200 text-sky-900 text-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-sky-100 flex items-center justify-center text-sky-700 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="font-bold text-sky-950">This coupon is currently Scheduled</p>
                    <p class="text-xs text-sky-700 mt-0.5">
                        Scheduled to start on <span class="font-semibold">{{ $coupon->starts_at->timezone(config('store.timezone', 'Asia/Kolkata'))->format('d M Y, h:i A') }} (IST)</span>. Customers cannot use it at checkout until then.
                    </p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.coupons.start-now', $coupon->id) }}" class="shrink-0">
                @csrf
                <button
                    type="submit"
                    class="w-full sm:w-auto px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-2xs transition-colors flex items-center justify-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Activate Coupon Now
                </button>
            </form>
        </div>
    @endif

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

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

    <form method="POST" action="{{ route('admin.coupons.update', $coupon->id) }}" class="p-6 rounded-xl bg-white border border-slate-200/80 shadow-xs space-y-6">
        @csrf
        @method('PUT')

        <!-- Code Notice & Description -->
        <div class="space-y-4">
            <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center justify-between">
                <div>
                    <span class="text-slate-500 uppercase tracking-wider font-bold">Coupon Code:</span>
                    <span class="ml-2 font-mono font-bold text-amber-800 text-sm">{{ $coupon->code }}</span>
                </div>
                <span class="text-[11px] text-slate-400 italic">Code is permanent and cannot be renamed</span>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                    Description
                </label>
                <input
                    type="text"
                    id="description"
                    name="description"
                    value="{{ old('description', $coupon->description) }}"
                    placeholder="e.g. 10% off for festival sale"
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
                            value="{{ old('value', $coupon->value / 100) }}"
                            required
                            class="w-full pl-8 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs"
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
                            value="{{ old('max_discount_rupees', $coupon->max_discount_paise ? $coupon->max_discount_paise / 100 : '') }}"
                            placeholder="e.g. 500"
                            class="w-full pl-8 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-2xs"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₹</div>
                    </div>
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
                            value="{{ old('min_order_rupees', $coupon->min_order_paise ? $coupon->min_order_paise / 100 : '') }}"
                            placeholder="e.g. 999"
                            class="w-full pl-8 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-2xs"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₹</div>
                    </div>
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
                        value="{{ old('usage_limit', $coupon->usage_limit) }}"
                        placeholder="e.g. 100"
                        class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-2xs"
                    >
                    <p class="text-[11px] text-slate-500 mt-1">Already redeemed {{ $coupon->times_redeemed }} times.</p>
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
                        value="{{ old('per_customer_limit', $coupon->per_customer_limit) }}"
                        placeholder="e.g. 1"
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
                        value="{{ old('starts_at', $coupon->starts_at ? $coupon->starts_at->timezone(config('store.timezone', 'Asia/Kolkata'))->format('Y-m-d\TH:i') : '') }}"
                        class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs"
                    >
                    <p class="text-[11px] text-slate-500 mt-1">Leave empty to activate immediately. All times in Indian Standard Time (IST).</p>
                </div>

                <div>
                    <label for="expires_at" class="block text-xs font-semibold text-slate-700 mb-1">
                        Expiration Date & Time (Optional)
                    </label>
                    <input
                        type="datetime-local"
                        id="expires_at"
                        name="expires_at"
                        value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->timezone(config('store.timezone', 'Asia/Kolkata'))->format('Y-m-d\TH:i') : '') }}"
                        class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs"
                    >
                    <p class="text-[11px] text-slate-500 mt-1">Leave empty for no expiration. Times in Indian Standard Time (IST).</p>
                </div>
            </div>

            <div class="pt-2">
                <label class="relative flex items-center gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}
                        class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 size-4"
                    >
                    <span class="text-sm font-semibold text-slate-800">Active and available for checkout</span>
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
                Save Changes
            </button>
        </div>
    </form>

    <!-- Recent Redemptions Preview -->
    <div class="rounded-xl bg-white border border-slate-200/80 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Redemption History</h3>
                <p class="text-xs text-slate-500 mt-0.5">Recent orders that used this coupon code.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-200 text-slate-700">
                Total: {{ number_format($coupon->times_redeemed) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-slate-600 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3">Order Number</th>
                        <th class="px-6 py-3">Customer / Email</th>
                        <th class="px-6 py-3">Order Total</th>
                        <th class="px-6 py-3">Discount Applied</th>
                        <th class="px-6 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($coupon->couponRedemptions as $redemption)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-6 py-3 font-mono font-bold text-amber-700">
                                @if($redemption->order)
                                    <a href="{{ url('/admin/orders') }}?search={{ $redemption->order->order_number }}" class="hover:underline">
                                        {{ $redemption->order->order_number }}
                                    </a>
                                @else
                                    #{{ $redemption->order_id }}
                                @endif
                            </td>
                            <td class="px-6 py-3 text-slate-800">
                                {{ $redemption->email }}
                            </td>
                            <td class="px-6 py-3 font-semibold text-slate-900">
                                ₹{{ number_format(($redemption->order?->total_paise ?? 0) / 100, 2) }}
                            </td>
                            <td class="px-6 py-3 font-bold text-emerald-700">
                                -₹{{ number_format($redemption->discount_paise / 100, 2) }}
                            </td>
                            <td class="px-6 py-3 text-slate-400">
                                {{ $redemption->created_at->format('d M Y, H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                This coupon has not been used on any orders yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
