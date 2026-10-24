@extends('layouts.admin')

@section('title', 'Customer Journeys & Traffic Analytics (GA4)')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Header & Date Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <span>Customers & Analytics</span>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-medium">Customer Journeys</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Customer Journeys & Traffic Explorer</h1>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    GA4 Attribution
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Track who visited your store, landing URL, traffic source (Google CPC, Google Organic, Meta Ads, Instagram, Facebook, WhatsApp), device, location, and unique vs repeat visitor journey trails.
            </p>
        </div>

        <!-- Filter Period, Platform, Type & Search -->
        <form method="GET" action="{{ route('admin.analytics.journeys') }}" class="flex flex-wrap items-center gap-2.5">
            <!-- Period Selector -->
            <div class="flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600">
                <a href="{{ route('admin.analytics.journeys', ['period' => 'today', 'platform' => $selectedPlatform, 'visitor_type' => $selectedVisitorType, 'search' => $search]) }}"
                   class="px-2.5 py-1.5 rounded-md transition-all {{ $period === 'today' ? 'bg-white text-slate-950 font-bold shadow-xs' : 'hover:text-slate-900' }}">
                    Today
                </a>
                <a href="{{ route('admin.analytics.journeys', ['period' => 'yesterday', 'platform' => $selectedPlatform, 'visitor_type' => $selectedVisitorType, 'search' => $search]) }}"
                   class="px-2.5 py-1.5 rounded-md transition-all {{ $period === 'yesterday' ? 'bg-white text-slate-950 font-bold shadow-xs' : 'hover:text-slate-900' }}">
                    Yesterday
                </a>
                <a href="{{ route('admin.analytics.journeys', ['period' => '7days', 'platform' => $selectedPlatform, 'visitor_type' => $selectedVisitorType, 'search' => $search]) }}"
                   class="px-2.5 py-1.5 rounded-md transition-all {{ $period === '7days' ? 'bg-white text-slate-950 font-bold shadow-xs' : 'hover:text-slate-900' }}">
                    Last 7 Days
                </a>
                <a href="{{ route('admin.analytics.journeys', ['period' => '30days', 'platform' => $selectedPlatform, 'visitor_type' => $selectedVisitorType, 'search' => $search]) }}"
                   class="px-2.5 py-1.5 rounded-md transition-all {{ $period === '30days' ? 'bg-white text-slate-950 font-bold shadow-xs' : 'hover:text-slate-900' }}">
                    Last 30 Days
                </a>
                <a href="{{ route('admin.analytics.journeys', ['period' => 'all', 'platform' => $selectedPlatform, 'visitor_type' => $selectedVisitorType, 'search' => $search]) }}"
                   class="px-2.5 py-1.5 rounded-md transition-all {{ $period === 'all' ? 'bg-white text-slate-950 font-bold shadow-xs' : 'hover:text-slate-900' }}">
                    All Time
                </a>
            </div>

            <!-- Visitor Type Filter -->
            <select name="visitor_type" onchange="this.form.submit()" class="px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 shadow-2xs font-medium">
                <option value="all" {{ $selectedVisitorType === 'all' ? 'selected' : '' }}>All Visitors</option>
                <option value="new" {{ $selectedVisitorType === 'new' ? 'selected' : '' }}>New Visitors</option>
                <option value="repeat" {{ $selectedVisitorType === 'repeat' ? 'selected' : '' }}>Repeat Visitors</option>
            </select>

            <!-- Platform Dropdown -->
            <select name="platform" onchange="this.form.submit()" class="px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 shadow-2xs font-medium">
                <option value="all" {{ $selectedPlatform === 'all' ? 'selected' : '' }}>All Traffic Sources</option>
                @foreach($allPlatforms as $p)
                    <option value="{{ $p }}" {{ $selectedPlatform === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>

            <!-- Search box -->
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search ID, email, IP, city..."
                       class="pl-8 pr-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 shadow-2xs w-48">
                <svg class="size-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            </div>
            @if($search || $selectedPlatform !== 'all' || $selectedVisitorType !== 'all')
                <a href="{{ route('admin.analytics.journeys', ['period' => $period]) }}" class="text-xs text-rose-600 hover:underline px-1 font-medium">Reset</a>
            @endif
        </form>
    </div>

    <!-- KPI Summary Cards (5 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Unique Customers -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Unique Customers</div>
                <div class="text-xl font-bold tracking-tight text-slate-900">{{ number_format($uniqueCustomers) }}</div>
                <div class="text-[10px] text-slate-500">By Email & IP match</div>
            </div>
        </div>

        <!-- Repeat Visitors -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Repeat Visitors</div>
                <div class="text-xl font-bold tracking-tight text-purple-700">{{ number_format($repeatVisitorsCount) }}</div>
                <div class="text-[10px] text-purple-600 font-semibold">{{ $repeatRate }}% repeat rate</div>
            </div>
        </div>

        <!-- New Visitors -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">New Visitors</div>
                <div class="text-xl font-bold tracking-tight text-emerald-700">{{ number_format($newVisitorsCount) }}</div>
                <div class="text-[10px] text-slate-500">First-time visitors</div>
            </div>
        </div>

        <!-- Total Sessions -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Visits</div>
                <div class="text-xl font-bold tracking-tight text-slate-900">{{ number_format($totalSessions) }}</div>
                <div class="text-[10px] text-slate-500">Active sessions</div>
            </div>
        </div>

        <!-- Top Platform -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Top Channel</div>
                <div class="text-base font-bold tracking-tight text-slate-900 truncate max-w-[130px]">{{ $topPlatform }}</div>
                <div class="text-[10px] text-amber-700 font-semibold">{{ $topPlatformShare }}% of traffic</div>
            </div>
        </div>
    </div>

    <!-- Traffic Sources & GA4 Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- GA4 Acquisition Channels Breakdown -->
        <div class="lg:col-span-2 rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
                <span>GA4 Acquisition Channels & Landing Platforms</span>
                <span class="text-xs font-normal text-slate-400">{{ count($platformBreakdown) }} sources active</span>
            </h2>

            <div class="space-y-3">
                @forelse($platformBreakdown as $pb)
                    @php
                        $pct = $totalSessions > 0 ? round(($pb->count / $totalSessions) * 100, 1) : 0;
                        $platformIcons = [
                            'Google CPC' => '🔍',
                            'Google Organic' => '🔍',
                            'Meta Ads' => '📢',
                            'Instagram' => '📸',
                            'Facebook' => '👥',
                            'WhatsApp' => '💬',
                            'YouTube' => '▶️',
                            'Twitter / X' => '🐦',
                            'Pinterest' => '📌',
                            'Email Marketing' => '✉️',
                            'Direct' => '🌐',
                            'Referral' => '🔗',
                        ];
                        $barColors = [
                            'Google CPC' => 'bg-amber-500',
                            'Google Organic' => 'bg-emerald-600',
                            'Meta Ads' => 'bg-indigo-600',
                            'Instagram' => 'bg-pink-500',
                            'Facebook' => 'bg-blue-600',
                            'WhatsApp' => 'bg-emerald-500',
                            'YouTube' => 'bg-red-500',
                            'Direct' => 'bg-slate-700',
                        ];
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-800 flex items-center gap-2">
                                <span>{{ $platformIcons[$pb->platform] ?? '🌐' }}</span>
                                <span>{{ $pb->platform }}</span>
                                @if(str_contains($pb->platform, 'CPC') || str_contains($pb->platform, 'Ads'))
                                    <span class="text-[9px] px-1.5 py-0.2 rounded font-bold uppercase bg-amber-100 text-amber-800 border border-amber-200">Paid</span>
                                @endif
                            </span>
                            <span class="text-slate-500 font-mono">
                                <strong>{{ number_format($pb->count) }}</strong> sessions ({{ $pct }}%)
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ $barColors[$pb->platform] ?? 'bg-slate-700' }}"
                                 style="width: {{ max(3, $pct) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        No customer sessions recorded for this period yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Geographic Locations & Top Campaigns -->
        <div class="space-y-6">
            <!-- Top Locations -->
            <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2 flex items-center justify-between">
                    <span>Top Visitor Locations</span>
                    <span class="text-[10px] font-normal text-slate-400">City & Region</span>
                </h2>
                <div class="space-y-2">
                    @forelse($locationsBreakdown as $loc)
                        <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="flex items-center gap-1.5 font-medium text-slate-800 truncate">
                                <span>📍</span>
                                <span>{{ $loc->city ?: 'Unknown City' }}</span>
                                <span class="text-[11px] text-slate-400">({{ $loc->region ?: $loc->country }})</span>
                            </span>
                            <span class="text-slate-600 font-mono font-bold">{{ $loc->count }}</span>
                        </div>
                    @empty
                        <div class="py-4 text-center text-slate-400 text-xs">
                            Locations will appear as visitors arrive.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Top Marketing Campaigns (UTM) -->
            <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-2">
                    Top Marketing Campaigns (UTM)
                </h2>
                <div class="space-y-2">
                    @forelse($campaignsBreakdown as $cb)
                        <div class="p-2.5 rounded-lg border border-slate-100 bg-slate-50/70 space-y-0.5">
                            <div class="flex items-center justify-between text-xs font-semibold text-slate-900">
                                <span class="truncate font-mono">{{ $cb->utm_campaign }}</span>
                                <span class="text-slate-500 font-mono">{{ $cb->count }}</span>
                            </div>
                            <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                <span>Source:</span>
                                <span class="font-semibold text-amber-700">{{ $cb->utm_source }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-4 text-center text-slate-400 text-xs">
                            No UTM campaigns tagged yet. Use parameters like:<br>
                            <code class="text-[10px] text-slate-600 bg-slate-100 px-1 py-0.5 rounded mt-1 inline-block">?utm_source=meta&utm_medium=cpc&utm_campaign=diwali</code>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Live Customer Journeys Feed -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50/50">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <span>Live Visitor Journeys & Exploration Trails</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-slate-200 text-slate-800">
                        {{ number_format($journeys->total()) }} sessions
                    </span>
                </h3>
                <p class="text-[11px] text-slate-500">
                    Showing unique customer IDs, repeat vs new status, arrival channel, location, landing page, and chronological steps.
                </p>
            </div>
            <div class="text-xs font-semibold text-slate-500">
                Page {{ $journeys->currentPage() }} of {{ $journeys->lastPage() }}
            </div>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($journeys as $session)
                @php
                    $durationTotal = $session->started_at && $session->last_activity_at
                        ? max(0, $session->last_activity_at->diffInSeconds($session->started_at))
                        : 0;

                    $platformStyles = [
                        'Google CPC' => 'bg-amber-50 text-amber-900 border-amber-300',
                        'Google Organic' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                        'Meta Ads' => 'bg-indigo-50 text-indigo-800 border-indigo-300',
                        'Instagram' => 'bg-pink-50 text-pink-700 border-pink-200',
                        'Facebook' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'WhatsApp' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'Direct' => 'bg-slate-100 text-slate-700 border-slate-200',
                        'Referral' => 'bg-cyan-50 text-cyan-800 border-cyan-200',
                    ];
                @endphp
                <div class="p-5 hover:bg-slate-50/60 transition-colors space-y-3" x-data="{ expanded: false }">
                    <!-- Session Summary Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Unique Customer ID Badge -->
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-slate-900 text-amber-300 shadow-2xs"
                                  title="Unique Customer ID (assigned by Email & IP matching)">
                                <span>👤</span>
                                <span>{{ $session->customer_code ?: 'CUST-' . substr($session->visitor_uuid, 0, 8) }}</span>
                            </span>

                            <!-- New vs Repeat Visitor Badge -->
                            @if($session->is_repeat_visitor)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                    <span>🔄 Repeat Visitor</span>
                                    @if($session->visit_number > 1)
                                        <span class="text-[10px] font-normal">(Visit #{{ $session->visit_number }})</span>
                                    @endif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <span>✨ New Visitor</span>
                                </span>
                            @endif

                            <!-- Platform / Source Tag -->
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $platformStyles[$session->platform] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                @if(str_contains($session->platform, 'Google'))
                                    <span>🔍</span>
                                @elseif(str_contains($session->platform, 'Meta'))
                                    <span>📢</span>
                                @elseif($session->platform === 'Instagram')
                                    <span>📸</span>
                                @elseif($session->platform === 'Facebook')
                                    <span>👥</span>
                                @elseif($session->platform === 'WhatsApp')
                                    <span>💬</span>
                                @else
                                    <span>🌐</span>
                                @endif
                                <span>{{ $session->platform }}</span>
                            </span>

                            <!-- Location Badge -->
                            @if($session->city || $session->country)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200" title="{{ $session->display_location }}">
                                    <span>📍</span>
                                    <span>{{ $session->city ?: $session->region ?: $session->country }}</span>
                                </span>
                            @endif

                            <!-- Device & OS Badge -->
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600">
                                @if($session->device_type === 'mobile')
                                    <span>📱</span>
                                @elseif($session->device_type === 'tablet')
                                    <span>📟</span>
                                @else
                                    <span>💻</span>
                                @endif
                                <span>{{ ucfirst($session->device_type) }} &bull; {{ $session->browser ?? 'Browser' }}</span>
                            </span>

                            <!-- Email / IP tag if known -->
                            @if($session->customer_email)
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    ✉️ {{ $session->customer_email }}
                                </span>
                            @endif

                            @if($session->ip_address)
                                <span class="font-mono text-[10px] text-slate-400">
                                    IP: {{ $session->ip_address }}
                                </span>
                            @endif

                            @if($session->utm_campaign)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 font-mono">
                                    UTM: {{ $session->utm_campaign }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2.5 text-[11px] text-slate-500 shrink-0">
                            <span>Started: <strong class="text-slate-700">{{ $session->started_at->format('M j, Y h:i A') }}</strong></span>
                            <span>&bull;</span>
                            <span>Pages: <strong class="text-slate-900 font-bold">{{ $session->page_views_count }}</strong></span>
                            @if($durationTotal > 0)
                                <span>&bull;</span>
                                <span>Active: <strong class="text-slate-700">{{ $durationTotal < 60 ? $durationTotal . 's' : round($durationTotal / 60, 1) . 'm' }}</strong></span>
                            @endif
                        </div>
                    </div>

                    <!-- Landing URL & Exploration Trail -->
                    <div class="space-y-2">
                        <!-- Step-by-Step Path Preview -->
                        <div class="flex items-center gap-2 overflow-x-auto py-1 scrollbar-thin">
                            @foreach($session->pageViews as $index => $pv)
                                <div class="inline-flex items-center gap-1.5 shrink-0 px-2.5 py-1 rounded-lg border text-xs {{ $index === 0 ? 'bg-amber-50/70 border-amber-200 text-amber-900 font-medium' : 'bg-white border-slate-200 text-slate-700' }}">
                                    <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold flex items-center justify-center shrink-0">
                                        {{ $pv->step_number }}
                                    </span>
                                    <span class="truncate max-w-[200px]" title="{{ $pv->path }}">
                                        {{ $pv->page_title ?: $pv->path }}
                                    </span>
                                    @if($pv->duration_seconds)
                                        <span class="text-[10px] text-slate-400 font-mono">
                                            ({{ $pv->duration_seconds < 60 ? $pv->duration_seconds . 's' : round($pv->duration_seconds / 60, 1) . 'm' }})
                                        </span>
                                    @endif
                                </div>

                                @if(!$loop->last)
                                    <svg class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                @endif
                            @endforeach
                        </div>

                        <!-- Full Details Accordion Toggle -->
                        <div class="flex items-center justify-between text-[11px] pt-1">
                            <div class="text-slate-500 truncate max-w-xl">
                                Landing Page: <a href="{{ $session->landing_page }}" target="_blank" class="text-amber-700 hover:underline font-mono">{{ $session->landing_path }}</a>
                                @if($session->referrer_domain)
                                    &bull; Via: <strong class="text-slate-600">{{ $session->referrer_domain }}</strong>
                                @endif
                                @if($session->display_location !== 'Location Unknown')
                                    &bull; Location: <strong class="text-slate-600">{{ $session->display_location }}</strong>
                                @endif
                            </div>

                            <button type="button" @click="expanded = !expanded" class="text-amber-700 hover:underline font-semibold flex items-center gap-1">
                                <span x-text="expanded ? 'Hide Details' : 'View Full Trail Details'"></span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>

                        <!-- Expanded Full Journey Details -->
                        <div x-show="expanded" x-cloak class="mt-3 p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-3">
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Chronological Journey Trail:</h4>
                            <div class="space-y-2">
                                @foreach($session->pageViews as $pv)
                                    <div class="flex items-start justify-between gap-4 text-xs bg-white p-2.5 rounded-lg border border-slate-200/60 shadow-2xs">
                                        <div class="flex items-center gap-3">
                                            <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-bold text-[11px] flex items-center justify-center shrink-0">
                                                {{ $pv->step_number }}
                                            </span>
                                            <div>
                                                <div class="font-semibold text-slate-900">{{ $pv->page_title ?: $pv->path }}</div>
                                                <a href="{{ $pv->url }}" target="_blank" class="text-[11px] text-amber-700 hover:underline font-mono truncate block max-w-md">
                                                    {{ $pv->path }}
                                                </a>
                                            </div>
                                        </div>
                                        <div class="text-right text-[11px] text-slate-500 shrink-0">
                                            <div>{{ $pv->created_at->format('h:i:s A') }}</div>
                                            @if($pv->duration_seconds)
                                                <div class="text-slate-400 font-mono">Stayed {{ $pv->duration_seconds }}s</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-16 text-center text-slate-400 text-xs">
                    No customer journeys recorded for this filter. As visitors browse your storefront, their paths, customer IDs, and GA4 channels will appear here live!
                </div>
            @endforelse
        </div>

        @if($journeys->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                {{ $journeys->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
