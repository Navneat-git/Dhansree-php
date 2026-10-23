@extends('layouts.admin')

@section('title', 'Customer Wishlists')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.products.index') }}" class="hover:text-amber-600 transition-colors">Catalog</a>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-medium">Wishlists</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Customer Wishlists & Demand Insights</h1>
            <p class="text-xs text-slate-500 mt-1">
                Track customer favorites, high-demand products, and individual customer wishlists to optimize inventory and marketing.
            </p>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-xl border border-slate-200/80 bg-white shadow-2xs space-y-1">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Wishlist Saves</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold text-slate-900">{{ number_format($totalWishlistItems) }}</span>
                <span class="text-xs text-rose-600 font-medium">♥ Items saved</span>
            </div>
        </div>

        <div class="p-5 rounded-xl border border-slate-200/80 bg-white shadow-2xs space-y-1">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Customers With Saved Items</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold text-slate-900">{{ number_format($totalCustomersWithWishlist) }}</span>
                <span class="text-xs text-slate-500">Customers</span>
            </div>
        </div>

        <div class="p-5 rounded-xl border border-slate-200/80 bg-white shadow-2xs space-y-1">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Unique Products in Wishlists</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold text-slate-900">{{ number_format($totalProductsWishlisted) }}</span>
                <span class="text-xs text-slate-500">Products</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.wishlists.index', ['tab' => 'products']) }}"
               class="px-4 py-2 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'products' ? 'bg-slate-900 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                Most Wishlisted Products ({{ $mostWishlisted->total() }})
            </a>
            <a href="{{ route('admin.wishlists.index', ['tab' => 'customers']) }}"
               class="px-4 py-2 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'customers' ? 'bg-slate-900 text-white shadow-2xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                Customer Wishlists ({{ $customerWishlists->total() }})
            </a>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="{{ route('admin.wishlists.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="{{ $tab === 'products' ? 'Search by product name or SKU...' : 'Search by name, email, or phone...' }}"
                       class="w-64 sm:w-80 pl-9 pr-3.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </div>
            <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                Search
            </button>
            @if($search !== '')
                <a href="{{ route('admin.wishlists.index', ['tab' => $tab]) }}" class="text-xs text-slate-500 hover:text-slate-800">Clear</a>
            @endif
        </form>
    </div>

    @if($tab === 'products')
        <!-- TAB 1: MOST WISHLISTED PRODUCTS -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            @if($mostWishlisted->isEmpty())
                <div class="p-12 text-center text-slate-500 text-xs">
                    No wishlisted products found.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4">Product</th>
                                <th class="py-3 px-4">Category</th>
                                <th class="py-3 px-4">Price</th>
                                <th class="py-3 px-4 text-center">Wishlist Saves</th>
                                <th class="py-3 px-4">Stock on Hand</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($mostWishlisted as $product)
                                @php
                                    $totalStock = $product->variants->flatMap->inventoryLevels->sum('on_hand') - $product->variants->flatMap->inventoryLevels->sum('reserved');
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-14 rounded-md bg-slate-100 border border-slate-200 overflow-hidden shrink-0">
                                                @if($product->primary_image_url)
                                                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs">No Img</div>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-slate-900 hover:text-amber-600 transition-colors line-clamp-1">
                                                    {{ $product->name }}
                                                </a>
                                                <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                                                    <span>SKU: <code class="font-mono">{{ $product->sku ?: '—' }}</code></span>
                                                    <span>&bull;</span>
                                                    <span>{{ $product->variants->count() }} Variants</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 font-medium text-slate-700">
                                        {{ $product->category?->name ?? 'Unassigned' }}
                                    </td>
                                    <td class="py-3 px-4 font-semibold text-slate-900">
                                        {{ $product->formatted_price }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            <svg class="w-3.5 h-3.5 fill-rose-500 text-rose-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                            </svg>
                                            {{ $product->wishlist_items_count }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($totalStock > 5)
                                            <span class="text-emerald-700 font-semibold">{{ $totalStock }} in stock</span>
                                        @elseif($totalStock > 0)
                                            <span class="text-amber-700 font-semibold">Low stock ({{ $totalStock }})</span>
                                        @else
                                            <span class="text-rose-600 font-semibold">Out of Stock</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($product->status->value === 'ACTIVE')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Active
                                            </span>
                                        @elseif($product->status->value === 'DRAFT')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                Draft
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                Archived
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right space-x-2">
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                           class="inline-flex items-center px-2.5 py-1 rounded-md bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold text-[11px] shadow-2xs transition-colors">
                                            Edit
                                        </a>
                                        <a href="{{ url('/product/' . $product->slug) }}" target="_blank"
                                           class="inline-flex items-center px-2.5 py-1 rounded-md bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold text-[11px] shadow-2xs transition-colors">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($mostWishlisted->hasPages())
                    <div class="p-4 border-t border-slate-200">
                        {{ $mostWishlisted->appends(['tab' => 'products', 'search' => $search])->links() }}
                    </div>
                @endif
            @endif
        </div>
    @else
        <!-- TAB 2: CUSTOMER WISHLISTS -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            @if($customerWishlists->isEmpty())
                <div class="p-12 text-center text-slate-500 text-xs">
                    No customer wishlists found.
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($customerWishlists as $customer)
                        <div class="p-5 space-y-3 hover:bg-slate-50/40 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-amber-100 border border-amber-300 text-amber-800 font-bold flex items-center justify-center text-xs shrink-0">
                                        {{ strtoupper(substr($customer->name ?: 'C', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.customers.show', $customer) }}" class="font-bold text-slate-900 hover:text-amber-600 transition-colors text-sm">
                                                {{ $customer->name ?: 'Customer #' . $customer->id }}
                                            </a>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                {{ $customer->wishlist_items_count }} {{ Str::plural('saved item', $customer->wishlist_items_count) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 text-xs text-slate-500 mt-0.5">
                                            <span>{{ $customer->email ?: 'No email' }}</span>
                                            @if($customer->phone)
                                                <span>&bull;</span>
                                                <span>{{ $customer->phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <a href="{{ route('admin.customers.show', $customer) }}"
                                   class="inline-flex items-center gap-1.5 text-xs text-amber-700 hover:underline font-semibold self-start sm:self-center">
                                    View Customer Profile &rsaquo;
                                </a>
                            </div>

                            <!-- Horizontal List of Customer's Saved Items -->
                            <div class="flex items-center gap-3 overflow-x-auto pb-2">
                                @foreach($customer->wishlistItems as $item)
                                    @if($item->product)
                                        <div class="flex items-center gap-2.5 p-2 rounded-lg border border-slate-200 bg-white shadow-2xs shrink-0 max-w-xs">
                                            <div class="w-10 h-12 rounded bg-slate-100 overflow-hidden shrink-0 border border-slate-200">
                                                @if($item->product->primary_image_url)
                                                    <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-[9px] text-slate-400">No Img</div>
                                                @endif
                                            </div>
                                            <div class="min-w-0 pr-1">
                                                <a href="{{ route('admin.products.edit', $item->product) }}" class="text-xs font-semibold text-slate-900 hover:text-amber-600 truncate block max-w-[10rem]">
                                                    {{ $item->product->name }}
                                                </a>
                                                <div class="flex items-center gap-2 text-[10px] text-slate-500 mt-0.5">
                                                    <span class="font-semibold text-slate-800">{{ $item->product->formatted_price }}</span>
                                                    <span>&bull;</span>
                                                    <span>Added {{ $item->created_at ? $item->created_at->diffForHumans() : 'Recently' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($customerWishlists->hasPages())
                    <div class="p-4 border-t border-slate-200">
                        {{ $customerWishlists->appends(['tab' => 'customers', 'search' => $search])->links() }}
                    </div>
                @endif
            @endif
        </div>
    @endif
</div>
@endsection
