@extends('layouts.admin')

@section('title', 'Edit Collection - Admin')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.collections.index') }}" class="hover:text-amber-400">Collections</a>
                <span>/</span>
                <span>Edit</span>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Edit: {{ $collection->name }}</h1>
        </div>
        <a href="{{ url('/collections/' . $collection->slug) }}"
           target="_blank"
           rel="noopener noreferrer"
           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-800 text-xs font-semibold text-amber-400 hover:text-amber-300 border border-slate-700 transition-colors">
            <span>View on Store</span>
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
        </a>
    </div>

    <!-- Edit Form -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm">
        <form action="{{ route('admin.collections.update', $collection) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Collection Name *</label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $collection->name) }}"
                           required
                           class="w-full text-sm rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">URL Slug *</label>
                    <div class="flex items-center rounded-lg border border-slate-700 bg-slate-800 overflow-hidden">
                        <span class="px-3 text-xs text-slate-400 font-mono">/collections/</span>
                        <input type="text"
                               name="slug"
                               value="{{ old('slug', $collection->slug) }}"
                               required
                               class="flex-1 text-sm bg-transparent py-2 pr-3 text-white placeholder-slate-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Page Heading</label>
                <input type="text"
                       name="heading"
                       value="{{ old('heading', $collection->heading) }}"
                       placeholder="e.g. Women's Ethnic Wear & Festive Edit"
                       class="w-full text-sm rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Introduction Text</label>
                <textarea name="intro"
                          rows="3"
                          class="w-full text-sm rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none">{{ old('intro', $collection->intro) }}</textarea>
            </div>

            <!-- Categories Selection -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">Gathered Categories</label>
                @php
                    $selectedCatIds = $collection->categories->pluck('id')->all();
                @endphp
                <div class="max-h-64 overflow-y-auto rounded-lg border border-slate-700 bg-slate-950/60 p-4 space-y-2.5">
                    @foreach($categories as $cat)
                        <label class="flex items-center gap-3 text-sm text-slate-300 hover:text-white cursor-pointer select-none">
                            <input type="checkbox"
                                   name="categories[]"
                                   value="{{ $cat->id }}"
                                   {{ in_array($cat->id, $selectedCatIds) ? 'checked' : '' }}
                                   class="rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                            <span>{{ $cat->name }} <span class="text-xs text-slate-500 font-mono">(/{{ $cat->slug }})</span></span>
                        </label>
                    @endforeach
                </div>
                <p class="text-[0.6875rem] text-slate-400 mt-1.5">
                    All products in selected categories will be included dynamically on <code>/collections/{{ $collection->slug }}</code>.
                </p>
            </div>

            <!-- SEO Settings -->
            <div class="pt-4 border-t border-slate-800 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">SEO & Metadata</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">SEO Title</label>
                        <input type="text"
                               name="seo_title"
                               value="{{ old('seo_title', $collection->seo_title) }}"
                               placeholder="e.g. Women's Ethnic Wear & Festive Fashion | Dhansree"
                               class="w-full text-sm rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">SEO Description</label>
                        <textarea name="seo_description"
                                  rows="2"
                                  placeholder="Meta description for search engines..."
                                  class="w-full text-sm rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none">{{ old('seo_description', $collection->seo_description) }}</textarea>
                    </div>
                </div>
            <!-- Quick Links Group Selection -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 space-y-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">Quick Links Group</label>
                <p class="text-xs text-slate-400">Select a group of quick navigation links to display on this collection's page, or leave blank to inherit default links.</p>
                <select name="quick_link_group_id" class="w-full text-sm rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-white focus:border-amber-500 focus:outline-none">
                    <option value="">-- Inherit Default / None --</option>
                    @foreach($quickLinkGroups ?? [] as $grp)
                        <option value="{{ $grp->id }}" {{ ($collection->quick_link_group_id == $grp->id) ? 'selected' : '' }}>
                            {{ $grp->title }} ({{ $grp->quickLinks->count() }} links)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Visibility Toggle -->
            <div class="flex items-center gap-2.5 pt-2">
                <input type="checkbox"
                       id="is_visible"
                       name="is_visible"
                       value="1"
                       {{ $collection->is_visible ? 'checked' : '' }}
                       class="rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-500">
                <label for="is_visible" class="text-xs font-semibold text-slate-300 cursor-pointer">Visible on Storefront</label>
            </div>

            <!-- Footer Buttons -->
            <div class="pt-6 border-t border-slate-800 flex items-center justify-between">
                <a href="{{ route('admin.collections.index') }}"
                   class="px-4 py-2.5 rounded-lg bg-slate-800 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition-colors">
                    Back to Collections
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-lg bg-amber-500 text-slate-950 text-xs font-semibold uppercase tracking-wider hover:bg-amber-400 transition-colors cursor-pointer shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
