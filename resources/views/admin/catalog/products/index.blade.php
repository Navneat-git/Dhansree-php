@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Products</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Manage catalog items, pricing, multi-variant inventory, and media.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Product
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200/80 pb-3 overflow-x-auto">
        <a href="{{ route('admin.products.index', array_merge(request()->query(), ['status' => null])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 {{ empty($status) ? 'bg-slate-900 text-white border border-slate-900 shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:text-slate-900 hover:bg-slate-50' }}">
            <span>All Products</span>
            <span class="px-1.5 py-0.5 rounded-full {{ empty($status) ? 'bg-slate-700 text-slate-200' : 'bg-slate-100 text-slate-600' }} text-[10px]">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', array_merge(request()->query(), ['status' => 'ACTIVE'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 {{ $status === 'ACTIVE' ? 'bg-emerald-600 text-white border border-emerald-600 shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:text-slate-900 hover:bg-slate-50' }}">
            <span>Active</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $status === 'ACTIVE' ? 'bg-emerald-700 text-emerald-100' : 'bg-emerald-50 text-emerald-700' }} text-[10px]">{{ $counts['active'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', array_merge(request()->query(), ['status' => 'DRAFT'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 {{ $status === 'DRAFT' ? 'bg-amber-500 text-slate-950 border border-amber-500 shadow-xs font-bold' : 'bg-white text-slate-600 border border-slate-200 hover:text-slate-900 hover:bg-slate-50' }}">
            <span>Draft</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $status === 'DRAFT' ? 'bg-amber-600 text-slate-950' : 'bg-amber-50 text-amber-700' }} text-[10px]">{{ $counts['draft'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', array_merge(request()->query(), ['status' => 'ARCHIVED'])) }}"
           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors flex items-center gap-2 {{ $status === 'ARCHIVED' ? 'bg-slate-900 text-white border border-slate-900 shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:text-slate-900 hover:bg-slate-50' }}">
            <span>Archived</span>
            <span class="px-1.5 py-0.5 rounded-full {{ $status === 'ARCHIVED' ? 'bg-slate-700 text-slate-200' : 'bg-slate-100 text-slate-600' }} text-[10px]">{{ $counts['archived'] }}</span>
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <form method="GET" action="{{ route('admin.products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <input type="hidden" name="status" value="{{ $status }}">

        <div class="md:col-span-2 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by title, SKU, brand..."
                   class="w-full pl-9 pr-4 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
        </div>

        <div>
            <select name="category_id" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
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
                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
                <option value="ranking" {{ $sort === 'ranking' ? 'selected' : '' }}>Sort: Ranking (#1, #2...)</option>
                <option value="updated" {{ $sort === 'updated' ? 'selected' : '' }}>Sort: Recently Updated</option>
                <option value="created" {{ $sort === 'created' ? 'selected' : '' }}>Sort: Recently Created</option>
                <option value="name" {{ $sort === 'name' ? 'selected' : '' }}>Sort: Title (A-Z)</option>
            </select>
        </div>
    </form>

    <!-- Products Table -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 border-b border-slate-200/80 text-xs font-semibold text-slate-600 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3.5">Product</th>
                    <th class="px-6 py-3.5">Rank</th>
                    <th class="px-6 py-3.5">Category</th>
                    <th class="px-6 py-3.5">Price / MRP</th>
                    <th class="px-6 py-3.5">Status</th>
                    <th class="px-6 py-3.5">Stock</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($products as $product)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <!-- Product Title + Thumbnail -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($product->primary_image_url)
                                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                         class="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0 bg-slate-50">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 shrink-0">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="font-bold text-slate-900 hover:text-amber-600 transition-colors line-clamp-1">
                                        {{ $product->name }}
                                    </a>
                                    <div class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                                        @if($product->brand)
                                            <span class="text-slate-600 font-medium">{{ $product->brand }}</span>
                                            <span>&bull;</span>
                                        @endif
                                        <span class="font-mono text-slate-500">{{ $product->variants->first()?->sku ?? 'No SKU' }}</span>
                                        <span>&bull;</span>
                                        <span>{{ $product->variants->count() }} {{ Str::plural('variant', $product->variants->count()) }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Rank -->
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.products.update-ranking', $product) }}" class="inline-flex items-center gap-1.5 quick-rank-form" onsubmit="handleQuickRankSubmit(event, this)">
                                @csrf
                                <div class="relative flex items-center">
                                    <input type="number" name="ranking" value="{{ $product->ranking }}" min="1" max="999999" placeholder="—"
                                           class="w-16 px-2 py-1 text-xs font-semibold text-center rounded border border-slate-200 bg-white focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs {{ $product->ranking ? 'text-amber-800 font-bold bg-amber-50/50 border-amber-300' : 'text-slate-500' }}">
                                </div>
                                <button type="submit" title="Save Rank" class="p-1 text-slate-400 hover:text-amber-600 rounded hover:bg-slate-100 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                        </td>

                        <!-- Category -->
                        <td class="px-6 py-4 text-xs text-slate-600 font-medium">
                            {{ $product->category?->name ?? '—' }}
                        </td>

                        <!-- Price / MRP -->
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-900 text-sm">
                                {{ $product->formatted_price }}
                            </div>
                            @if($product->mrp_rupees && $product->mrp_rupees > $product->min_price_rupees)
                                <div class="text-xs text-slate-400 line-through">
                                    ₹{{ number_format($product->mrp_rupees, 0) }}
                                </div>
                            @endif
                        </td>

                        <!-- Status Badge -->
                        <td class="px-6 py-4">
                            @if($product->status->value === 'ACTIVE')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Active
                                </span>
                            @elseif($product->status->value === 'DRAFT')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    Draft
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
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
                                <span class="text-xs text-emerald-700 font-semibold">{{ $totalStock }} in stock</span>
                            @elseif($totalStock > 0)
                                <span class="text-xs text-amber-700 font-semibold">{{ $totalStock }} left (Low)</span>
                            @else
                                <span class="text-xs text-rose-600 font-semibold">Out of stock</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.products.edit', $product) }}"
                                   class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                                    Manage
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="text-sm font-semibold text-slate-700">No products found matching criteria.</p>
                            <a href="{{ route('admin.products.create') }}" class="text-amber-600 hover:underline text-xs mt-1 inline-block font-semibold">Create a new product &rarr;</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($products->hasPages())
            <div class="px-6 py-4 border-t border-slate-200/80 bg-slate-50/50">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function handleQuickRankSubmit(e, form) {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const input = form.querySelector('input[name="ranking"]');
    const origSvg = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="text-[10px] text-amber-600 animate-spin">⟳</span>';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(new FormData(form)).toString(),
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = '<svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        if (data.ranking) {
            input.classList.add('text-amber-800', 'font-bold', 'bg-amber-50/50', 'border-amber-300');
            input.classList.remove('text-slate-500');
        } else {
            input.classList.remove('text-amber-800', 'font-bold', 'bg-amber-50/50', 'border-amber-300');
            input.classList.add('text-slate-500');
        }
        setTimeout(() => {
            btn.innerHTML = origSvg;
            btn.disabled = false;
        }, 1500);
    })
    .catch(() => {
        form.submit();
    });
}
</script>
@endsection
