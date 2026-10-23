@extends('layouts.admin')

@section('title', 'Collections - Admin')

@section('content')
<div x-data="{ createModalOpen: false }" class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Curated Collections</h1>
            <p class="text-sm text-slate-400 mt-1">
                Gather multiple categories under curated campaign pages (e.g. <em>Women's Ethnic Fashion</em>, <em>Everyday Essentials</em>).
            </p>
        </div>
        <button type="button"
                @click="createModalOpen = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-amber-500 text-slate-950 font-semibold text-xs tracking-wider uppercase hover:bg-amber-400 transition-colors shadow-sm cursor-pointer self-start sm:self-auto">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Create Collection
        </button>
    </div>

    <!-- Flash message -->
    @if(session('status'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-400 flex items-center gap-3">
            <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Collections Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-3.5 font-semibold">Collection</th>
                        <th class="px-6 py-3.5 font-semibold">Store URL</th>
                        <th class="px-6 py-3.5 font-semibold">Gathered Categories</th>
                        <th class="px-6 py-3.5 font-semibold text-center">Status</th>
                        <th class="px-6 py-3.5 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-normal">
                    @forelse($collections as $col)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-white">{{ $col->name }}</div>
                                @if($col->heading && $col->heading !== $col->name)
                                    <div class="text-xs text-slate-400">{{ $col->heading }}</div>
                                @endif
                                @if($col->intro)
                                    <div class="text-xs text-slate-500 truncate max-w-xs mt-0.5">{{ $col->intro }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ url('/collections/' . $col->slug) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-xs text-amber-400 hover:text-amber-300 font-mono underline">
                                    <span>/collections/{{ $col->slug }}</span>
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5 max-w-sm">
                                    @forelse($col->categories as $c)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[0.6875rem] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                            {{ $c->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-500 italic">No categories assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('admin.collections.toggle-visibility', $col) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold cursor-pointer {{ $col->is_visible ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' }}"
                                            title="Click to toggle visibility">
                                        <span class="size-1.5 rounded-full {{ $col->is_visible ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                                        {{ $col->is_visible ? 'Active' : 'Hidden' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.collections.edit', $col) }}"
                                       class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition-colors">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.collections.destroy', $col) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete collection \'{{ $col->name }}\'?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-rose-950/60 hover:bg-rose-900 text-xs font-semibold text-rose-300 border border-rose-800/40 transition-colors cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <div class="text-3xl mb-2">📁</div>
                                <div class="text-base font-semibold text-slate-300">No collections found</div>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
                                    Create a collection to combine multiple categories into a single landing page with dynamic filtering and sorting.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Collection Modal -->
    <div x-show="createModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 flex items-center justify-center p-4">
        <div @click.away="createModalOpen = false"
             class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl p-6 sm:p-8 space-y-6 shadow-2xl text-left">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="text-lg font-bold text-white">Create New Curated Collection</h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-white">
                    ✕
                </button>
            </div>

            <form action="{{ route('admin.collections.store') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Collection Name *</label>
                    <input type="text"
                           name="name"
                           required
                           placeholder="e.g. Women Fashion"
                           class="w-full text-sm rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">URL Slug</label>
                    <div class="flex items-center rounded-lg border border-slate-700 bg-slate-800 overflow-hidden">
                        <span class="px-3 text-xs text-slate-400 font-mono">/collections/</span>
                        <input type="text"
                               name="slug"
                               placeholder="leave blank to auto-generate"
                               class="flex-1 text-sm bg-transparent py-2 pr-3 text-white placeholder-slate-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Page Heading</label>
                    <input type="text"
                           name="heading"
                           placeholder="e.g. Women's Ethnic Wear & Festive Edit"
                           class="w-full text-sm rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Introduction Text</label>
                    <textarea name="intro"
                              rows="2"
                              placeholder="Brief description displayed under the collection title..."
                              class="w-full text-sm rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">Gathered Categories</label>
                    <div class="max-h-48 overflow-y-auto rounded-lg border border-slate-700 bg-slate-950/60 p-3 space-y-2">
                        @foreach($categories as $cat)
                            <label class="flex items-center gap-2.5 text-xs text-slate-300 hover:text-white cursor-pointer select-none">
                                <input type="checkbox"
                                       name="categories[]"
                                       value="{{ $cat->id }}"
                                       class="rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                                <span>{{ $cat->name }} <span class="text-slate-500 font-mono">(/{{ $cat->slug }})</span></span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[0.6875rem] text-slate-400 mt-1">Select all categories whose products should appear in this collection.</p>
                </div>

                <div class="flex items-center gap-2.5 pt-2">
                    <input type="checkbox"
                           id="is_visible"
                           name="is_visible"
                           value="1"
                           checked
                           class="rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                    <label for="is_visible" class="text-xs font-semibold text-slate-300 cursor-pointer">Visible on Storefront</label>
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="createModalOpen = false"
                            class="px-4 py-2 rounded-lg bg-slate-800 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-amber-500 text-slate-950 text-xs font-semibold uppercase tracking-wider hover:bg-amber-400 transition-colors cursor-pointer">
                        Save Collection
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
