@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Overview & Store Metrics')

@section('content')
<div class="space-y-8">
    <!-- Top KPI Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Revenue Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-xs font-semibold uppercase tracking-wider">Total Revenue</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold text-slate-900">₹{{ number_format($totalRevenueRupees, 2) }}</h3>
                <p class="text-xs text-slate-500 mt-1">Excludes cancelled orders</p>
            </div>
        </div>

        <!-- Orders Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-xs font-semibold uppercase tracking-wider">Orders</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl font-bold text-slate-900">{{ number_format($totalOrders) }}</h3>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                        +{{ $ordersToday }} today
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">All store orders</p>
            </div>
        </div>

        <!-- Active Products Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-xs font-semibold uppercase tracking-wider">Active Products</span>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold text-slate-900">{{ number_format($activeProductsCount) }}</h3>
                <p class="text-xs text-slate-500 mt-1">Live in store catalog</p>
            </div>
        </div>

        <!-- Low Stock Alert Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-xs font-semibold uppercase tracking-wider">Inventory Health</span>
                <span class="p-2 rounded-xl {{ $lowStockCount > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl font-bold {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                        {{ $lowStockCount }}
                    </h3>
                    <span class="text-xs font-medium {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $lowStockCount > 0 ? 'items need reorder' : 'All stocks healthy' }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Available &le; 5 units</p>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts Bar -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl p-5 text-white shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h4 class="text-sm font-semibold text-white">Direct Management Actions</h4>
            <p class="text-xs text-slate-400 mt-0.5">Quickly jump to primary catalog & store administration workflows</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ url('/admin/catalog/products') }}" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs transition-colors shadow-xs">
                Manage Products
            </a>
            <a href="{{ url('/admin/catalog/categories') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium text-xs transition-colors border border-slate-700">
                Categories Tree
            </a>
            <a href="{{ url('/admin/inventory') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium text-xs transition-colors border border-slate-700">
                Stock Adjustments
            </a>
            <a href="{{ url('/admin/orders') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium text-xs transition-colors border border-slate-700">
                View Orders
            </a>
        </div>
    </div>

    <!-- Live Recent Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Recent Customer Orders</h3>
                <p class="text-xs text-slate-500 mt-0.5">Latest store transactions and fulfilment status</p>
            </div>
            <a href="{{ url('/admin/orders') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700 transition-colors">
                View all orders &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                        <th class="px-5 py-3">Order Number</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Items</th>
                        <th class="px-5 py-3">Total Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Placed At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3.5 font-semibold text-slate-900">
                                #{{ $order->order_number }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-slate-900">{{ $order->customer->full_name ?? 'Guest Customer' }}</div>
                                <div class="text-[0.6875rem] text-slate-500">{{ $order->customer->email ?? '' }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                {{ $order->items->count() }} items
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-900">
                                ₹{{ number_format($order->total_paise / 100, 2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                @php
                                    $statusColor = match($order->status?->value ?? '') {
                                        'DELIVERED' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'SHIPPED' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        'CONFIRMED', 'PROCESSING' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'CANCELLED' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                        default => 'bg-slate-100 text-slate-700 ring-slate-600/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColor }}">
                                    {{ $order->status?->value ?? 'PENDING' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-500">
                                {{ $order->placed_at?->diffForHumans() ?? 'Just now' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-400">
                                No orders recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Audit Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Security Audit Trail</h3>
                <p class="text-xs text-slate-500 mt-0.5">Immutable log of administrator actions and authentication events</p>
            </div>
            <a href="{{ url('/admin/audit-logs') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700 transition-colors">
                View full audit log &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase tracking-wider">
                        <th class="px-5 py-3">Action</th>
                        <th class="px-5 py-3">Actor</th>
                        <th class="px-5 py-3">IP Address</th>
                        <th class="px-5 py-3">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-mono text-[0.6875rem]">
                    @forelse($recentAuditLogs as $log)
                        <tr class="hover:bg-slate-50/80 transition-colors font-sans">
                            <td class="px-5 py-3 font-medium text-slate-900">
                                <span class="font-mono text-xs text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-700">
                                {{ $log->adminUser->name ?? $log->actor_type?->value ?? 'System' }}
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500 font-sans">
                                {{ $log->occurred_at?->diffForHumans() ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-400 font-sans">
                                No audit events logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
