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
            <a href="{{ route('admin.coupons.index') }}" class="p-2 rounded-xl bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-white font-mono text-amber-400">{{ $coupon->code }}</h1>
                    @if($coupon->is_active)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/70 text-emerald-300 border border-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Active
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-400 border border-zinc-700">
                            Inactive
                        </span>
                    @endif
                </div>
                <p class="text-xs text-zinc-400 mt-1">
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
                        class="px-4 py-2 text-xs font-semibold text-rose-300 bg-rose-950/60 hover:bg-rose-900/60 border border-rose-800 rounded-xl transition-colors"
                    >
                        Delete Coupon
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-800/80 text-emerald-200 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Errors -->
    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-800/80 text-rose-200 text-sm space-y-1">
            <div class="font-semibold flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Please fix the following errors:
            </div>
            <ul class="list-disc list-inside text-xs text-rose-300 pl-4 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.coupons.update', $coupon->id) }}" class="p-6 rounded-2xl bg-zinc-900/80 border border-zinc-800/80 space-y-6">
        @csrf
        @method('PUT')

        <!-- Code Notice & Description -->
        <div class="space-y-4">
            <div class="p-3.5 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-400 flex items-center justify-between">
                <div>
                    <span class="text-zinc-500 uppercase tracking-wider font-semibold">Coupon Code:</span>
                    <span class="ml-2 font-mono font-bold text-amber-400 text-sm">{{ $coupon->code }}</span>
                </div>
                <span class="text-[11px] text-zinc-500 italic">Code is permanent and cannot be renamed</span>
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-1">
                    Description
                </label>
                <input
                    type="text"
                    id="description"
                    name="description"
                    value="{{ old('description', $coupon->description) }}"
                    placeholder="e.g. 10% off for festival sale"
                    class="w-full px-4 py-2 text-sm bg-zinc-950 border border-zinc-700/80 rounded-xl text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-amber-500"
                >
            </div>
        </div>

        <hr class="border-zinc-800">

        <!-- Discount Parameters -->
        <div class="space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Discount Mechanics</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-xs font-semibold text-zinc-400 mb-1">
                        Discount Type <span class="text-rose-400">*</span>
                    </label>
                    <select
                        id="type"
                        name="type"
                        x-model="type"
                        class="w-full px-3 py-2 text-sm bg-zinc-950 border border-zinc-700/80 rounded-xl text-zinc-200 focus:outline-none focus:border-amber-500"
                    >
                        <option value="PERCENTAGE">Percentage Discount (%)</option>
                        <option value="FIXED_AMOUNT">Fixed Amount Discount (₹)</option>
                    </select>
                </div>

                <div>
                    <label for="value" class="block text-xs font-semibold text-zinc-400 mb-1">
                        <span x-text="isPercentage() ? 'Discount Percentage (%)' : 'Discount Amount (₹)'"></span> <span class="text-rose-400">*</span>
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            step="any"
                            id="value"
                            name="value"
                            value="{{ old('value', $coupon->value / 100) }}"
                            required
                            class="w-full pl-8 pr-4 py-2 text-sm bg-zinc-950 border border-zinc-700/80 rounded-xl text-zinc-200 focus:outline-none focus:border-amber-500"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-500 font-bold text-xs" x-text="isPercentage() ? '%' : '₹'"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div x-show="isPercentage()" x-cloak>
                    <label for="max_discount_rupees" class="block text-xs font-semibold text-zinc-400 mb-1">
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
                            class="w-full pl-8 pr-4 py-2 text-sm bg-zinc-950 border border-zinc-700/80 rounded-xl text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-amber-500"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-500 font-bold text-xs">₹</div>
                    </div>
                </div>

                <div>
                    <label for="min_order_rupees" class="block text-xs font-semibold text-zinc-400 mb-1">
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
                            class="w-full pl-8 pr-4 py-2 text-sm bg-zinc-950 border border-zinc-700/80 rounded-xl text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-amber-500"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-500 font-bold text-xs">₹</div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="border-zinc-800">

        <!-- Usage & Validity Limits -->
        <div class="space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Usage & Timing Limits</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="usage_limit" class="block text-xs font-semibold text-zinc-400 mb-1">
                        Total Usage Limit (Optional)
                    </label>
                    <input
                        type="number"
                        min="1"
                        id="usage_limit"
                        name="usage_limit"
                        value="{{ old('usage_limit', $coupon->usage_limit) }}"
                        placeholder="e.g. 100"
                        class="w-full px-3 py-2 text-sm bg-zinc-950 border border-zinc-700/80 rounded-xl text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-amber-500"
                    >
                    <p class="text-[11px] text-zinc-500 mt-1">Already redeemed {{ $coupon->times_redeemed }} times.</p>
                </div>

                <div>
                    <label for="per_customer_limit" class="block text-xs font-semibold text-zinc-400 mb-1">
                        Per-Customer Usage Limit (Optional)
                    </label>
                    <input
                        type="number"
                        min="1"
                        id="per_customer_limit"
                        name="per_customer_limit"
                        value="{{ old('per_customer_limit', $coupon->per_customer_limit) }}"
                        placeholder="e.g. 1"
                        class="w-full px-3 py-2 text-sm bg-zinc-950 border border-zinc-700/80 rounded-xl text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-amber-500"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="starts_at" class="block text-xs font-semibold text-zinc-400 mb-1">
                        Start Date & Time (Optional)
                    </label>
                    <input
                        type="datetime-local"
                        id="starts_at"
                        name="starts_at"
                        value="{{ old('starts_at', $coupon->starts_at ? $coupon->starts_at->format('Y-m-d\TH:i') : '') }}"
                        class="w-full px-3 py-2 text-sm bg-zinc-950 border border-zinc-700/80 rounded-xl text-zinc-200 focus:outline-none focus:border-amber-500"
                    >
                </div>

                <div>
                    <label for="expires_at" class="block text-xs font-semibold text-zinc-400 mb-1">
                        Expiration Date & Time (Optional)
                    </label>
                    <input
                        type="datetime-local"
                        id="expires_at"
                        name="expires_at"
                        value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}"
                        class="w-full px-3 py-2 text-sm bg-zinc-950 border border-zinc-700/80 rounded-xl text-zinc-200 focus:outline-none focus:border-amber-500"
                    >
                </div>
            </div>

            <div class="pt-2">
                <label class="relative flex items-center gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}
                        class="sr-only peer"
                    >
                    <div class="w-11 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                    <span class="text-sm font-medium text-zinc-200">Active and available for checkout</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-800">
            <a href="{{ route('admin.coupons.index') }}" class="px-4 py-2.5 text-sm font-semibold text-zinc-400 hover:text-white transition-colors">
                Cancel
            </a>
            <button
                type="submit"
                class="px-6 py-2.5 text-sm font-semibold text-zinc-950 bg-amber-400 hover:bg-amber-300 rounded-xl transition-colors shadow-lg shadow-amber-400/20"
            >
                Save Changes
            </button>
        </div>
    </form>

    <!-- Recent Redemptions Preview -->
    <div class="rounded-2xl bg-zinc-900/80 border border-zinc-800/80 overflow-hidden">
        <div class="p-5 border-b border-zinc-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Redemption History</h3>
                <p class="text-xs text-zinc-400 mt-0.5">Recent orders that used this coupon code.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-zinc-800 text-zinc-300 border border-zinc-700">
                Total: {{ number_format($coupon->times_redeemed) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-zinc-300">
                <thead class="bg-zinc-950/60 text-zinc-400 uppercase font-semibold border-b border-zinc-800">
                    <tr>
                        <th class="px-6 py-3">Order Number</th>
                        <th class="px-6 py-3">Customer / Email</th>
                        <th class="px-6 py-3">Order Total</th>
                        <th class="px-6 py-3">Discount Applied</th>
                        <th class="px-6 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60">
                    @forelse($coupon->couponRedemptions as $redemption)
                        <tr class="hover:bg-zinc-800/30">
                            <td class="px-6 py-3 font-mono font-medium text-amber-400">
                                @if($redemption->order)
                                    <a href="{{ url('/admin/orders') }}?search={{ $redemption->order->order_number }}" class="hover:underline">
                                        {{ $redemption->order->order_number }}
                                    </a>
                                @else
                                    #{{ $redemption->order_id }}
                                @endif
                            </td>
                            <td class="px-6 py-3 text-zinc-300">
                                {{ $redemption->email }}
                            </td>
                            <td class="px-6 py-3 font-medium text-white">
                                ₹{{ number_format(($redemption->order?->total_paise ?? 0) / 100, 2) }}
                            </td>
                            <td class="px-6 py-3 font-semibold text-emerald-400">
                                -₹{{ number_format($redemption->discount_paise / 100, 2) }}
                            </td>
                            <td class="px-6 py-3 text-zinc-500">
                                {{ $redemption->created_at->format('d M Y, H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-zinc-500">
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
