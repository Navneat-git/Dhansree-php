@extends('layouts.admin')

@section('title', 'Customer Journeys & Traffic Analytics')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Header & Date Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <span>Analytics & Insights</span>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-medium">Customer Journeys</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Customer Journeys & Traffic Explorer</h1>
            <p class="text-xs text-slate-500 mt-1">
                GA4-style acquisition tracking and full step-by-step page exploration paths for every visitor on your store.
            </p>
        </div>

        <!-- Filter Period & Platform -->
        <form method="GET" action="{{ route('admin.analytics.journeys') }}" class="flex flex-wrap items-center gap-2.5">
            <!-- Period Selector -->
            <div class="flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600">
                <a href="{{ route('admin.analytics.journeys', ['period' => 'today', 'platform' => $selectedPlatform]) }}"
                   class="px-3 py-1.5 rounded-md transition-all {{ $period === 'today' ? 'bg-white text-slate-950 font-bold shadow-xs' : 'hover:text-slate-900' }}">
                    Today
                </a>
                <a href="{{ route('admin.analytics.journeys', ['period' => 'yesterday', 'platform' => $selectedPlatform]) }}"
                   class="px-3 py-1.5 rounded-md transition-all {{ $period === 'yesterday' ? 'bg-white text-slate-950 font-bold shadow-xs' : 'hover:text-slate-900' }}">
                    Yesterday
                </a>
                <a href="{{ route('admin.analytics.journeys', ['period' => '7days', 'platform' => $selectedPlatform]) }}"
                   class="px-3 py-1.5 rounded-md transition-all {{ $period === '7days' ? 'bg-white text-slate-950 font-bold shadow-xs' : 'hover:text-slate-900' }}">
                    Last 7 Days
                </a>
                <a href="{{ route('admin.analytics.journeys', ['period' => '30days', 'platform' => $selectedPlatform]) }}"
                   class="px-3 py-1.5 rounded-md transition-all {{ $period === '30days' ? 'bg-white text-slate-950 font-bold shadow-xs' : 'hover:text-slate-900' }}">
                    Last 30 Days
                </a>
            </div>

            <!-- Platform Dropdown -->
            <select name="platform" onchange="this.form.submit()" class="px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-800 shadow-2xs font-medium">
                <option value="all" {{ $selectedPlatform === 'all' ? 'selected' : '' }}>All Traffic Sources</option>
                @foreach($allPlatforms as $p)
                    <option value="{{ $p }}" {{ $selectedPlatform === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Unique Visitors -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Unique Visitors</div>
                <div class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($uniqueVisitors) }}</div>
                <div class="text-[11px] text-slate-500">Tracked via persistent ID</div>
            </div>
        </div>

        <!-- Total Sessions -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Visits / Sessions</div>
                <div class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($totalSessions) }}</div>
                <div class="text-[11px] text-slate-500">Active browsing sessions</div>
            </div>
        </div>

        <!-- Top Platform -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Top Landing Source</div>
                <div class="text-xl font-bold tracking-tight text-slate-900 truncate">{{ $topPlatform }}</div>
                <div class="text-[11px] text-purple-700 font-semibold">{{ $topPlatformShare }}% of all visits</div>
            </div>
        </div>

        <!-- Avg Page Views -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Exploration Depth</div>
                <div class="text-2xl font-bold tracking-tight text-slate-900">{{ $avgPagesPerSession }} <span class="text-xs font-normal text-slate-500">pages</span></div>
                <div class="text-[11px] text-slate-500">Avg pages viewed per session</div>
            </div>
        </div>
    </div>

    <!-- Traffic Sources Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Platform Acquisition Breakdown -->
        <div class="lg:col-span-2 rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center justify-between">
                <span>Acquisition Channels & Landing Platforms</span>
                <span class="text-xs font-normal text-slate-400">{{ count($platformBreakdown) }} sources detected</span>
            </h2>

            <div class="space-y-3">
                @forelse($platformBreakdown as $pb)
                    @php
                        $pct = $totalSessions > 0 ? round(($pb->count / $totalSessions) * 100, 1) : 0;
                        $platformColors = [
                            'Instagram' => 'bg-pink-500 text-pink-700 bg-pink-50',
                            'Facebook' => 'bg-blue-600 text-blue-700 bg-blue-50',
                            'WhatsApp' => 'bg-emerald-500 text-emerald-700 bg-emerald-50',
                            'Google' => 'bg-amber-500 text-amber-700 bg-amber-50',
                            'Direct' => 'bg-slate-700 text-slate-700 bg-slate-50',
                            'YouTube' => 'bg-red-500 text-red-700 bg-red-50',
                        ];
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-800 flex items-center gap-2">
                                @if(str_contains(strtolower($pb->platform), 'instagram'))
                                    <span>📸</span>
                                @elseif(str_contains(strtolower($pb->platform), 'facebook'))
                                    <span>👥</span>
                                @elseif(str_contains(strtolower($pb->platform), 'whatsapp'))
                                    <span>💬</span>
                                @elseif(str_contains(strtolower($pb->platform), 'google'))
                                    <span>🔍</span>
                                @elseif(str_contains(strtolower($pb->platform), 'youtube'))
                                    <span>▶️</span>
                                @else
                                    <span>🌐</span>
                                @endif
                                <span>{{ $pb->platform }}</span>
                            </span>
                            <span class="text-slate-500 font-mono">
                                <strong>{{ number_format($pb->count) }}</strong> sessions ({{ $pct }}%)
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ str_contains(strtolower($pb->platform), 'instagram') ? 'bg-pink-500' : (str_contains(strtolower($pb->platform), 'facebook') ? 'bg-blue-600' : (str_contains(strtolower($pb->platform), 'whatsapp') ? 'bg-emerald-500' : (str_contains(strtolower($pb->platform), 'google') ? 'bg-amber-500' : 'bg-slate-700'))) }}"
                                 style="width: {{ max(4, $pct) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        No customer sessions recorded for this period yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Top Campaigns / Marketing Links -->
        <div class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">
                Top Marketing Campaigns (UTM)
            </h2>

            <div class="space-y-3">
                @forelse($campaignsBreakdown as $cb)
                    <div class="p-3 rounded-lg border border-slate-100 bg-slate-50/50 space-y-1">
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-900">
                            <span class="truncate font-mono">{{ $cb->utm_campaign }}</span>
                            <span class="text-slate-500 font-mono">{{ $cb->count }} visits</span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5">
                            <span>Source:</span>
                            <span class="font-semibold text-amber-700">{{ $cb->utm_source }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-slate-400 text-xs">
                        No UTM campaigns active yet. You can use links like:<br>
                        <code class="text-[10px] text-slate-600 bg-slate-100 px-1 py-0.5 rounded mt-1 inline-block">?utm_source=instagram&utm_campaign=festive</code>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Live Customer Journeys Feed -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                    Live Visitor Journeys & Exploration Trails ({{ $journeys->total() }})
                </h3>
                <p class="text-[11px] text-slate-500">
                    Chronological step-by-step browsing trail showing where each customer landed and what pages they explored.
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
                        'Instagram' => 'bg-pink-50 text-pink-700 border-pink-200',
                        'Facebook' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'WhatsApp' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'Google' => 'bg-amber-50 text-amber-800 border-amber-200',
                        'Direct' => 'bg-slate-100 text-slate-700 border-slate-200',
                    ];
                @endphp
                <div class="p-5 hover:bg-slate-50/60 transition-colors space-y-3" x-data="{ expanded: false }">
                    <!-- Session Summary Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <!-- Platform Tag -->
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $platformStyles[$session->platform] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                @if($session->platform === 'Instagram')
                                    <span>📸</span>
                                @elseif($session->platform === 'Facebook')
                                    <span>👥</span>
                                @elseif($session->platform === 'WhatsApp')
                                    <span>💬</span>
                                @elseif($session->platform === 'Google')
                                    <span>🔍</span>
                                @else
                                    <span>🌐</span>
                                @endif
                                <span>{{ $session->platform }}</span>
                            </span>

                            <!-- Device & OS Badge -->
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600">
                                @if($session->device_type === 'mobile')
                                    <span>📱</span>
                                @elseif($session->device_type === 'tablet')
                                    <span>📟</span>
                                @else
                                    <span>💻</span>
                                @endif
                                <span>{{ ucfirst($session->device_type) }} &bull; {{ $session->browser ?? 'Browser' }} ({{ $session->os ?? 'OS' }})</span>
                            </span>

                            <!-- Visitor ID -->
                            <span class="font-mono text-[11px] text-slate-400" title="Visitor UUID: {{ $session->visitor_uuid }}">
                                ID: {{ substr($session->visitor_uuid, 0, 8) }}...
                            </span>

                            @if($session->utm_campaign)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 font-mono">
                                    UTM: {{ $session->utm_campaign }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 text-[11px] text-slate-500">
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
                            <div class="text-slate-500 truncate max-w-lg">
                                Landing Page: <a href="{{ $session->landing_page }}" target="_blank" class="text-amber-700 hover:underline font-mono">{{ $session->landing_path }}</a>
                                @if($session->referrer_domain)
                                    &bull; Via: <strong class="text-slate-600">{{ $session->referrer_domain }}</strong>
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
                    No customer journeys recorded for this filter. As visitors browse your storefront, their paths will appear here live!
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
