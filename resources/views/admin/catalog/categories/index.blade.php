@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Categories</h1>
            <p class="text-sm text-zinc-400 mt-1">Organize your store hierarchy, navigation menus, and category landing pages.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="inline-flex rounded-lg border border-zinc-800 bg-zinc-900/80 p-1 text-xs font-medium">
                <a href="{{ route('admin.categories.index', array_merge(request()->query(), ['view' => 'tree'])) }}"
                   class="px-3 py-1.5 rounded-md transition-colors {{ $viewMode === 'tree' ? 'bg-amber-500/20 text-amber-300 font-semibold' : 'text-zinc-400 hover:text-zinc-200' }}">
                    Tree View
                </a>
                <a href="{{ route('admin.categories.index', array_merge(request()->query(), ['view' => 'table'])) }}"
                   class="px-3 py-1.5 rounded-md transition-colors {{ $viewMode === 'table' ? 'bg-amber-500/20 text-amber-300 font-semibold' : 'text-zinc-400 hover:text-zinc-200' }}">
                    List View
                </a>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-medium text-sm shadow-lg shadow-amber-950/40 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Category
            </a>
        </div>
    </div>

    <!-- Status Tabs & Filter Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-zinc-800/80 pb-4">
        <div class="flex items-center gap-2 overflow-x-auto">
            <a href="{{ route('admin.categories.index', ['status' => 'active', 'view' => $viewMode]) }}"
               class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $status === 'active' ? 'bg-zinc-800 text-white border border-zinc-700' : 'text-zinc-400 hover:text-white' }}">
                Active
            </a>
            <a href="{{ route('admin.categories.index', ['status' => 'visible', 'view' => $viewMode]) }}"
               class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $status === 'visible' ? 'bg-zinc-800 text-white border border-zinc-700' : 'text-zinc-400 hover:text-white' }}">
                Visible on Store
            </a>
            <a href="{{ route('admin.categories.index', ['status' => 'hidden', 'view' => $viewMode]) }}"
               class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $status === 'hidden' ? 'bg-zinc-800 text-white border border-zinc-700' : 'text-zinc-400 hover:text-white' }}">
                Hidden
            </a>
            <a href="{{ route('admin.categories.index', ['status' => 'archived', 'view' => $viewMode]) }}"
               class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $status === 'archived' ? 'bg-zinc-800 text-white border border-zinc-700' : 'text-zinc-400 hover:text-white' }}">
                Archived
            </a>
        </div>

        <form method="GET" action="{{ route('admin.categories.index') }}" class="relative w-full md:w-72">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search categories..."
                       class="w-full pl-9 pr-4 py-2 bg-zinc-900/90 border border-zinc-800 rounded-lg text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500/60 focus:ring-1 focus:ring-amber-500/50">
            </div>
        </form>
    </div>

    <!-- Tree View -->
    @if($viewMode === 'tree' && empty($search) && $status !== 'archived')
        <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-800/80 bg-zinc-900/90 flex items-center justify-between">
                <div class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Category Hierarchy (Max 3 Levels)</div>
                <div class="text-xs text-zinc-500">Root &rsaquo; Subcategory &rsaquo; Sub-subcategory</div>
            </div>

            <div class="divide-y divide-zinc-800/60">
                @forelse($tree as $root)
                    <div class="p-4 sm:p-5 hover:bg-zinc-800/20 transition-colors">
                        <!-- Level 1: Root -->
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                @if($root->image_storage_key)
                                    <img src="{{ $root->image_url }}" alt="{{ $root->name }}" class="w-10 h-10 rounded-lg object-cover border border-zinc-800 shrink-0">
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-amber-950/40 border border-amber-900/40 flex items-center justify-center text-amber-400 font-bold shrink-0">
                                        {{ mb_substr($root->name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.categories.edit', $root) }}" class="text-sm font-semibold text-white hover:text-amber-400 transition-colors truncate">
                                            {{ $root->name }}
                                        </a>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-zinc-800 text-zinc-400 font-mono">/{{ $root->slug }}</span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-zinc-400 mt-1">
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
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-colors {{ $root->is_visible ? 'bg-emerald-950/50 text-emerald-400 border border-emerald-800/60' : 'bg-zinc-800 text-zinc-400 border border-zinc-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $root->is_visible ? 'bg-emerald-400 animate-pulse' : 'bg-zinc-500' }}"></span>
                                        {{ $root->is_visible ? 'Visible' : 'Hidden' }}
                                    </button>
                                </form>

                                <a href="{{ route('admin.categories.edit', $root) }}" class="p-1.5 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Level 2 Children -->
                        @if(!empty($root->tree_children))
                            <div class="mt-3 ml-6 pl-4 border-l-2 border-zinc-800 space-y-3">
                                @foreach($root->tree_children as $child)
                                    <div class="flex items-center justify-between gap-4 py-1.5 hover:bg-zinc-800/10 rounded px-2">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="text-zinc-600 font-mono text-xs">&boxur;</span>
                                            <a href="{{ route('admin.categories.edit', $child) }}" class="text-sm font-medium text-zinc-200 hover:text-amber-400 transition-colors truncate">
                                                {{ $child->name }}
                                            </a>
                                            <span class="text-xs px-1.5 py-0.5 rounded bg-zinc-800/80 text-zinc-500 font-mono">/{{ $child->slug }}</span>
                                            <span class="text-xs text-zinc-500">({{ $child->products_count }} products)</span>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <form method="POST" action="{{ route('admin.categories.toggle-visibility', $child) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs {{ $child->is_visible ? 'text-emerald-400 hover:text-emerald-300' : 'text-zinc-500 hover:text-zinc-400' }}">
                                                    {{ $child->is_visible ? 'Visible' : 'Hidden' }}
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.categories.edit', $child) }}" class="p-1 text-zinc-400 hover:text-white" title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Level 3 Children -->
                                    @if(!empty($child->tree_children))
                                        <div class="ml-6 pl-4 border-l border-zinc-800/70 space-y-2">
                                            @foreach($child->tree_children as $subchild)
                                                <div class="flex items-center justify-between gap-4 py-1 hover:bg-zinc-800/10 rounded px-2">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="text-zinc-700 font-mono text-xs">&boxur;</span>
                                                        <a href="{{ route('admin.categories.edit', $subchild) }}" class="text-xs font-medium text-zinc-300 hover:text-amber-400 transition-colors truncate">
                                                            {{ $subchild->name }}
                                                        </a>
                                                        <span class="text-[11px] text-zinc-500 font-mono">/{{ $subchild->slug }}</span>
                                                        <span class="text-[11px] text-zinc-600">({{ $subchild->products_count }})</span>
                                                    </div>
                                                    <a href="{{ route('admin.categories.edit', $subchild) }}" class="p-1 text-zinc-500 hover:text-white">
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
                    <div class="py-12 text-center text-zinc-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        <p class="text-sm font-medium">No categories found.</p>
                        <a href="{{ route('admin.categories.create') }}" class="text-amber-400 hover:underline text-xs mt-1 inline-block">Create your first category &rarr;</a>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- Table View -->
        <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-900/90 border-b border-zinc-800/80 text-xs font-semibold text-zinc-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Parent</th>
                        <th class="px-6 py-3.5">Products</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60">
                    @forelse($categories as $category)
                        <tr class="hover:bg-zinc-800/20 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($category->image_storage_key)
                                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="w-9 h-9 rounded-lg object-cover border border-zinc-800 shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-lg bg-zinc-800 flex items-center justify-center text-zinc-400 font-bold shrink-0 text-xs">
                                            {{ mb_substr($category->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="font-medium text-white hover:text-amber-400 transition-colors">
                                            {{ $category->name }}
                                        </a>
                                        <div class="text-xs text-zinc-500 font-mono">/{{ $category->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-zinc-400">
                                {{ $category->parent?->name ?? '— (Root)' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-zinc-300">
                                {{ $category->product_count ?? 0 }} products
                            </td>
                            <td class="px-6 py-4">
                                @if($category->archived_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-950/50 text-red-400 border border-red-800/40">Archived</span>
                                @elseif($category->is_visible)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-950/50 text-emerald-400 border border-emerald-800/40">Visible</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-800 text-zinc-400 border border-zinc-700">Hidden</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 transition-colors">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                                No categories found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($categories->hasPages())
                <div class="px-6 py-4 border-t border-zinc-800/80 bg-zinc-900/40">
                    {{ $categories->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
