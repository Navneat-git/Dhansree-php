@extends('layouts.admin')

@section('title', 'Coupons & Discounts')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Coupons & Discounts</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Manage promotional discount codes, usage limits, and checkout pricing rules.
            </p>
        </div>
        @if($canCreate)
            <div>
                <a
                    href="{{ route('admin.coupons.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm transition-colors shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Create Coupon</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Coupon Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Coupons</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Active Coupons</div>
            <div class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Expired Coupons</div>
            <div class="text-2xl font-bold text-slate-700 mt-1">{{ number_format($stats['expired']) }}</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs">
            <div class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Total Redemptions</div>
            <div class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($stats['redeemed_total']) }}</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <form method="GET" action="{{ route('admin.coupons.index') }}" class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs space-y-3 sm:space-y-0 sm:flex sm:items-center sm:gap-3">
        <div class="flex-1">
            <label for="search" class="sr-only">Search</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search by coupon code or description..."
                    class="w-full pl-9 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs"
                >
            </div>
        </div>

        <div class="w-full sm:w-44">
            <select name="state" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
                <option value="">All States</option>
                <option value="usable" {{ ($filters['state'] ?? '') === 'usable' ? 'selected' : '' }}>Usable Now</option>
                <option value="scheduled" {{ ($filters['state'] ?? '') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="expired" {{ ($filters['state'] ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="inactive" {{ ($filters['state'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-xs">
                Filter
            </button>
            @if(!empty($filters['search']) || !empty($filters['state']))
                <a href="{{ route('admin.coupons.index') }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-800 font-medium transition-colors">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Coupons Table -->
    <div class="rounded-xl bg-white border border-slate-200/80 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-600 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Code & Details</th>
                        <th class="px-6 py-4">Discount</th>
                        <th class="px-6 py-4">Minimum Cart</th>
                        <th class="px-6 py-4">Redemptions & Limits</th>
                        <th class="px-6 py-4">Validity</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($coupons as $coupon)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- Code & Details -->
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-amber-800 text-sm tracking-wide bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
                                        {{ $coupon->code }}
                                    </span>
                                </div>
                                @if($coupon->description)
                                    <div class="text-xs text-slate-600 mt-1 max-w-xs">
                                        {{ $coupon->description }}
                                    </div>
                                @endif
                                <div class="text-[11px] text-slate-400 mt-1">
                                    Created {{ $coupon->created_at->format('d M Y') }}
                                    @if($coupon->createdByAdminUser)
                                        by {{ $coupon->createdByAdminUser->name }}
                                    @endif
                                </div>
                            </td>

                            <!-- Discount -->
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                @if($coupon->type->value === 'PERCENTAGE')
                                    <div class="font-bold text-slate-900 text-sm">
                                        {{ rtrim(rtrim(number_format($coupon->value / 100, 2), '0'), '.') }}% OFF
                                    </div>
                                    @if($coupon->max_discount_paise)
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            Max ₹{{ number_format($coupon->max_discount_paise / 100, 0) }}
                                        </div>
                                    @endif
                                @else
                                    <div class="font-bold text-slate-900 text-sm">
                                        ₹{{ number_format($coupon->value / 100, 0) }} FLAT OFF
                                    </div>
                                @endif
                            </td>

                            <!-- Minimum Cart -->
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                @if($coupon->min_order_paise)
                                    <span class="text-slate-800 text-xs font-semibold">₹{{ number_format($coupon->min_order_paise / 100, 0) }}</span>
                                @else
                                    <span class="text-slate-400 text-xs">No minimum</span>
                                @endif
                            </td>

                            <!-- Redemptions & Limits -->
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                <div class="text-xs font-bold text-slate-900">
                                    {{ number_format($coupon->times_redeemed) }}
                                    @if($coupon->usage_limit)
                                        <span class="text-slate-500 font-normal">/ {{ number_format($coupon->usage_limit) }} total</span>
                                    @else
                                        <span class="text-slate-400 font-normal">uses (Unlimited)</span>
                                    @endif
                                </div>
                                @if($coupon->per_customer_limit)
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        Limit: {{ $coupon->per_customer_limit }} per customer
                                    </div>
                                @endif
                            </td>

                            <!-- Validity -->
                            <td class="px-6 py-4 align-top text-xs text-slate-600 whitespace-nowrap">
                                @if($coupon->starts_at)
                                    <div>From: {{ $coupon->starts_at->format('d M Y, H:i') }}</div>
                                @endif
                                @if($coupon->expires_at)
                                    <div class="{{ $coupon->expires_at->isPast() ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                                        Expires: {{ $coupon->expires_at->format('d M Y, H:i') }}
                                    </div>
                                @elseif(!$coupon->starts_at)
                                    <span class="text-slate-400">Always active</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 align-top whitespace-nowrap">
                                @php
                                    $isExpired = $coupon->expires_at && $coupon->expires_at->isPast();
                                    $isScheduled = $coupon->starts_at && $coupon->starts_at->isFuture();
                                    $isExhausted = $coupon->usage_limit && $coupon->times_redeemed >= $coupon->usage_limit;
                                @endphp

                                @if(!$coupon->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Inactive
                                    </span>
                                @elseif($isExpired)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Expired
                                    </span>
                                @elseif($isExhausted)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">
                                        Exhausted
                                    </span>
                                @elseif($isScheduled)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                        Scheduled
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 align-top text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @if($canUpdate)
                                        <form method="POST" action="{{ route('admin.coupons.toggle', $coupon->id) }}" class="inline">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="px-2.5 py-1 text-xs font-semibold rounded-lg border transition-colors {{ $coupon->is_active ? 'bg-white hover:bg-slate-50 text-slate-700 border-slate-300 shadow-2xs' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-300' }}"
                                                title="{{ $coupon->is_active ? 'Deactivate coupon' : 'Activate coupon' }}"
                                            >
                                                {{ $coupon->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <a
                                            href="{{ route('admin.coupons.edit', $coupon->id) }}"
                                            class="px-2.5 py-1 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg transition-colors shadow-2xs"
                                        >
                                            Edit
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                                <p class="text-sm font-semibold text-slate-700">No coupons found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paginator->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $paginator->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
