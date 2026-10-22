@extends('layouts.admin')

@section('title', 'Payments & Transactions')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Payments & Transactions</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Payment transactions, Razorpay settlements, and COD collections.</p>
        </div>
    </div>

    <!-- Metrics Strip -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Collected Volume</span>
            <p class="text-xl sm:text-2xl font-bold text-slate-900 mt-1 font-mono">₹{{ number_format($metrics['total_volume'] / 100, 2) }}</p>
            <p class="text-[11px] text-emerald-600 font-semibold mt-1">{{ $metrics['paid_count'] }} successful payments</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending COD</span>
            <p class="text-xl sm:text-2xl font-bold text-slate-900 mt-1 font-mono">₹{{ number_format($metrics['pending_volume'] / 100, 2) }}</p>
            <p class="text-[11px] text-amber-600 font-semibold mt-1">{{ $metrics['pending_count'] }} awaiting collection</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Failed Attempts</span>
            <p class="text-xl sm:text-2xl font-bold text-rose-600 mt-1 font-mono">{{ $metrics['failed_count'] }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Declined or aborted</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Refunded Volume</span>
            <p class="text-xl sm:text-2xl font-bold text-slate-700 mt-1 font-mono">₹{{ number_format($metrics['refunded_volume'] / 100, 2) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Returned to customers</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Search -->
            <div class="flex-1 relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by Order #, Customer, Phone, or Payment ID..."
                       class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-amber-500">
                <svg class="size-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <!-- Status Filter -->
            <select name="status" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:bg-white focus:border-amber-500">
                <option value="">All Payment Statuses</option>
                <option value="PAID" {{ $currentStatus === 'PAID' ? 'selected' : '' }}>Paid / Captured</option>
                <option value="PENDING" {{ $currentStatus === 'PENDING' ? 'selected' : '' }}>Pending</option>
                <option value="FAILED" {{ $currentStatus === 'FAILED' ? 'selected' : '' }}>Failed</option>
                <option value="REFUNDED" {{ $currentStatus === 'REFUNDED' ? 'selected' : '' }}>Refunded</option>
            </select>

            <!-- Provider Filter -->
            <select name="provider" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:bg-white focus:border-amber-500">
                <option value="">All Providers</option>
                <option value="razorpay" {{ $currentProvider === 'razorpay' ? 'selected' : '' }}>Razorpay</option>
                <option value="cod" {{ $currentProvider === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-semibold uppercase tracking-wider transition-colors shadow-2xs">
                Filter
            </button>

            @if($currentStatus || $currentProvider || $search)
                <a href="{{ route('admin.payments.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-xs font-medium text-center transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Payments Table -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Payment ID / Date</th>
                        <th class="py-3 px-4">Order Details</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4">Method / Provider</th>
                        <th class="py-3 px-4">Amount</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 font-mono">
                                <span class="font-bold text-slate-900">#{{ $payment->id }}</span>
                                <div class="text-[11px] text-slate-400 font-sans mt-0.5">
                                    {{ $payment->created_at ? $payment->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') : '—' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($payment->order)
                                    <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-bold text-amber-700 hover:underline">
                                        {{ $payment->order->order_number }}
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                                @if($payment->provider_payment_id)
                                    <div class="text-[10px] text-slate-400 font-mono truncate max-w-[140px]" title="{{ $payment->provider_payment_id }}">
                                        Ref: {{ $payment->provider_payment_id }}
                                    </div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800">{{ $payment->order?->customer_name ?? '—' }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $payment->order?->customer_phone ?? '' }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md font-semibold text-[11px]
                                    {{ strtolower((string)$payment->provider) === 'razorpay' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                    {{ strtoupper($payment->provider ?: 'COD') }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                                ₹{{ number_format(($payment->amount_paise ?? 0) / 100, 2) }}
                            </td>

                            <td class="py-3.5 px-4">
                                @php
                                    $st = strtoupper($payment->status?->value ?? (string)$payment->status);
                                @endphp
                                @if($st === 'PAID')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span> Paid
                                    </span>
                                @elseif($st === 'PENDING')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <span class="size-1.5 rounded-full bg-amber-500"></span> Pending
                                    </span>
                                @elseif($st === 'FAILED')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="size-1.5 rounded-full bg-rose-500"></span> Failed
                                    </span>
                                @elseif($st === 'REFUNDED')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                        <span class="size-1.5 rounded-full bg-purple-500"></span> Refunded
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                        {{ $st }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($st === 'PENDING' && $canUpdate)
                                        <form method="POST" action="{{ route('admin.payments.record-offline', $payment->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Confirm cash collection for this order?')"
                                                    class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] shadow-2xs transition-colors">
                                                Mark Collected
                                            </button>
                                        </form>
                                    @endif

                                    @if($payment->order)
                                        <a href="{{ route('admin.orders.show', $payment->order) }}"
                                           class="p-1 rounded text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors" title="View Order">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                No payment transactions found matching the filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
