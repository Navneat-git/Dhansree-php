@extends('layouts.admin')

@section('title', 'SEO 301 Redirects')

@section('content')
<div class="space-y-6" x-data="{ addModalOpen: false }">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">SEO 301 Redirects &amp; URL Migrations</h1>
            <p class="text-xs text-slate-500 mt-1">Preserve search rankings, fix broken links, and manage seamless 301 permanent redirects from old URLs.</p>
        </div>
        @if($canUpdate)
        <button type="button"
                @click="addModalOpen = true"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-semibold text-xs tracking-wider uppercase hover:bg-amber-400 transition-colors shadow-sm self-start sm:self-auto cursor-pointer">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add Redirect
        </button>
        @endif
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Rules</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Active Rules</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">301 Permanent</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($stats['code_301']) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">410 Gone</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($stats['code_410']) }}</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ route('admin.seo.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px]">
                <input type="text"
                       name="search"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="Search source or destination URL..."
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
            </div>

            <div class="w-32">
                <select name="status_code" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    <option value="">All Codes</option>
                    <option value="301" {{ ($filters['status_code'] ?? '') == '301' ? 'selected' : '' }}>301 Moved</option>
                    <option value="410" {{ ($filters['status_code'] ?? '') == '410' ? 'selected' : '' }}>410 Gone</option>
                </select>
            </div>

            <div class="w-32">
                <select name="is_active" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    <option value="">All Statuses</option>
                    <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition-colors">
                Filter
            </button>
            @if(!empty($filters['search']) || !empty($filters['status_code']) || isset($filters['is_active']))
                <a href="{{ route('admin.seo.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-slate-600 text-xs hover:bg-slate-50 transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Redirects Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if($redirects->isEmpty())
            <div class="p-12 text-center">
                <div class="size-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">No redirects found</h3>
                <p class="text-xs text-slate-500 mt-1">Add redirects for old product URLs, renamed categories, or legacy website links.</p>
                @if($canUpdate)
                <div class="mt-4">
                    <button type="button" @click="addModalOpen = true" class="px-4 py-2 rounded-lg bg-amber-500 text-slate-950 font-semibold text-xs hover:bg-amber-400 transition-colors cursor-pointer">
                        Add First Redirect
                    </button>
                </div>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-slate-600 font-semibold uppercase tracking-wider text-[0.6875rem]">
                            <th class="py-3 px-4">Source URL</th>
                            <th class="py-3 px-4 w-8 text-center">→</th>
                            <th class="py-3 px-4">Destination Target</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Origin</th>
                            <th class="py-3 px-4 text-center">Active</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        @foreach($redirects as $r)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4 text-slate-900 font-medium">
                                    {{ $r->source_path }}
                                </td>
                                <td class="py-3 px-4 text-center text-slate-400 font-sans">
                                    →
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    @if($r->destination_path)
                                        <a href="{{ $r->destination_path }}" target="_blank" class="hover:text-amber-600 underline decoration-slate-300">
                                            {{ $r->destination_path }}
                                        </a>
                                    @else
                                        <span class="text-purple-600 italic font-sans text-xs">410 Gone (Permanently Removed)</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-sans">
                                    @if($r->status_code === 301)
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[0.625rem] font-bold bg-blue-100 text-blue-800">
                                            301 Permanent
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[0.625rem] font-bold bg-purple-100 text-purple-800">
                                            410 Gone
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-sans">
                                    <span class="inline-block px-2 py-0.5 rounded text-[0.625rem] font-semibold {{ $r->origin->value === 'AUTOMATIC' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $r->origin->value }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-sans">
                                    @if($canUpdate)
                                        <form method="POST" action="{{ route('admin.seo.toggle', $r->id) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold transition-colors cursor-pointer {{ $r->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                                <span class="size-1.5 rounded-full mr-1.5 {{ $r->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                {{ $r->is_active ? 'Active' : 'Disabled' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold {{ $r->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                            {{ $r->is_active ? 'Active' : 'Disabled' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-sans">
                                    @if($canUpdate)
                                    <form method="POST" action="{{ route('admin.seo.destroy', $r->id) }}" class="inline" onsubmit="return confirm('Delete this redirect rule?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50 text-xs font-medium transition-colors cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                    @endif
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

    <!-- Add Redirect Modal -->
    <div x-show="addModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4" @click.away="addModalOpen = false" x-data="{ code: '301' }">
            <h3 class="text-base font-bold text-slate-900">Add SEO Redirect</h3>
            <p class="text-xs text-slate-500">Configure redirect from old legacy path to current store URL.</p>

            <form action="{{ route('admin.seo.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Source Path (Old URL)</label>
                    <input type="text"
                           name="source_path"
                           required
                           placeholder="e.g. /old-kurti-collection"
                           class="w-full text-xs font-mono rounded-lg border-slate-300">
                    <p class="text-[0.6875rem] text-slate-400 mt-1">Must start with slash (e.g. /old-kurti-collection)</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status Code</label>
                    <select name="status_code" x-model="code" class="w-full text-xs rounded-lg border-slate-300">
                        <option value="301">301 — Moved Permanently (Recommended)</option>
                        <option value="410">410 — Gone (Permanently Removed)</option>
                    </select>
                </div>

                <div x-show="code === '301'">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Destination Target (New URL)</label>
                    <input type="text"
                           name="destination_path"
                           :required="code === '301'"
                           placeholder="e.g. /kurta-set"
                           class="w-full text-xs font-mono rounded-lg border-slate-300">
                    <p class="text-[0.6875rem] text-slate-400 mt-1">Relative URL (e.g. /kurta-set)</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addModalOpen = false" class="px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-950 bg-amber-500 hover:bg-amber-400 rounded-lg uppercase tracking-wider">
                        Create Redirect
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
