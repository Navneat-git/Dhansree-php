@extends('layouts.admin')

@section('title', 'Customers')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Customers</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                People with an account on the store, newest first. Buying as a guest does not create an account.
            </p>
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Filters & Search -->
    <form method="GET" action="{{ route('admin.customers.index') }}" class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-xs space-y-3 sm:space-y-0 sm:flex sm:items-center sm:gap-3">
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
                    placeholder="Search by name, email or mobile..."
                    class="w-full pl-9 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs"
                >
            </div>
        </div>

        <div class="w-full sm:w-44">
            <select name="status" class="w-full px-3 py-2 text-sm bg-white border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
                <option value="">All Statuses</option>
                <option value="ACTIVE" {{ ($filters['status'] ?? '') === 'ACTIVE' ? 'selected' : '' }}>Active</option>
                <option value="DISABLED" {{ ($filters['status'] ?? '') === 'DISABLED' ? 'selected' : '' }}>Disabled</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg text-sm transition-colors shadow-xs">
                Filter
            </button>
            @if(!empty($filters['search']) || !empty($filters['status']))
                <a href="{{ route('admin.customers.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-800 text-sm font-medium">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Table Card -->
    <div class="rounded-xl bg-white border border-slate-200/80 overflow-hidden shadow-xs">
        @if(empty($customers))
            <div class="p-12 text-center text-slate-500 space-y-3">
                <svg class="w-12 h-12 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <p class="text-base font-semibold text-slate-700">No customers found</p>
                <p class="text-xs text-slate-500">Accounts appear here once shoppers sign in with their email.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5">Customer</th>
                            <th class="px-6 py-3.5">Account Status</th>
                            <th class="px-6 py-3.5 text-right">Orders</th>
                            <th class="px-6 py-3.5 text-right">Total Spent</th>
                            <th class="px-6 py-3.5">Last Order</th>
                            <th class="px-6 py-3.5 text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($customers as $c)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.customers.show', $c['id']) }}" class="font-semibold text-slate-900 hover:text-amber-700">
                                        {{ $c['name'] }}
                                    </a>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        {{ $c['email'] }}
                                        @if($c['phone'])
                                            · <span class="font-mono text-slate-400">{{ $c['phone'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border
                                        @if($c['status'] === 'ACTIVE') bg-emerald-50 text-emerald-700 border-emerald-200
                                        @else bg-rose-50 text-rose-700 border-rose-200 @endif">
                                        {{ $c['status'] }}
                                    </span>
                                    <span class="block text-[0.6875rem] text-slate-400 mt-1">
                                        Joined {{ \Carbon\Carbon::parse($c['createdAt'])->format('d M Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-900 tabular-nums">
                                    {{ $c['orderCount'] }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-slate-900 tabular-nums">
                                    ₹{{ number_format($c['totalSpentPaise'] / 100, 2) }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    @if($c['lastOrderAt'])
                                        {{ \Carbon\Carbon::parse($c['lastOrderAt'])->diffForHumans() }}
                                    @else
                                        <span class="text-slate-400">No orders yet</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.customers.show', $c['id']) }}" class="text-xs font-semibold text-amber-700 hover:text-amber-800">
                                        Inspect &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($paginator->hasPages())
                <div class="p-4 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500 bg-slate-50">
                    <div>
                        Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} customers
                    </div>
                    <div class="flex items-center gap-2">
                        @if(!$paginator->onFirstPage())
                            <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold shadow-2xs">
                                Previous
                            </a>
                        @endif
                        @if($paginator->hasMorePages())
                            <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold shadow-2xs">
                                Next
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
