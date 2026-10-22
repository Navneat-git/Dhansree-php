@extends('layouts.admin')

@section('title', 'Shipments & Fulfillment')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Shipments & Logistics</h1>
                <span class="rounded-full bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                    {{ $shipments->total() }} Shipments
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Courier manifests, AWB tracking, dispatch operations, and customer delivery progress.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.shipments.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="flex-1 relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by AWB code, Order #, Courier, or Customer..."
                       class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-amber-500">
                <svg class="size-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <select name="status" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700 font-medium focus:outline-none focus:bg-white focus:border-amber-500">
                <option value="">All Shipment Statuses</option>
                <option value="MANIFESTED" {{ $currentStatus === 'MANIFESTED' ? 'selected' : '' }}>Manifested</option>
                <option value="IN_TRANSIT" {{ $currentStatus === 'IN_TRANSIT' ? 'selected' : '' }}>In Transit</option>
                <option value="OUT_FOR_DELIVERY" {{ $currentStatus === 'OUT_FOR_DELIVERY' ? 'selected' : '' }}>Out for Delivery</option>
                <option value="DELIVERED" {{ $currentStatus === 'DELIVERED' ? 'selected' : '' }}>Delivered</option>
                <option value="CANCELLED" {{ $currentStatus === 'CANCELLED' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <button type="submit" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-semibold uppercase tracking-wider transition-colors shadow-2xs">
                Filter
            </button>

            @if($search || $currentStatus)
                <a href="{{ route('admin.shipments.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-xs font-medium text-center transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Shipment / Date</th>
                        <th class="py-3 px-4">Order</th>
                        <th class="py-3 px-4">Courier & AWB</th>
                        <th class="py-3 px-4">Payment & Weight</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($shipments as $shipment)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 font-mono">
                                <span class="font-bold text-slate-900">#{{ $shipment->id }}</span>
                                <div class="text-[11px] text-slate-400 font-sans mt-0.5">
                                    {{ $shipment->created_at ? $shipment->created_at->format('d M Y, h:i A') : '—' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($shipment->order)
                                    <a href="{{ route('admin.orders.show', $shipment->order) }}" class="font-bold text-amber-700 hover:underline">
                                        {{ $shipment->order->order_number }}
                                    </a>
                                    <div class="text-[11px] text-slate-600 font-sans mt-0.5">{{ $shipment->order->customer_name }}</div>
                                @else
                                    <span class="text-slate-400 font-mono">—</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 font-mono">
                                <div class="font-bold text-slate-900 font-sans">{{ $shipment->courier_name ?: 'Shiprocket' }}</div>
                                @if($shipment->awb_code)
                                    <div class="text-[11px] text-amber-700 font-semibold">{{ $shipment->awb_code }}</div>
                                @else
                                    <span class="text-[11px] text-slate-400">AWB Pending</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ $shipment->payment_mode?->value === 'COD' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $shipment->payment_mode?->value ?: 'PREPAID' }}
                                </span>
                                @if($shipment->weight_grams)
                                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $shipment->weight_grams }}g</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                @php
                                    $st = strtoupper($shipment->status?->value ?? (string)$shipment->status);
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold
                                    @if($st === 'DELIVERED') bg-emerald-50 text-emerald-700 border border-emerald-200
                                    @elseif(in_array($st, ['IN_TRANSIT', 'OUT_FOR_DELIVERY', 'SHIPPED'])) bg-blue-50 text-blue-700 border border-blue-200
                                    @elseif($st === 'CANCELLED') bg-rose-50 text-rose-700 border border-rose-200
                                    @else bg-amber-50 text-amber-700 border border-amber-200 @endif">
                                    {{ str_replace('_', ' ', $st) }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                @if($shipment->order)
                                    <a href="{{ route('admin.orders.show', $shipment->order) }}"
                                       class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-[11px] shadow-2xs transition-colors">
                                        View Order &rarr;
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                No shipments found matching the filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shipments->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
                {{ $shipments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
