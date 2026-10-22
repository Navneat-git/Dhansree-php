@extends('layouts.admin')

@section('title', 'Newsletter Subscribers')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Newsletter Subscribers</h1>
            <p class="text-xs text-slate-500 mt-1">Manage marketing contacts, newsletter leads, and export CSV for email marketing campaigns.</p>
        </div>
        <div>
            <a href="{{ route('admin.subscribers.export', request()->query()) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white font-semibold text-xs tracking-wider uppercase hover:bg-slate-800 transition-colors shadow-sm cursor-pointer">
                <svg class="size-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Export CSV
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Leads</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Confirmed</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['confirmed']) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Pending</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Unsubscribed</p>
            <p class="text-2xl font-bold text-slate-400 mt-1">{{ number_format($stats['unsubscribed']) }}</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.subscribers.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                       name="search"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="Search email address..."
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
            </div>

            <div class="w-36">
                <select name="status" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    <option value="">All Statuses</option>
                    <option value="CONFIRMED" {{ ($filters['status'] ?? '') === 'CONFIRMED' ? 'selected' : '' }}>Confirmed</option>
                    <option value="PENDING" {{ ($filters['status'] ?? '') === 'PENDING' ? 'selected' : '' }}>Pending</option>
                    <option value="UNSUBSCRIBED" {{ ($filters['status'] ?? '') === 'UNSUBSCRIBED' ? 'selected' : '' }}>Unsubscribed</option>
                </select>
            </div>

            <div class="w-36">
                <select name="source" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    <option value="">All Sources</option>
                    <option value="POPUP" {{ ($filters['source'] ?? '') === 'POPUP' ? 'selected' : '' }}>Popup Modal</option>
                    <option value="FOOTER" {{ ($filters['source'] ?? '') === 'FOOTER' ? 'selected' : '' }}>Footer Form</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition-colors">
                Filter
            </button>
            @if(!empty($filters['search']) || !empty($filters['status']) || !empty($filters['source']))
                <a href="{{ route('admin.subscribers.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-600 text-xs hover:bg-slate-50 transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Subscribers Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if($subscribers->isEmpty())
            <div class="p-12 text-center">
                <div class="size-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No subscribers found</h3>
                <p class="text-xs text-slate-500 mt-1">Visitors subscribing via the website popup or footer will appear here.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-slate-600 font-semibold uppercase tracking-wider text-[0.6875rem]">
                            <th class="py-3 px-4 w-16">ID</th>
                            <th class="py-3 px-4">Subscriber Email</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Source</th>
                            <th class="py-3 px-4">Subscribed At</th>
                            <th class="py-3 px-4">Confirmed At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($subscribers as $s)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4 text-slate-400 font-mono text-[0.6875rem]">
                                    #{{ $s->id }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ $s->email }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.625rem] font-bold {{ $s->status === 'CONFIRMED' ? 'bg-emerald-100 text-emerald-800' : ($s->status === 'PENDING' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600') }}">
                                        {{ $s->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded text-[0.625rem] font-semibold bg-slate-100 text-slate-700">
                                        {{ $s->source }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    {{ $s->created_at ? $s->created_at->format('d M Y, h:i A') : '—' }}
                                </td>
                                <td class="py-3 px-4 text-slate-500">
                                    {{ $s->confirmed_at ? $s->confirmed_at->format('d M Y, h:i A') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($paginator->hasPages())
                <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $paginator->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
