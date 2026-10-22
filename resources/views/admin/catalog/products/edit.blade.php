@extends('layouts.admin')

@section('title', 'Manage Product - ' . $product->name)

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{ activeTab: 'details' }">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-zinc-400">
        <a href="{{ route('admin.products.index') }}" class="hover:text-amber-400 transition-colors">Products</a>
        <span>&rsaquo;</span>
        <span class="text-zinc-200 font-medium truncate max-w-sm">{{ $product->name }}</span>
    </div>

    <!-- Header & Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-zinc-800 pb-5">
        <div class="space-y-1.5 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-bold tracking-tight text-white truncate">{{ $product->name }}</h1>
                @if($product->status->value === 'ACTIVE')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-950/70 text-emerald-400 border border-emerald-800/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Active
                    </span>
                @elseif($product->status->value === 'DRAFT')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-950/70 text-amber-300 border border-amber-800/60">
                        Draft
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-zinc-800 text-zinc-400 border border-zinc-700">
                        Archived
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-3 text-xs text-zinc-400 font-mono flex-wrap">
                <span>Category: <strong class="text-zinc-300">{{ $product->category?->name ?? 'Unassigned' }}</strong></span>
                <span>&bull;</span>
                <span>Slug: <a href="{{ url('/product/' . $product->slug) }}" target="_blank" class="text-amber-400/90 hover:underline">/product/{{ $product->slug }}</a></span>
                <span>&bull;</span>
                <span>{{ $product->variants->count() }} Variants</span>
                <span>&bull;</span>
                <span>{{ $product->images->count() }} Images</span>
            </div>
        </div>

        <!-- Publish / Lifecycle Controls -->
        <div class="flex items-center gap-2 shrink-0">
            @if($product->status->value === 'DRAFT')
                <form method="POST" action="{{ route('admin.products.publish', $product) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs shadow-lg shadow-emerald-950/40 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Publish Product
                    </button>
                </form>
            @elseif($product->status->value === 'ACTIVE')
                <form method="POST" action="{{ route('admin.products.unpublish', $product) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white border border-zinc-700 text-xs font-medium transition-colors">
                        Unpublish (Revert to Draft)
                    </button>
                </form>
            @endif

            <a href="{{ url('/product/' . $product->slug) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-zinc-800/80 hover:bg-zinc-700 text-zinc-300 hover:text-white border border-zinc-700 text-xs font-medium transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Preview Store
            </a>
        </div>
    </div>

    <!-- Publish Issues Checklist Banner (if any) -->
    @if(!empty($publishIssues))
        <div class="rounded-xl border border-amber-900/60 bg-amber-950/30 p-4 space-y-2">
            <div class="flex items-center gap-2 text-amber-300 font-semibold text-xs">
                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Publish Requirements Checklist ({{ count($publishIssues) }} pending):
            </div>
            <ul class="text-xs text-amber-200/80 space-y-1 list-disc pl-5">
                @foreach($publishIssues as $issue)
                    <li>{{ $issue['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-zinc-800 overflow-x-auto text-xs font-semibold">
        <button type="button" @click="activeTab = 'details'"
                :class="activeTab === 'details' ? 'border-amber-500 text-amber-300 bg-zinc-800/40' : 'border-transparent text-zinc-400 hover:text-zinc-200'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2">
            Details & SEO
        </button>
        <button type="button" @click="activeTab = 'variants'"
                :class="activeTab === 'variants' ? 'border-amber-500 text-amber-300 bg-zinc-800/40' : 'border-transparent text-zinc-400 hover:text-zinc-200'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2">
            Variants & Stock
            <span class="px-1.5 py-0.2 rounded-full bg-zinc-800 text-[10px] text-zinc-300">{{ $product->variants->count() }}</span>
        </button>
        <button type="button" @click="activeTab = 'media'"
                :class="activeTab === 'media' ? 'border-amber-500 text-amber-300 bg-zinc-800/40' : 'border-transparent text-zinc-400 hover:text-zinc-200'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2">
            Media & Images
            <span class="px-1.5 py-0.2 rounded-full bg-zinc-800 text-[10px] text-zinc-300">{{ $product->images->count() }}</span>
        </button>
        <button type="button" @click="activeTab = 'attributes'"
                :class="activeTab === 'attributes' ? 'border-amber-500 text-amber-300 bg-zinc-800/40' : 'border-transparent text-zinc-400 hover:text-zinc-200'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2">
            Specifications
            <span class="px-1.5 py-0.2 rounded-full bg-zinc-800 text-[10px] text-zinc-300">{{ $product->productAttributes->count() }}</span>
        </button>
        <button type="button" @click="activeTab = 'danger'"
                :class="activeTab === 'danger' ? 'border-red-500 text-red-300 bg-zinc-800/40' : 'border-transparent text-zinc-400 hover:text-zinc-200'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2 ml-auto text-red-400">
            Danger Zone
        </button>
    </div>

    <!-- TAB 1: DETAILS & SEO -->
    <div x-show="activeTab === 'details'" class="space-y-6">
        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="version" value="{{ $product->version }}">

            <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-5">
                <h2 class="text-base font-semibold text-white border-b border-zinc-800/60 pb-3">Product Information</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">Product Name <span class="text-red-400">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required maxlength="150"
                               class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70 focus:ring-1 focus:ring-amber-500/50">
                        @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">URL Slug (301 redirect created on change)</label>
                        <div class="flex items-center rounded-lg border border-zinc-700/80 bg-zinc-900 overflow-hidden focus-within:border-amber-500/70 focus-within:ring-1 focus-within:ring-amber-500/50">
                            <span class="px-3 text-xs text-zinc-500 font-mono select-none">/product/</span>
                            <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" maxlength="100"
                                   class="w-full py-2.5 pr-3 bg-transparent text-sm text-white font-mono focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">Category</label>
                        <select name="category_id" class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70">
                            <option value="">Select Category...</option>
                            @foreach($categoryOptions as $opt)
                                <option value="{{ $opt['id'] }}" {{ old('category_id', $product->category_id) == $opt['id'] ? 'selected' : '' }}>
                                    {{ str_repeat('— ', $opt['depth'] - 1) }}{{ $opt['name'] }} ({{ $opt['label'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">Brand</label>
                        <input type="text" name="brand" value="{{ old('brand', $product->brand) }}" maxlength="80"
                               class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">HSN Code</label>
                        <input type="text" name="hsn_code" value="{{ old('hsn_code', $product->hsn_code) }}" maxlength="8"
                               class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm font-mono text-white focus:outline-none focus:border-amber-500/70">
                        <p class="text-[11px] text-zinc-500 mt-1">4, 6, or 8 digits. Required on GST tax invoices.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">GST Rate</label>
                        <select name="gst_rate_bps" class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70">
                            <option value="">Inherit Store Default ({{ ($store->default_gst_rate_bps ?? 1800) / 100 }}%)</option>
                            <option value="500" {{ old('gst_rate_bps', $product->gst_rate_bps) === 500 ? 'selected' : '' }}>5% (Apparel &le; ₹1000)</option>
                            <option value="1200" {{ old('gst_rate_bps', $product->gst_rate_bps) === 1200 ? 'selected' : '' }}>12% (Apparel &gt; ₹1000)</option>
                            <option value="1800" {{ old('gst_rate_bps', $product->gst_rate_bps) === 1800 ? 'selected' : '' }}>18% (Standard / Gadgets)</option>
                            <option value="2800" {{ old('gst_rate_bps', $product->gst_rate_bps) === 2800 ? 'selected' : '' }}>28% (Luxury)</option>
                            <option value="0" {{ old('gst_rate_bps', $product->gst_rate_bps) === 0 ? 'selected' : '' }}>0% (Exempt)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">Short Description</label>
                        <input type="text" name="short_description" value="{{ old('short_description', $product->short_description) }}" maxlength="300"
                               class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-zinc-300 mb-1.5">Full Description</label>
                        <textarea name="description" rows="5"
                                  class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- SEO Card -->
            <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-white border-b border-zinc-800/60 pb-3">Search Engine Optimization (SEO)</h2>

                <div class="p-4 rounded-lg bg-zinc-950/80 border border-zinc-800/80 font-sans space-y-1">
                    <div class="text-xs text-zinc-400 font-mono">https://dhansree.com &rsaquo; product &rsaquo; <span>{{ $product->slug }}</span></div>
                    <div class="text-base text-blue-400 font-medium hover:underline">{{ $product->seo_title ?: $product->name . ' | Dhansree' }}</div>
                    <div class="text-xs text-zinc-400 line-clamp-2">{{ $product->seo_description ?: Str::limit($product->short_description ?: $product->description, 160) }}</div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1">SEO Title</label>
                        <input type="text" name="seo_title" value="{{ old('seo_title', $product->seo_title) }}" maxlength="120"
                               class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1">SEO Meta Description</label>
                        <textarea name="seo_description" rows="2" maxlength="320"
                                  class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70">{{ old('seo_description', $product->seo_description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-medium text-sm shadow-lg shadow-amber-950/40 transition-all">
                    Save Details
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 2: VARIANTS & INVENTORY -->
    <div x-show="activeTab === 'variants'" class="space-y-6">
        <!-- Options Configurator Card -->
        <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-800/60 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-white">Product Options</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Define options like Size, Color, or Material to generate multi-variant combinations.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.products.sync-options', $product) }}" class="space-y-4">
                @csrf
                <div class="space-y-3" id="options-container">
                    @forelse($product->options as $index => $option)
                        <div class="flex items-center gap-3 p-3 bg-zinc-900/80 rounded-lg border border-zinc-800 option-row">
                            <div class="w-1/3">
                                <label class="block text-[11px] font-medium text-zinc-400 mb-1">Option Name</label>
                                <input type="text" name="options[{{ $index }}][name]" value="{{ $option->name }}" placeholder="e.g. Size" required
                                       class="w-full px-3 py-1.5 bg-zinc-950 border border-zinc-700/80 rounded text-xs text-white">
                            </div>
                            <div class="flex-1">
                                <label class="block text-[11px] font-medium text-zinc-400 mb-1">Values (comma separated)</label>
                                <input type="text" name="options[{{ $index }}][values]" value="{{ $option->values->pluck('value')->implode(', ') }}" placeholder="S, M, L, XL" required
                                       class="w-full px-3 py-1.5 bg-zinc-950 border border-zinc-700/80 rounded text-xs text-white">
                            </div>
                        </div>
                    @empty
                        <div class="p-3 bg-zinc-900/80 rounded-lg border border-zinc-800 option-row">
                            <div class="flex items-center gap-3">
                                <div class="w-1/3">
                                    <label class="block text-[11px] font-medium text-zinc-400 mb-1">Option Name</label>
                                    <input type="text" name="options[0][name]" value="Size" placeholder="e.g. Size"
                                           class="w-full px-3 py-1.5 bg-zinc-950 border border-zinc-700/80 rounded text-xs text-white">
                                </div>
                                <div class="flex-1">
                                    <label class="block text-[11px] font-medium text-zinc-400 mb-1">Values (comma separated)</label>
                                    <input type="text" name="options[0][values]" value="S, M, L, XL" placeholder="S, M, L, XL"
                                           class="w-full px-3 py-1.5 bg-zinc-950 border border-zinc-700/80 rounded text-xs text-white">
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-medium transition-colors">
                        Save Options
                    </button>
                </div>
            </form>

            @if($product->options->isNotEmpty())
                <div class="border-t border-zinc-800/80 pt-4">
                    <form method="POST" action="{{ route('admin.products.generate-variants', $product) }}" class="flex items-end gap-3 flex-wrap">
                        @csrf
                        <div>
                            <label class="block text-[11px] text-zinc-400 mb-1">Default Price (₹)</label>
                            <input type="number" step="0.01" name="price_rupees" value="{{ $product->min_price_rupees ?? 699 }}" required class="w-28 px-3 py-1.5 bg-zinc-950 border border-zinc-700 rounded text-xs text-white">
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-400 mb-1">Default MRP (₹)</label>
                            <input type="number" step="0.01" name="mrp_rupees" value="{{ $product->mrp_rupees ?? 1299 }}" class="w-28 px-3 py-1.5 bg-zinc-950 border border-zinc-700 rounded text-xs text-white">
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-medium shadow transition-colors">
                            Generate Missing Variant Matrix
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Variants Table Card -->
        <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-800/80 bg-zinc-900/90 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">Live Variants ({{ $product->variants->count() }})</h3>
                <span class="text-xs text-zinc-500">Each variant tracks separate stock on-hand, prices and SKU</span>
            </div>

            <div class="divide-y divide-zinc-800/60">
                @foreach($product->variants as $variant)
                    <div class="p-4 sm:p-5 hover:bg-zinc-800/20 transition-colors">
                        <form method="POST" action="{{ route('admin.variants.update', $variant) }}" class="grid grid-cols-2 sm:grid-cols-6 lg:grid-cols-8 gap-3 items-center">
                            @csrf
                            @method('PUT')

                            <!-- Variant Option Combo Title -->
                            <div class="col-span-2">
                                <span class="text-xs font-semibold text-white">
                                    @php
                                        $combo = $variant->optionValues->pluck('value')->implode(' / ');
                                    @endphp
                                    {{ $combo ?: 'Default' }}
                                </span>
                                @if($variant->archived_at)
                                    <span class="ml-2 text-[10px] text-red-400 bg-red-950 px-1.5 py-0.5 rounded">Archived</span>
                                @endif
                                <div class="text-[11px] text-zinc-500 font-mono mt-0.5">{{ $variant->sku }}</div>
                            </div>

                            <!-- SKU Input -->
                            <div>
                                <label class="block text-[10px] text-zinc-500 uppercase">SKU</label>
                                <input type="text" name="sku" value="{{ $variant->sku }}" required
                                       class="w-full px-2 py-1 bg-zinc-950 border border-zinc-700 rounded text-xs text-white font-mono uppercase">
                            </div>

                            <!-- Price Input -->
                            <div>
                                <label class="block text-[10px] text-zinc-500 uppercase">Price (₹)</label>
                                <input type="number" step="0.01" name="price_rupees" value="{{ $variant->price_rupees }}" required
                                       class="w-full px-2 py-1 bg-zinc-950 border border-zinc-700 rounded text-xs text-white">
                            </div>

                            <!-- MRP Input -->
                            <div>
                                <label class="block text-[10px] text-zinc-500 uppercase">MRP (₹)</label>
                                <input type="number" step="0.01" name="mrp_rupees" value="{{ $variant->mrp_rupees }}"
                                       class="w-full px-2 py-1 bg-zinc-950 border border-zinc-700 rounded text-xs text-white">
                            </div>

                            <!-- Stock Input -->
                            <div>
                                <label class="block text-[10px] text-zinc-500 uppercase">Stock Qty</label>
                                <input type="number" min="0" name="stock_quantity" value="{{ $variant->stock_on_hand }}"
                                       class="w-full px-2 py-1 bg-zinc-950 border border-zinc-700 rounded text-xs text-white font-medium text-emerald-400">
                            </div>

                            <!-- Weight Input -->
                            <div>
                                <label class="block text-[10px] text-zinc-500 uppercase">Weight (g)</label>
                                <input type="number" min="0" name="weight_grams" value="{{ $variant->weight_grams ?? 250 }}"
                                       class="w-full px-2 py-1 bg-zinc-950 border border-zinc-700 rounded text-xs text-white">
                            </div>

                            <!-- Actions -->
                            <div class="col-span-2 sm:col-span-6 lg:col-span-1 flex items-center justify-end gap-2 pt-2 lg:pt-0">
                                <button type="submit" class="px-3 py-1 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs rounded transition-colors" title="Save changes">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- TAB 3: MEDIA & IMAGES -->
    <div x-show="activeTab === 'media'" class="space-y-6">
        <!-- Upload Box -->
        <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
            <h2 class="text-base font-semibold text-white border-b border-zinc-800/60 pb-3">Upload Product Image</h2>

            <form method="POST" action="{{ route('admin.products.upload-image', $product) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1">Image File (JPEG, PNG, WebP &bull; Max 10MB)</label>
                        <input type="file" name="image" required accept="image/jpeg,image/png,image/webp"
                               class="block w-full text-xs text-zinc-400 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-zinc-800 file:text-zinc-200 hover:file:bg-zinc-700">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-300 mb-1">Alt Text (Accessibility & Google Image Search)</label>
                        <input type="text" name="alt_text" value="{{ $product->name }}" maxlength="160"
                               class="w-full px-3 py-1.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-xs text-white">
                    </div>
                </div>

                <!-- Optional Variant Pinning -->
                @if($product->variants->count() > 1)
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1">Pin Image to Variant / Color (Optional)</label>
                        <div class="flex items-center gap-3 flex-wrap">
                            @foreach($product->variants as $var)
                                <label class="inline-flex items-center gap-1.5 text-xs text-zinc-300 bg-zinc-950 px-2.5 py-1 rounded border border-zinc-800">
                                    <input type="checkbox" name="variant_ids[]" value="{{ $var->id }}" class="rounded bg-zinc-900 text-amber-500">
                                    <span>{{ $var->sku }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <button type="submit" class="px-5 py-2.5 rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-medium text-xs shadow transition-all">
                    Upload & Process WebP
                </button>
            </form>
        </div>

        <!-- Images Grid -->
        <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
            <h2 class="text-base font-semibold text-white border-b border-zinc-800/60 pb-3 flex items-center justify-between">
                <span>Product Gallery ({{ $product->images->count() }})</span>
                <span class="text-xs font-normal text-zinc-400">Position 0 is the primary storefront photo</span>
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @forelse($product->images as $img)
                    <div class="rounded-lg border border-zinc-800 bg-zinc-950 p-2.5 space-y-2 relative group">
                        <div class="aspect-square rounded-md overflow-hidden bg-zinc-900 relative">
                            <img src="{{ $img->url }}" alt="{{ $img->alt_text }}" class="w-full h-full object-cover">
                            @if($img->is_primary)
                                <span class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-black shadow">
                                    PRIMARY
                                </span>
                            @endif
                        </div>

                        <div class="text-[11px] text-zinc-400 truncate font-mono">
                            {{ $img->width }} &times; {{ $img->height }} px ({{ round($img->byte_size / 1024) }} KB)
                        </div>

                        <div class="flex items-center justify-between gap-1 pt-1">
                            @if(!$img->is_primary)
                                <form method="POST" action="{{ route('admin.images.set-primary', $img) }}">
                                    @csrf
                                    <button type="submit" class="text-[11px] text-amber-400 hover:underline">
                                        Make Primary
                                    </button>
                                </form>
                            @else
                                <span class="text-[11px] text-zinc-500">Main photo</span>
                            @endif

                            <form method="POST" action="{{ route('admin.images.delete', $img) }}" onsubmit="return confirm('Delete this image?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] text-red-400 hover:underline">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-zinc-500">
                        No images uploaded yet. Products require at least 1 image to be published.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- TAB 4: SPECIFICATIONS -->
    <div x-show="activeTab === 'attributes'" class="space-y-6">
        <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-800/60 pb-3">
                <div>
                    <h2 class="text-base font-semibold text-white">Product Specifications</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Key-value attributes shown on the product details page (e.g. Fabric, Pattern, Occasion).</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.products.update-attributes', $product) }}" class="space-y-4">
                @csrf
                <div class="space-y-3" id="specs-container">
                    @php
                        $attrs = $product->productAttributes;
                        if ($attrs->isEmpty()) {
                            $attrs = collect([
                                (object) ['label' => 'Fabric', 'value' => 'Pure Cotton'],
                                (object) ['label' => 'Pattern', 'value' => 'Handblock Print'],
                                (object) ['label' => 'Occasion', 'value' => 'Ethnic Casual / Festive'],
                                (object) ['label' => 'Wash Care', 'value' => 'Hand Wash Separately'],
                            ]);
                        }
                    @endphp

                    @foreach($attrs as $idx => $attr)
                        <div class="flex items-center gap-3 p-2 bg-zinc-950/70 rounded-lg border border-zinc-800">
                            <div class="w-1/3">
                                <input type="text" name="attributes[{{ $idx }}][label]" value="{{ $attr->label }}" placeholder="Label (e.g. Fabric)"
                                       class="w-full px-3 py-1.5 bg-zinc-900 border border-zinc-700/80 rounded text-xs text-white">
                            </div>
                            <div class="flex-1">
                                <input type="text" name="attributes[{{ $idx }}][value]" value="{{ $attr->value }}" placeholder="Value (e.g. 100% Cotton)"
                                       class="w-full px-3 py-1.5 bg-zinc-900 border border-zinc-700/80 rounded text-xs text-white">
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="px-5 py-2.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-medium transition-colors">
                    Save Specifications
                </button>
            </form>
        </div>
    </div>

    <!-- TAB 5: DANGER ZONE -->
    <div x-show="activeTab === 'danger'" class="space-y-6">
        <div class="rounded-xl border border-red-900/40 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
            <h2 class="text-base font-semibold text-red-400 border-b border-red-900/30 pb-3">Danger Zone</h2>

            @if($product->status->value === 'ARCHIVED')
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-white">Restore Product</h3>
                        <p class="text-xs text-zinc-400">Restores this archived product to Draft status.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.products.restore', $product) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-950/60 hover:bg-emerald-900 text-emerald-400 border border-emerald-800 text-xs font-medium transition-colors">
                            Restore to Draft
                        </button>
                    </form>
                </div>
            @else
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-white">Archive Product</h3>
                        <p class="text-xs text-zinc-400">
                            Retires the product from catalog. Its URL and SKUs stay reserved and historic order records remain intact.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.products.archive', $product) }}" onsubmit="return confirm('Archive this product?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-400 border border-red-800 text-xs font-medium transition-colors">
                            Archive Product
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
