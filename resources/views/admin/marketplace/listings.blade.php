@extends('layouts.admin')

@section('title', 'Marketplace Listings & Export')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{
    copied: false,
    async copyTable() {
        try {
            const url = '{{ route('admin.marketplace.copy') }}?template={{ $selectedTemplate?->id }}&category_id={{ $filters['category_id'] ?? '' }}&q={{ urlencode($filters['search'] ?? '') }}';
            const res = await fetch(url);
            const text = await res.text();
            await navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 3000);
        } catch (e) {
            alert('Failed to copy to clipboard: ' + e.message);
        }
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <span>Multi-Channel Catalog</span>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-medium">Marketplace Feeds</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Marketplace Listings & Export</h1>
            <p class="text-xs text-slate-500 mt-1">
                View and export your catalogue directly in Meesho, Flipkart, Amazon, Myntra, and Ajio listing formats.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <!-- Copy TSV Button -->
            <button type="button" @click="copyTable()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-all">
                <template x-if="!copied">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        <span>Copy for Excel / Sheets</span>
                    </span>
                </template>
                <template x-if="copied">
                    <span class="inline-flex items-center gap-1.5 text-emerald-600 font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Copied to Clipboard!</span>
                    </span>
                </template>
            </button>

            <!-- Export CSV Button -->
            <a href="{{ route('admin.marketplace.export', ['template' => $selectedTemplate?->id, 'category_id' => $filters['category_id'] ?? null, 'q' => $filters['search'] ?? null]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Download {{ $selectedTemplate?->platform }} CSV</span>
            </a>
        </div>
    </div>

    <!-- Platform Selection Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto text-xs font-semibold">
        @foreach($templates as $tpl)
            @php
                $isActive = $selectedTemplate && $selectedTemplate->id === $tpl->id;
                $badgeColors = [
                    'MEESHO' => 'bg-pink-100 text-pink-800',
                    'FLIPKART' => 'bg-blue-100 text-blue-800',
                    'AMAZON' => 'bg-amber-100 text-amber-900',
                    'MYNTRA' => 'bg-rose-100 text-rose-800',
                    'AJIO' => 'bg-teal-100 text-teal-800',
                ];
            @endphp
            <a href="{{ route('admin.marketplace.listings', ['template' => $tpl->id, 'category_id' => $filters['category_id'] ?? null, 'q' => $filters['search'] ?? null]) }}"
               class="px-4 py-3 border-b-2 transition-all flex items-center gap-2 rounded-t-md whitespace-nowrap {{ $isActive ? 'border-amber-600 text-amber-800 bg-amber-50/60 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $badgeColors[$tpl->platform] ?? 'bg-slate-100 text-slate-700' }}">
                    {{ $tpl->platform }}
                </span>
                <span>{{ $tpl->name }}</span>
            </a>
        @endforeach
    </div>

    <!-- Stats & Filter Bar -->
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Filter Form -->
            <form method="GET" action="{{ route('admin.marketplace.listings') }}" class="flex flex-wrap items-center gap-3 flex-1">
                <input type="hidden" name="template" value="{{ $selectedTemplate?->id }}">

                <div class="w-48 sm:w-60">
                    <select name="category_id" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ (string)($filters['category_id'] ?? '') === (string)$cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-56 sm:w-72">
                    <input type="text" name="q" value="{{ $filters['search'] ?? '' }}" placeholder="Search by name, slug or SKU..."
                           class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                </div>

                <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors">
                    Filter
                </button>

                @if(!empty($filters['category_id']) || !empty($filters['search']))
                    <a href="{{ route('admin.marketplace.listings', ['template' => $selectedTemplate?->id]) }}" class="text-xs text-slate-500 hover:underline">
                        Reset
                    </a>
                @endif
            </form>

            <!-- Summary KPI badges -->
            <div class="flex items-center gap-3 text-xs shrink-0 flex-wrap">
                <div class="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 font-medium">
                    Total Variants: <strong class="text-slate-900">{{ $totalRows }}</strong>
                </div>
                <div class="px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-medium border border-emerald-200">
                    Ready to List: <strong class="text-emerald-900">{{ $readyCount }}</strong>
                </div>
                @if($totalRows > $readyCount)
                    <div class="px-3 py-1 rounded-lg bg-amber-50 text-amber-800 font-medium border border-amber-200">
                        Incomplete: <strong class="text-amber-900">{{ $totalRows - $readyCount }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Listing Table Preview -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                    {{ $selectedTemplate?->platform }} Listing Table Preview ({{ count($columns) }} columns &bull; {{ count($rows) }} items)
                </h3>
                <p class="text-[11px] text-slate-500">
                    Pre-formatted for direct upload or copy-paste into {{ $selectedTemplate?->platform }} seller portal.
                </p>
            </div>
            <div class="text-[11px] text-slate-400">
                Scroll horizontally to inspect all columns &rarr;
            </div>
        </div>

        <div class="overflow-x-auto max-h-[650px] relative">
            <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                <thead class="bg-slate-100/90 text-slate-700 font-bold sticky top-0 z-10 border-b border-slate-200">
                    <tr>
                        <th class="p-3 border-r border-slate-200 sticky left-0 bg-slate-100 z-20 shadow-xs">Status</th>
                        @foreach($columns as $col)
                            <th class="p-3 border-r border-slate-200">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ $col->label }}</span>
                                    @if($col->need === 'REQUIRED')
                                        <span class="text-rose-500 font-bold" title="Compulsory field">*</span>
                                    @endif
                                </div>
                                <div class="text-[9px] font-mono text-slate-400 font-normal">Col {{ $col->ref }} &bull; {{ $col->scope }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $r)
                        <tr class="hover:bg-amber-50/30 transition-colors">
                            <td class="p-3 border-r border-slate-200 sticky left-0 bg-white z-10 shadow-xs">
                                @if($r['is_ready'])
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                        READY
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                          title="Missing: {{ implode(', ', $r['missing']) }}">
                                        INCOMPLETE
                                    </span>
                                @endif
                            </td>

                            @foreach($r['cells'] as $idx => $cell)
                                @php
                                    $col = $columns[$idx] ?? null;
                                    $isUrl = $col && $col->kind === 'URL' && str_starts_with((string)$cell, 'http');
                                @endphp
                                <td class="p-3 border-r border-slate-100 text-slate-700 max-w-xs truncate font-mono text-[11px]">
                                    @if($isUrl)
                                        <a href="{{ $cell }}" target="_blank" class="text-amber-700 hover:underline inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                                            <span>Image Link</span>
                                        </a>
                                    @else
                                        {{ $cell ?: '—' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="p-12 text-center text-slate-400">
                                No products found matching your current filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
