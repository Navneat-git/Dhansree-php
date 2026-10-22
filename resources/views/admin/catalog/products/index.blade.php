@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Products</h1>
            <p class="text-sm text-zinc-400 mt-1">Manage catalog items, pricing, multi-variant inventory, and media.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-medium text-sm shadow-lg shadow-amber-950/40 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Product
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center gap-2 border-b border-zinc-800/80 pb-3 overflow-x-auto">
        <a href="{{ route('admin.products.index', array_merge(request()->query(), ['status' => null])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 {{ empty($status) ? 'bg-zinc-800 text-white border border-zinc-700' : 'text-zinc-400 hover:text-white' }}">
            <span>All Products</span>
            <span class="px-1.5 py-0.2 rounded-full bg-zinc-700/60 text-[10px] text-zinc-300">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', array_merge(request()->query(), ['status' => 'ACTIVE'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 {{ $status === 'ACTIVE' ? 'bg-emerald-950/70 text-emerald-300 border border-emerald-800/60' : 'text-zinc-400 hover:text-white' }}">
            <span>Active</span>
            <span class="px-1.5 py-0.2 rounded-full bg-emerald-900/60 text-[10px] text-emerald-300">{{ $counts['active'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', array_merge(request()->query(), ['status' => 'DRAFT'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 {{ $status === 'DRAFT' ? 'bg-amber-950/70 text-amber-300 border border-amber-800/60' : 'text-zinc-400 hover:text-white' }}">
            <span>Draft</span>
            <span class="px-1.5 py-0.2 rounded-full bg-amber-900/60 text-[10px] text-amber-300">{{ $counts['draft'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', array_merge(request()->query(), ['status' => 'ARCHIVED'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 {{ $status === 'ARCHIVED' ? 'bg-zinc-800 text-white border border-zinc-700' : 'text-zinc-400 hover:text-white' }}">
            <span>Archived</span>
            <span class="px-1.5 py-0.2 rounded-full bg-zinc-700/60 text-[10px] text-zinc-300">{{ $counts['archived'] }}</span>
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <form method="GET" action="{{ route('admin.products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <input type="hidden" name="status" value="{{ $status }}">

        <div class="md:col-span-2 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by title, SKU, brand..."
                   class="w-full pl-9 pr-4 py-2 bg-zinc-900/90 border border-zinc-800 rounded-lg text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500/60 focus:ring-1 focus:ring-amber-500/50">
        </div>

        <div>
            <select name="category_id" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-zinc-900/90 border border-zinc-800 rounded-lg text-xs text-white focus:outline-none focus:border-amber-500/60">
                <option value="">All Categories</option>
                @foreach($categoryOptions as $opt)
                    <option value="{{ $opt['id'] }}" {{ $categoryId == $opt['id'] ? 'selected' : '' }}>
                        {{ str_repeat('— ', $opt['depth'] - 1) }}{{ $opt['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <select name="sort" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-zinc-900/90 border border-zinc-800 rounded-lg text-xs text-white focus:outline-none focus:border-amber-500/60">
                <option value="updated" {{ $sort === 'updated' ? 'selected' : '' }}>Sort: Recently Updated</option>
                <option value="created" {{ $sort === 'created' ? 'selected' : '' }}>Sort: Recently Created</option>
                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Sort: Title (A-Z)</option>
            </select>
        </div>
    </form>

    <!-- Products Table -->
    <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-900/90 border-b border-zinc-800/80 text-xs font-semibold text-zinc-400 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3.5">Product</th>
                    <th class="px-6 py-3.5">Category</th>
                    <th class="px-6 py-3.5">Price / MRP</th>
                    <th class="px-6 py-3.5">Status</th>
                    <th class="px-6 py-3.5">Stock</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/60">
                @forelse($products as $product)
                    <tr class="hover:bg-zinc-800/20 transition-colors">
                        <!-- Product Title + Thumbnail -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($product->primary_image_url)
                                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                         class="w-12 h-12 rounded-lg object-cover border border-zinc-800 shrink-0 bg-zinc-900">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-zinc-800 flex items-center justify-center text-zinc-500 shrink-0">
                                        <svg class="w-5 h-5 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-white hover:text-amber-400 transition-colors line-clamp-1">
                                        {{ $product->name }}
                                    </a>
                                    <div class="flex items-center gap-2 text-xs text-zinc-400 mt-0.5">
                                        @if($product->brand)
                                            <span class="text-zinc-500">{{ $product->brand }}</span>
                                            <span>&bull;</span>
                                        @endif
                                        <span class="font-mono text-zinc-500">{{ $product->variants->first()?->sku ?? 'No SKU' }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $product->variants->count() }} {{ Str::plural('variant', $product->variants->count()) }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Category -->
                        <td class="px-6 py-4 text-xs text-zinc-300">
                            {{ $product->category?->name ?? '—' }}
                        </td>

                        <!-- Price / MRP -->
                        <td class="px-6 py-4">
                            <div class="font-medium text-white text-sm">
                                {{ $product->formatted_price }}
                            </div>
                            @if($product->mrp_rupees && $product->mrp_rupees > $product->min_price_rupees)
                                <div class="text-xs text-zinc-500 line-through">
                                    ₹{{ number_format($product->mrp_rupees, 0) }}
                                </div>
                            @endif
                        </td>

                        <!-- Status Badge -->
                        <td class="px-6 py-4">
                            @if($product->status->value === 'ACTIVE')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-950/60 text-emerald-400 border border-emerald-800/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Active
                                </span>
                            @elseif($product->status->value === 'DRAFT')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-950/60 text-amber-400 border border-amber-800/60">
                                    Draft
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-800 text-zinc-400 border border-zinc-700">
                                    Archived
                                </span>
                            @endif
                        </td>

                        <!-- Stock Level -->
                        <td class="px-6 py-4">
                            @php
                                $totalStock = $product->variants->sum(fn($v) => $v->stock_on_hand);
                            @endphp
                            @if($totalStock > 5)
                                <span class="text-xs text-emerald-400 font-medium">{{ $totalStock }} in stock</span>
                            @elseif($totalStock > 0)
                                <span class="text-xs text-amber-400 font-medium">{{ $totalStock }} left (Low)</span>
                            @else
                                <span class="text-xs text-rose-400 font-medium">Out of stock</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.products.edit', $product) }}"
                                   class="px-3 py-1.5 text-xs font-medium rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 transition-colors">
                                    Manage
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="text-sm font-medium">No products found matching criteria.</p>
                            <a href="{{ route('admin.products.create') }}" class="text-amber-400 hover:underline text-xs mt-1 inline-block">Create a new product &rarr;</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($products->hasPages())
            <div class="px-6 py-4 border-t border-zinc-800/80 bg-zinc-900/40">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
