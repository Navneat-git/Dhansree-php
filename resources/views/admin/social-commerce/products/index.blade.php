@extends('layouts.admin')

@section('title', 'Product Library')
@section('page_title', 'Supplier Product Library')

@section('header_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.social-commerce.inbox.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-900 text-white hover:bg-slate-800 shadow-xs transition-colors">
        <svg class="size-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5" />
        </svg>
        Media Inbox
    </a>
    <a href="{{ route('admin.social-commerce.products.create') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 text-slate-950 hover:bg-amber-400 shadow-xs transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        New Supplier Product
    </a>
</div>
@endsection

@section('content')
<div class="space-y-5">

    <!-- Filters & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.social-commerce.products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Product name, code, fabric..."
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
            </div>

            <!-- Category -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Category</label>
                <select name="category_name" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category_name') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Availability Status -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Availability</label>
                <select name="availability_status" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                    <option value="">All Statuses</option>
                    <option value="NEW" {{ request('availability_status') === 'NEW' ? 'selected' : '' }}>New</option>
                    <option value="AVAILABLE" {{ request('availability_status') === 'AVAILABLE' ? 'selected' : '' }}>Available</option>
                    <option value="TEMPORARILY_UNAVAILABLE" {{ request('availability_status') === 'TEMPORARILY_UNAVAILABLE' ? 'selected' : '' }}>Temporarily Unavailable</option>
                    <option value="OUT_OF_STOCK" {{ request('availability_status') === 'OUT_OF_STOCK' ? 'selected' : '' }}>Out of Stock</option>
                    <option value="ARCHIVED" {{ request('availability_status') === 'ARCHIVED' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <!-- Supplier -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Supplier</label>
                <select name="supplier_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'category_name', 'availability_status', 'supplier_id']))
                    <a href="{{ route('admin.social-commerce.products.index') }}" class="p-1.5 rounded-lg border border-slate-300 text-slate-500 hover:text-slate-900 hover:bg-slate-50" title="Reset Filters">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Product Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        @if($products->isEmpty())
        <div class="p-12 text-center text-slate-500">
            <svg class="size-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
            <h3 class="text-sm font-semibold text-slate-900">No supplier products found</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                Select images from the Supplier Media Inbox to create a product, or add one manually.
            </p>
            <div class="mt-4 flex items-center justify-center gap-3">
                <a href="{{ route('admin.social-commerce.inbox.index') }}" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                    Go to Media Inbox
                </a>
                <a href="{{ route('admin.social-commerce.products.create') }}" class="px-4 py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-semibold hover:bg-amber-400">
                    Create Product Manually
                </a>
            </div>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Product</th>
                        <th class="py-3 px-4">Category / Fabric</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4">Pricing</th>
                        <th class="py-3 px-4">Availability</th>
                        <th class="py-3 px-4">Web Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($products as $product)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <!-- Product thumbnail & name -->
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3 min-w-[220px]">
                                <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}"
                                     class="size-12 rounded-xl object-cover bg-slate-100 shrink-0 border border-slate-200">
                                <div>
                                    <a href="{{ route('admin.social-commerce.products.show', $product) }}"
                                       class="font-semibold text-slate-900 hover:text-amber-600 block leading-snug">
                                        {{ $product->name }}
                                    </a>
                                    <p class="font-mono text-[10px] text-slate-400 mt-0.5">
                                        {{ $product->product_code }}
                                        @if($product->mediaAssets->count() > 0)
                                            &bull; {{ $product->mediaAssets->count() }} media
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Category & Fabric -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <p class="font-medium text-slate-900">{{ $product->category_name }}</p>
                            <p class="text-[11px] text-slate-500">{{ $product->fabric ?: '—' }}</p>
                        </td>

                        <!-- Supplier -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <p class="font-medium text-slate-900">{{ $product->supplier?->name ?? 'WhatsApp Group' }}</p>
                            @if($product->supplier_product_code)
                                <p class="text-[10px] text-slate-400 font-mono">Code: {{ $product->supplier_product_code }}</p>
                            @endif
                        </td>

                        <!-- Pricing -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($product->special_offer_price_rupees)
                                <p class="font-bold text-emerald-600">₹{{ number_format($product->special_offer_price_rupees, 0) }}</p>
                                @if($product->suggested_selling_price_rupees)
                                    <p class="text-[10px] text-slate-400 line-through">₹{{ number_format($product->suggested_selling_price_rupees, 0) }}</p>
                                @endif
                            @elseif($product->suggested_selling_price_rupees)
                                <p class="font-bold text-slate-900">₹{{ number_format($product->suggested_selling_price_rupees, 0) }}</p>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                            @if($product->purchase_price_rupees)
                                <p class="text-[10px] text-slate-400">Cost: ₹{{ number_format($product->purchase_price_rupees, 0) }}</p>
                            @endif
                        </td>

                        <!-- Availability Status -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            @php
                                $badgeClass = match($product->availability_status) {
                                    'AVAILABLE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'NEW' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'TEMPORARILY_UNAVAILABLE' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'OUT_OF_STOCK' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                {{ str_replace('_', ' ', $product->availability_status) }}
                            </span>
                            @if($product->last_availability_confirmed_at)
                                <p class="text-[9px] text-slate-400 mt-0.5">
                                    {{ $product->last_availability_confirmed_at->diffForHumans() }}
                                </p>
                            @endif
                        </td>

                        <!-- Web Promotion Status -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($product->is_promoted_to_web)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="size-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    On Website
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-600">
                                    Social Only
                                </span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <!-- Status Card Generator -->
                                <a href="{{ route('admin.social-commerce.whatsapp-status.index', ['product_id' => $product->id]) }}"
                                   title="Create WhatsApp Status Card"
                                   class="p-1.5 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition-colors">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                    </svg>
                                </a>

                                <!-- View Details -->
                                <a href="{{ route('admin.social-commerce.products.show', $product) }}"
                                   class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                                    View
                                </a>

                                <!-- Edit -->
                                <a href="{{ route('admin.social-commerce.products.edit', $product) }}"
                                   class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-500/10 text-amber-700 hover:bg-amber-500/20 transition-colors">
                                    Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $products->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
