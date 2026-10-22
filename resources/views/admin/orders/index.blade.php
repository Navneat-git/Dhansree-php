@extends('layouts.admin')

@section('title', 'Orders')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Orders & Fulfillment</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Manage customer orders, book Shiprocket courier dispatch, track deliveries, and print GST tax invoices.</p>
        </div>
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
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Status Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 overflow-x-auto scrollbar-thin">
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'ALL'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'ALL' ? 'bg-slate-900 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>All Orders</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $currentStatus === 'ALL' ? 'bg-slate-700 text-slate-200' : 'bg-slate-200 text-slate-700' }} text-[10px]">{{ $counts['ALL'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'CONFIRMED'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'CONFIRMED' ? 'bg-blue-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>Confirmed</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $currentStatus === 'CONFIRMED' ? 'bg-blue-500 text-white' : 'bg-blue-100 text-blue-800' }} text-[10px]">{{ $counts['CONFIRMED'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'PROCESSING'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'PROCESSING' ? 'bg-amber-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>Processing</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $currentStatus === 'PROCESSING' ? 'bg-amber-500 text-white' : 'bg-amber-100 text-amber-800' }} text-[10px]">{{ $counts['PROCESSING'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'SHIPPED'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'SHIPPED' ? 'bg-purple-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>Shipped</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $currentStatus === 'SHIPPED' ? 'bg-purple-500 text-white' : 'bg-purple-100 text-purple-800' }} text-[10px]">{{ $counts['SHIPPED'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'DELIVERED'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'DELIVERED' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>Delivered</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $currentStatus === 'DELIVERED' ? 'bg-emerald-500 text-white' : 'bg-emerald-100 text-emerald-800' }} text-[10px]">{{ $counts['DELIVERED'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'CANCELLED'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'CANCELLED' ? 'bg-rose-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>Cancelled</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $currentStatus === 'CANCELLED' ? 'bg-rose-500 text-white' : 'bg-rose-100 text-rose-800' }} text-[10px]">{{ $counts['CANCELLED'] }}</span>
        </a>
        <a href="{{ route('admin.orders.index', array_merge(request()->query(), ['status' => 'PENDING_PAYMENT'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap {{ $currentStatus === 'PENDING_PAYMENT' ? 'bg-slate-700 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span>Pending Payment</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $currentStatus === 'PENDING_PAYMENT' ? 'bg-slate-600 text-white' : 'bg-slate-200 text-slate-700' }} text-[10px]">{{ $counts['PENDING_PAYMENT'] }}</span>
        </a>
    </div>

    <!-- Filters & Search Bar -->
    <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input type="hidden" name="status" value="{{ $currentStatus }}">

        <!-- Search Bar -->
        <div class="lg:col-span-2 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by Order ID, name, email, phone..."
                   class="w-full pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
        </div>

        <!-- Payment Status Filter -->
        <div>
            <select name="payment_status" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
                <option value="ALL">Payment: All</option>
                <option value="PAID" {{ $currentPaymentStatus === 'PAID' ? 'selected' : '' }}>Paid</option>
                <option value="PENDING" {{ $currentPaymentStatus === 'PENDING' ? 'selected' : '' }}>Pending</option>
                <option value="FAILED" {{ $currentPaymentStatus === 'FAILED' ? 'selected' : '' }}>Failed</option>
                <option value="REFUNDED" {{ $currentPaymentStatus === 'REFUNDED' ? 'selected' : '' }}>Refunded</option>
            </select>
        </div>

        <!-- Date Range: From -->
        <div>
            <input type="date" name="date_from" value="{{ $dateFrom }}" placeholder="Date From"
                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs"
                   onchange="this.form.submit()">
        </div>

        <!-- Date Range: To -->
        <div class="flex items-center gap-2">
            <input type="date" name="date_to" value="{{ $dateTo }}" placeholder="Date To"
                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs"
                   onchange="this.form.submit()">
            @if(!empty($search) || !empty($dateFrom) || !empty($dateTo) || ($currentPaymentStatus !== 'ALL') || ($currentStatus !== 'ALL'))
                <a href="{{ route('admin.orders.index') }}" class="p-2 text-slate-400 hover:text-slate-700 transition-colors" title="Clear Filters">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            @endif
        </div>
    </form>

    <!-- Orders Table -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Order</th>
                        <th class="px-6 py-3.5">Customer</th>
                        <th class="px-6 py-3.5">Items</th>
                        <th class="px-6 py-3.5">Total</th>
                        <th class="px-6 py-3.5">Payment</th>
                        <th class="px-6 py-3.5">Fulfillment</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <!-- Order Number & Date -->
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-bold text-amber-700 hover:text-amber-800 transition-colors">
                                    {{ $order->order_number }}
                                </a>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $order->placed_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                                </div>
                            </td>

                            <!-- Customer Info -->
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900 line-clamp-1">{{ $order->customer_name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $order->customer_phone }}</div>
                                @if($order->shippingAddress)
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->postal_code }}</div>
                                @endif
                            </td>

                            <!-- Items -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-xs text-slate-700 font-medium">
                                    {{ $order->item_count }} {{ Str::plural('item', $order->item_count) }}
                                </span>
                                @if($order->items->first())
                                    <div class="text-xs text-slate-600 mt-1 line-clamp-1 max-w-[200px]" title="{{ $order->items->first()->product_name }}">
                                        {{ $order->items->first()->product_name }}
                                        @if($order->items->count() > 1)
                                            <span class="text-slate-400">+{{ $order->items->count() - 1 }} more</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Total -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 text-base">₹{{ number_format($order->total_rupees, 2) }}</div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $order->payment_method->value }}
                                </div>
                            </td>

                            <!-- Payment Badge -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($order->payment_status->value === 'PAID')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Paid
                                    </span>
                                @elseif($order->payment_status->value === 'PENDING')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Pending
                                    </span>
                                @elseif($order->payment_status->value === 'REFUNDED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        Refunded
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $order->payment_status->value }}
                                    </span>
                                @endif
                            </td>

                            <!-- Fulfillment Status Badge -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusClasses = match($order->status->value) {
                                        'CONFIRMED' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'PROCESSING' => 'bg-amber-50 text-amber-800 border-amber-200',
                                        'SHIPPED' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'DELIVERED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusClasses }}">
                                    {{ $order->status->value }}
                                </span>
                                @php
                                    $activeShipment = $order->shipments->firstWhere('active_order_id', $order->id);
                                @endphp
                                @if($activeShipment && $activeShipment->awb)
                                    <div class="text-[11px] font-mono text-slate-500 mt-1">
                                        AWB: {{ $activeShipment->awb }}
                                    </div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors border border-slate-300 shadow-2xs">
                                        View
                                    </a>
                                    <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank"
                                       class="p-1.5 rounded-lg bg-white hover:bg-slate-50 text-slate-500 hover:text-slate-900 transition-colors border border-slate-300 shadow-2xs" title="Print Tax Invoice">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <p class="text-base font-semibold text-slate-700">No orders found</p>
                                <p class="text-xs text-slate-500 mt-1">Try adjusting your search criteria or status filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
