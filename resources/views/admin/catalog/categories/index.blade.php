@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Categories</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Organize your store hierarchy, navigation menus, and category landing pages.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1 text-xs font-medium shadow-2xs">
                <a href="{{ route('admin.categories.index', array_merge(request()->query(), ['view' => 'tree'])) }}"
                   class="px-3 py-1.5 rounded-md transition-colors {{ $viewMode === 'tree' ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Tree View
                </a>
                <a href="{{ route('admin.categories.index', array_merge(request()->query(), ['view' => 'table'])) }}"
                   class="px-3 py-1.5 rounded-md transition-colors {{ $viewMode === 'table' ? 'bg-slate-900 text-white font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    List View
                </a>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Category
            </a>
        </div>
    </div>

    <!-- Status Tabs & Filter Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-200/80 pb-4">
        <div class="flex items-center gap-2 overflow-x-auto">
            <a href="{{ route('admin.categories.index', ['status' => 'active', 'view' => $viewMode]) }}"
               class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $status === 'active' ? 'bg-slate-900 text-white border border-slate-900 shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:text-slate-900 hover:bg-slate-50' }}">
                Active
            </a>
            <a href="{{ route('admin.categories.index', ['status' => 'visible', 'view' => $viewMode]) }}"
               class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $status === 'visible' ? 'bg-slate-900 text-white border border-slate-900 shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:text-slate-900 hover:bg-slate-50' }}">
                Visible on Store
            </a>
            <a href="{{ route('admin.categories.index', ['status' => 'hidden', 'view' => $viewMode]) }}"
               class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $status === 'hidden' ? 'bg-slate-900 text-white border border-slate-900 shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:text-slate-900 hover:bg-slate-50' }}">
                Hidden
            </a>
            <a href="{{ route('admin.categories.index', ['status' => 'archived', 'view' => $viewMode]) }}"
               class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $status === 'archived' ? 'bg-slate-900 text-white border border-slate-900 shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:text-slate-900 hover:bg-slate-50' }}">
                Archived
            </a>
        </div>

        <form method="GET" action="{{ route('admin.categories.index') }}" class="relative w-full md:w-72">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search categories..."
                       class="w-full pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
            </div>
        </form>
    </div>

    <!-- Tree View -->
    @if($viewMode === 'tree' && empty($search) && $status !== 'archived')
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200/80 bg-slate-50/70 flex items-center justify-between">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-600">Category Hierarchy (Max 3 Levels)</div>
                <div class="text-xs text-slate-400">Root &rsaquo; Subcategory &rsaquo; Sub-subcategory</div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($tree as $root)
                    <div class="p-4 sm:p-5 hover:bg-slate-50/60 transition-colors">
                        <!-- Level 1: Root -->
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                @if($root->image_storage_key)
                                    <img src="{{ $root->image_url }}" alt="{{ $root->name }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shrink-0">
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-700 font-bold shrink-0">
                                        {{ mb_substr($root->name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.categories.edit', $root) }}" class="text-sm font-bold text-slate-900 hover:text-amber-600 transition-colors truncate">
                                            {{ $root->name }}
                                        </a>
                                        <span class="text-xs px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-mono border border-slate-200/60">/{{ $root->slug }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                                        <span>{{ $root->products_count }} products</span>
                                        <span>&bull;</span>
                                        <span>{{ count($root->tree_children ?? []) }} subcategories</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 shrink-0">
                                <!-- Visibility Switch -->
                                <form method="POST" action="{{ route('admin.categories.toggle-visibility', $root) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-colors {{ $root->is_visible ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $root->is_visible ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
                                        {{ $root->is_visible ? 'Visible' : 'Hidden' }}
                                    </button>
                                </form>

                                <a href="{{ route('admin.categories.edit', $root) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Level 2 Children -->
                        @if(!empty($root->tree_children))
                            <div class="mt-3 ml-6 pl-4 border-l-2 border-slate-200 space-y-3">
                                @foreach($root->tree_children as $child)
                                    <div class="flex items-center justify-between gap-4 py-1.5 hover:bg-slate-100/50 rounded px-2">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="text-slate-400 font-mono text-xs">&boxur;</span>
                                            <a href="{{ route('admin.categories.edit', $child) }}" class="text-sm font-semibold text-slate-800 hover:text-amber-600 transition-colors truncate">
                                                {{ $child->name }}
                                            </a>
                                            <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono border border-slate-200/60">/{{ $child->slug }}</span>
                                            <span class="text-xs text-slate-500">({{ $child->products_count }} products)</span>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <form method="POST" action="{{ route('admin.categories.toggle-visibility', $child) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs {{ $child->is_visible ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-slate-600 bg-slate-100 border border-slate-200' }}">
                                                    {{ $child->is_visible ? 'Visible' : 'Hidden' }}
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.categories.edit', $child) }}" class="p-1 text-slate-400 hover:text-slate-900 rounded hover:bg-slate-100" title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Level 3 Children -->
                                    @if(!empty($child->tree_children))
                                        <div class="ml-6 pl-4 border-l border-slate-200 space-y-2">
                                            @foreach($child->tree_children as $subchild)
                                                <div class="flex items-center justify-between gap-4 py-1 hover:bg-slate-100/40 rounded px-2">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="text-slate-300 font-mono text-xs">&boxur;</span>
                                                        <a href="{{ route('admin.categories.edit', $subchild) }}" class="text-xs font-medium text-slate-700 hover:text-amber-600 transition-colors truncate">
                                                            {{ $subchild->name }}
                                                        </a>
                                                        <span class="text-[11px] text-slate-500 font-mono">/{{ $subchild->slug }}</span>
                                                        <span class="text-[11px] text-slate-400">({{ $subchild->products_count }})</span>
                                                    </div>
                                                    <a href="{{ route('admin.categories.edit', $subchild) }}" class="p-1 text-slate-400 hover:text-slate-900 rounded hover:bg-slate-100">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        <p class="text-sm font-semibold text-slate-700">No categories found.</p>
                        <a href="{{ route('admin.categories.create') }}" class="text-amber-600 hover:underline text-xs mt-1 inline-block font-medium">Create your first category &rarr;</a>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- Table View -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Parent</th>
                        <th class="px-6 py-3.5">Products</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($category->image_storage_key)
                                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="w-9 h-9 rounded-lg object-cover border border-slate-200 shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-700 font-bold shrink-0 text-xs">
                                            {{ mb_substr($category->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="font-semibold text-slate-900 hover:text-amber-600 transition-colors">
                                            {{ $category->name }}
                                        </a>
                                        <div class="text-xs text-slate-500 font-mono">/{{ $category->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600 font-medium">
                                {{ $category->parent?->name ?? '— (Root)' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-700">
                                {{ $category->product_count ?? 0 }} products
                            </td>
                            <td class="px-6 py-4">
                                @if($category->archived_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Archived</span>
                                @elseif($category->is_visible)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Visible</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">Hidden</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                No categories found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($categories->hasPages())
                <div class="px-6 py-4 border-t border-slate-200/80 bg-slate-50/50">
                    {{ $categories->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
