@extends('layouts.admin')

@section('title', 'Manage Product - ' . $product->name)

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{ 
    activeTab: '{{ session('active_tab', request()->query('tab')) }}' || (new URLSearchParams(window.location.search).get('tab')) || 'details',
    setTab(t) {
        this.activeTab = t;
        const url = new URL(window.location.href);
        url.searchParams.set('tab', t);
        window.history.replaceState({}, '', url.toString());
    }
}" x-init="$watch('activeTab', val => {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', val);
    window.history.replaceState({}, '', url.toString());
})">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('admin.products.index') }}" class="hover:text-amber-600 transition-colors">Products</a>
        <span>&rsaquo;</span>
        <span class="text-slate-800 font-medium truncate max-w-sm">{{ $product->name }}</span>
    </div>

    <!-- Header & Action Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-200 pb-5">
        <div class="space-y-1.5 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 truncate">{{ $product->name }}</h1>
                @if($product->status->value === 'ACTIVE')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Active
                    </span>
                @elseif($product->status->value === 'DRAFT')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                        Draft
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                        Archived
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-3 text-xs text-slate-500 flex-wrap">
                <span>Category: <strong class="text-slate-700">{{ $product->category?->name ?? 'Unassigned' }}</strong></span>
                <span>&bull;</span>
                <span>Slug: <a href="{{ url('/product/' . $product->slug) }}" target="_blank" class="text-amber-700 hover:underline font-mono">/product/{{ $product->slug }}</a></span>
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
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Publish Product
                    </button>
                </form>
            @elseif($product->status->value === 'ACTIVE')
                <form method="POST" action="{{ route('admin.products.unpublish', $product) }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold transition-colors shadow-2xs">
                        Unpublish (Revert to Draft)
                    </button>
                </form>
            @endif

            <a href="{{ url('/product/' . $product->slug) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-semibold transition-colors shadow-2xs">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Preview Store
            </a>
        </div>
    </div>

    <!-- Publish Issues Checklist Banner (if any) -->
    @if(!empty($publishIssues))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 space-y-2">
            <div class="flex items-center gap-2 text-amber-900 font-bold text-xs">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Publish Requirements Checklist ({{ count($publishIssues) }} pending):
            </div>
            <ul class="text-xs text-amber-800 space-y-1 list-disc pl-5">
                @foreach($publishIssues as $issue)
                    <li>{{ $issue['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto text-xs font-semibold">
        <button type="button" @click="setTab('details')"
                :class="activeTab === 'details' ? 'border-amber-600 text-amber-700 bg-amber-50/60' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2 rounded-t-md">
            Details & SEO
        </button>
        <button type="button" @click="setTab('variants')"
                :class="activeTab === 'variants' ? 'border-amber-600 text-amber-700 bg-amber-50/60' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2 rounded-t-md">
            Variants & Stock
            <span class="px-1.5 py-0.2 rounded-full bg-slate-200 text-[10px] text-slate-700">{{ $product->variants->count() }}</span>
        </button>
        <button type="button" @click="setTab('media')"
                :class="activeTab === 'media' ? 'border-amber-600 text-amber-700 bg-amber-50/60' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2 rounded-t-md">
            Media & Images
            <span class="px-1.5 py-0.2 rounded-full bg-slate-200 text-[10px] text-slate-700">{{ $product->images->count() }}</span>
        </button>
        <button type="button" @click="setTab('attributes')"
                :class="activeTab === 'attributes' ? 'border-amber-600 text-amber-700 bg-amber-50/60' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2 rounded-t-md">
            Specifications
            <span class="px-1.5 py-0.2 rounded-full bg-slate-200 text-[10px] text-slate-700">{{ $product->productAttributes->count() }}</span>
        </button>
        <button type="button" @click="setTab('faqs')"
                :class="activeTab === 'faqs' ? 'border-amber-600 text-amber-700 bg-amber-50/60' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2 rounded-t-md">
            FAQs
            <span class="px-1.5 py-0.2 rounded-full bg-slate-200 text-[10px] text-slate-700">{{ ($productContent?->faq_mode === 'CUSTOM') ? $customFaqs->count() : ($productContent?->faq_mode === 'NONE' ? 'Off' : 'Default') }}</span>
        </button>
        <button type="button" @click="setTab('quick-links')"
                :class="activeTab === 'quick-links' ? 'border-amber-600 text-amber-700 bg-amber-50/60' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2 rounded-t-md">
            Quick Links
            <span class="px-1.5 py-0.2 rounded-full bg-slate-200 text-[10px] text-slate-700">{{ $productContent?->quick_links_mode ?: 'INHERIT' }}</span>
        </button>
        <button type="button" @click="setTab('danger')"
                :class="activeTab === 'danger' ? 'border-rose-600 text-rose-700 bg-rose-50/60' : 'border-transparent text-slate-600 hover:text-rose-600'"
                class="px-4 py-2.5 border-b-2 transition-all flex items-center gap-2 ml-auto text-rose-600 rounded-t-md">
            Danger Zone
        </button>
    </div>

    <!-- TAB 1: DETAILS & SEO -->
    <div x-show="activeTab === 'details'" class="space-y-6">
        <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="version" value="{{ $product->version }}">

            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Product Information</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Product Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required maxlength="150"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                        @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">URL Slug (301 redirect created on change)</label>
                        <div class="flex items-center rounded-lg border border-slate-300 bg-white overflow-hidden focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500 shadow-2xs">
                            <span class="px-3 text-xs text-slate-400 font-mono select-none bg-slate-50 border-r border-slate-200 py-2.5">/product/</span>
                            <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" maxlength="100"
                                   class="w-full py-2.5 px-3 bg-transparent text-sm text-slate-900 font-mono focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Category</label>
                        <select name="category_id" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                            <option value="">Select Category...</option>
                            @foreach($categoryOptions as $opt)
                                <option value="{{ $opt['id'] }}" {{ old('category_id', $product->category_id) == $opt['id'] ? 'selected' : '' }}>
                                    {{ str_repeat('— ', $opt['depth'] - 1) }}{{ $opt['name'] }} ({{ $opt['label'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Brand</label>
                        <input type="text" name="brand" value="{{ old('brand', $product->brand) }}" maxlength="80"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">HSN Code</label>
                        <input type="text" name="hsn_code" value="{{ old('hsn_code', $product->hsn_code) }}" maxlength="8"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm font-mono text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                        <p class="text-[11px] text-slate-500 mt-1">4, 6, or 8 digits. Required on GST tax invoices.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">GST Rate</label>
                        <select name="gst_rate_bps" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                            <option value="">Inherit Store Default ({{ ($store->default_gst_rate_bps ?? 1800) / 100 }}%)</option>
                            <option value="500" {{ old('gst_rate_bps', $product->gst_rate_bps) === 500 ? 'selected' : '' }}>5% (Apparel &le; ₹1000)</option>
                            <option value="1200" {{ old('gst_rate_bps', $product->gst_rate_bps) === 1200 ? 'selected' : '' }}>12% (Apparel &gt; ₹1000)</option>
                            <option value="1800" {{ old('gst_rate_bps', $product->gst_rate_bps) === 1800 ? 'selected' : '' }}>18% (Standard / Gadgets)</option>
                            <option value="2800" {{ old('gst_rate_bps', $product->gst_rate_bps) === 2800 ? 'selected' : '' }}>28% (Luxury)</option>
                            <option value="0" {{ old('gst_rate_bps', $product->gst_rate_bps) === 0 ? 'selected' : '' }}>0% (Exempt)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Short Description</label>
                        <input type="text" name="short_description" value="{{ old('short_description', $product->short_description) }}" maxlength="300"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Description</label>
                        <textarea name="description" rows="5"
                                  class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- SEO Card -->
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Search Engine Optimization (SEO)</h2>

                <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 font-sans space-y-1">
                    <div class="text-xs text-slate-500 font-mono">https://dhansree.com &rsaquo; product &rsaquo; <span>{{ $product->slug }}</span></div>
                    <div class="text-base text-blue-700 font-medium hover:underline">{{ $product->seo_title ?: $product->name . ' | Dhansree' }}</div>
                    <div class="text-xs text-slate-600 line-clamp-2">{{ $product->seo_description ?: Str::limit($product->short_description ?: $product->description, 160) }}</div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">SEO Title</label>
                        <input type="text" name="seo_title" value="{{ old('seo_title', $product->seo_title) }}" maxlength="120"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">SEO Meta Description</label>
                        <textarea name="seo_description" rows="2" maxlength="320"
                                  class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">{{ old('seo_description', $product->seo_description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm shadow-xs transition-all">
                    Save Details
                </button>
            </div>
        </form>
    </div>

    <!-- TAB 2: VARIANTS & INVENTORY -->
    <div x-show="activeTab === 'variants'" class="space-y-6">
        <!-- Options Configurator Card -->
        @php
            $existingOptions = $product->options->map(function($o) {
                return [
                    'name' => (string) $o->name,
                    'values' => (string) $o->values->pluck('value')->implode(', ')
                ];
            })->values()->all();
            if (empty($existingOptions)) {
                $existingOptions = [
                    ['name' => 'Size', 'values' => 'S, M, L, XL']
                ];
            }
        @endphp
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4" x-data="{
            options: {{ json_encode($existingOptions) }},
            addOption(name = '', values = '') {
                if (this.options.length >= 3) {
                    alert('Maximum 3 option types allowed per product (e.g. Size, Color, Style).');
                    return;
                }
                this.options.push({ name: name, values: values });
            },
            removeOption(index) {
                this.options.splice(index, 1);
            }
        }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200/80 pb-3 gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Product Option Dimensions</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Define option types (e.g. Size, Color, Storage, Model) and values to create multi-variant matrices.</p>
                </div>
                <button type="button" @click="addOption('', '')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition-all shadow-2xs self-start sm:self-auto">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Option Type
                </button>
            </div>

            <!-- Quick option suggestions -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Quick Suggestions:</span>
                <button type="button" @click="addOption('Size', 'S, M, L, XL, XXL')" class="px-2 py-0.5 rounded text-[11px] bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-900 font-medium border border-slate-200">+ Size</button>
                <button type="button" @click="addOption('Color', 'Black, White, Blue, Red')" class="px-2 py-0.5 rounded text-[11px] bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-900 font-medium border border-slate-200">+ Color</button>
                <button type="button" @click="addOption('Material', 'Leather, Fabric, Silicone')" class="px-2 py-0.5 rounded text-[11px] bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-900 font-medium border border-slate-200">+ Material</button>
                <button type="button" @click="addOption('Model', 'iPhone 15, iPhone 16')" class="px-2 py-0.5 rounded text-[11px] bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-900 font-medium border border-slate-200">+ Model</button>
                <button type="button" @click="addOption('Capacity', '64GB, 128GB, 256GB')" class="px-2 py-0.5 rounded text-[11px] bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-900 font-medium border border-slate-200">+ Capacity</button>
            </div>

            <form method="POST" action="{{ route('admin.products.sync-options', $product) }}" class="space-y-4">
                @csrf
                <div class="space-y-3" id="options-container">
                    <template x-for="(opt, index) in options" :key="index">
                        <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-xs font-mono font-bold text-slate-400 w-5 text-center" x-text="index + 1"></span>
                            <div class="w-1/3 min-w-[140px]">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Option Name</label>
                                <input type="text" :name="'options[' + index + '][name]'" x-model="opt.name" placeholder="e.g. Size or Color" required
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded text-xs font-semibold text-slate-900 shadow-2xs">
                            </div>
                            <div class="flex-1">
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Values (comma-separated)</label>
                                <input type="text" :name="'options[' + index + '][values]'" x-model="opt.values" placeholder="e.g. S, M, L, XL or Black, Blue, Gold" required
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-900 shadow-2xs">
                            </div>
                            <button type="button" @click="removeOption(index)"
                                    class="p-1.5 mt-4 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors" title="Remove this option type">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </template>

                    <div x-show="options.length === 0" class="p-6 text-center border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                        <p class="text-sm font-medium text-slate-600">No option types configured</p>
                        <p class="text-xs text-slate-400 mt-1">Click "Add Option Type" to add dimensions like Color, Style, or Size.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold transition-colors shadow-2xs">
                        Save Options
                    </button>
                    <span class="text-xs text-slate-500" x-text="options.length + ' option type(s)'"></span>
                </div>
            </form>

            @if($product->options->isNotEmpty())
                <div class="border-t border-slate-200/80 pt-4">
                    <form method="POST" action="{{ route('admin.products.generate-variants', $product) }}" class="flex items-end gap-3 flex-wrap">
                        @csrf
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Default Price (₹)</label>
                            <input type="number" step="0.01" name="price_rupees" value="{{ $product->min_price_rupees ?? 699 }}" required class="w-28 px-3 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-900 shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 mb-1">Default MRP (₹)</label>
                            <input type="number" step="0.01" name="mrp_rupees" value="{{ $product->mrp_rupees ?? 1299 }}" class="w-28 px-3 py-1.5 bg-white border border-slate-300 rounded text-xs text-slate-900 shadow-2xs">
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs transition-colors">
                            Generate Missing Variant Matrix
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Variants Table Card -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Live Variants ({{ $product->variants->count() }})</h3>
                <span class="text-xs text-slate-500">Each variant tracks separate stock on-hand, prices and SKU</span>
            </div>

            <div class="divide-y divide-slate-200">
                @foreach($product->variants as $variant)
                    <div class="p-4 sm:p-5 hover:bg-slate-50/60 transition-colors">
                        <form method="POST" action="{{ route('admin.variants.update', $variant) }}" class="grid grid-cols-2 sm:grid-cols-6 lg:grid-cols-8 gap-3 items-center">
                            @csrf
                            @method('PUT')

                            <!-- Variant Option Combo Title -->
                            <div class="col-span-2">
                                <span class="text-xs font-bold text-slate-900">
                                    @php
                                        $combo = $variant->optionValues->pluck('value')->implode(' / ');
                                    @endphp
                                    {{ $combo ?: 'Default' }}
                                </span>
                                @if($variant->archived_at)
                                    <span class="ml-2 text-[10px] text-rose-700 bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded font-medium">Archived</span>
                                @endif
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">{{ $variant->sku }}</div>
                            </div>

                            <!-- SKU Input -->
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 uppercase">SKU</label>
                                <input type="text" name="sku" value="{{ $variant->sku }}" required
                                       class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-900 font-mono uppercase shadow-2xs">
                            </div>

                            <!-- Price Input -->
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 uppercase">Price (₹)</label>
                                <input type="number" step="0.01" name="price_rupees" value="{{ $variant->price_rupees }}" required
                                       class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-900 shadow-2xs">
                            </div>

                            <!-- MRP Input -->
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 uppercase">MRP (₹)</label>
                                <input type="number" step="0.01" name="mrp_rupees" value="{{ $variant->mrp_rupees }}"
                                       class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-900 shadow-2xs">
                            </div>

                            <!-- Stock Input -->
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 uppercase">Stock Qty</label>
                                <input type="number" min="0" name="stock_quantity" value="{{ $variant->stock_on_hand }}"
                                       class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs font-bold text-emerald-700 shadow-2xs">
                            </div>

                            <!-- Weight Input -->
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 uppercase">Weight (g)</label>
                                <input type="number" min="0" name="weight_grams" value="{{ $variant->weight_grams ?? 250 }}"
                                       class="w-full px-2 py-1 bg-white border border-slate-300 rounded text-xs text-slate-900 shadow-2xs">
                            </div>

                            <!-- Actions -->
                            <div class="col-span-2 sm:col-span-6 lg:col-span-1 flex items-center justify-end gap-2 pt-2 lg:pt-0">
                                <button type="submit" class="px-3 py-1 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded transition-colors shadow-2xs" title="Save changes">
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
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Upload Product Image</h2>

            <form method="POST" action="{{ route('admin.products.upload-image', ['product' => $product, 'tab' => 'media']) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="tab" value="media">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Image File (JPEG, PNG, WebP &bull; Max 10MB)</label>
                        <input type="file" name="image" required accept="image/jpeg,image/png,image/webp"
                               class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alt Text (Accessibility & Google Image Search)</label>
                        <input type="text" name="alt_text" value="{{ $product->name }}" maxlength="160"
                               class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs">
                    </div>
                </div>

                <!-- Pin by Option (e.g. Color) -->
                @if($product->options->isNotEmpty())
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-semibold text-slate-700">Pin to Specific Color / Option (Optional):</label>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            @foreach($product->options as $opt)
                                @foreach($opt->values as $oval)
                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 bg-slate-50 hover:bg-amber-50/50 px-2.5 py-1.5 rounded-lg border border-slate-200 cursor-pointer transition-colors">
                                        <input type="checkbox" name="option_value_ids[]" value="{{ $oval->id }}" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                        <span class="font-medium"><strong class="text-slate-900">{{ $opt->name }}:</strong> {{ $oval->value }}</span>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Pin by Specific Variant SKUs -->
                @if($product->variants->count() > 1)
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-semibold text-slate-700">Pin to Specific Variant SKUs (Optional):</label>
                        <div class="flex items-center gap-2 flex-wrap max-h-36 overflow-y-auto p-2 bg-slate-50 rounded-lg border border-slate-200">
                            @foreach($product->variants as $var)
                                <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 bg-white px-2.5 py-1 rounded border border-slate-200 hover:border-amber-400 cursor-pointer">
                                    <input type="checkbox" name="variant_ids[]" value="{{ $var->id }}" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                    <span class="font-mono text-[11px]">{{ $var->sku }}</span>
                                    <span class="text-slate-400 text-[10px]">({{ $var->display_option_label }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <button type="submit" class="px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span>Upload & Process WebP</span>
                </button>
            </form>
        </div>

        <!-- Images Grid -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3 flex items-center justify-between">
                <span>Product Gallery ({{ $product->images->count() }})</span>
                <span class="text-xs font-normal text-slate-500">Position 0 is the primary storefront photo &bull; Pin media to variants to switch photos when color is selected</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse($product->images as $img)
                    @php
                        $pinnedVariantIds = $img->productImageVariants->pluck('variant_id')->all();
                        $pinnedOptionValueIds = $img->productImageOptionValues->pluck('option_value_id')->all();
                        $pinnedVars = $product->variants->whereIn('id', $pinnedVariantIds);
                        $pinnedVals = $product->options->flatMap->values->whereIn('id', $pinnedOptionValueIds);
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-white p-3 space-y-2.5 relative flex flex-col justify-between shadow-2xs hover:shadow-xs transition-shadow"
                         x-data="{ editModal: false }">
                        <div class="space-y-2">
                            <div class="aspect-square rounded-lg overflow-hidden bg-slate-100 relative border border-slate-200">
                                <img src="{{ $img->url }}" alt="{{ $img->alt_text }}" class="w-full h-full object-contain">
                                @if($img->is_primary)
                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-slate-950 shadow-xs">
                                        PRIMARY
                                    </span>
                                @endif
                                <span class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded text-[9px] font-mono bg-black/60 text-white">
                                    #{{ $img->position }}
                                </span>
                            </div>

                            <div class="text-[11px] text-slate-500 truncate font-mono">
                                {{ $img->width }} &times; {{ $img->height }} px ({{ round($img->byte_size / 1024) }} KB)
                            </div>

                            <!-- Pinned Variation Badges -->
                            <div class="space-y-1">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Pinned to:</div>
                                <div class="flex items-center gap-1 flex-wrap min-h-6">
                                    @if($pinnedVals->isNotEmpty() || $pinnedVars->isNotEmpty())
                                        @foreach($pinnedVals as $pv)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                Color: {{ $pv->value }}
                                            </span>
                                        @endforeach
                                        @foreach($pinnedVars as $pVar)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-sky-50 text-sky-700 border border-sky-200 font-mono" title="{{ $pVar->sku }}">
                                                {{ $pVar->display_option_label }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] text-slate-500 bg-slate-100">
                                            All Variations
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <!-- Button to Open Assign & Edit Modal -->
                            <button type="button" @click="editModal = true"
                                    class="w-full py-1.5 px-3 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-800 transition-colors flex items-center justify-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>Assign Variations / Alt</span>
                            </button>

                            <div class="flex items-center justify-between gap-1 text-[11px]">
                                @if(!$img->is_primary)
                                    <form method="POST" action="{{ route('admin.images.set-primary', $img) }}">
                                        @csrf
                                        <button type="submit" class="font-semibold text-amber-700 hover:underline">
                                            Make Primary
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-400">Main photo</span>
                                @endif

                                <form method="POST" action="{{ route('admin.images.delete', $img) }}" onsubmit="return confirm('Delete this image?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-semibold text-rose-600 hover:underline">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Assign Variations Modal -->
                        <div x-show="editModal" x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4"
                             @keydown.escape.window="editModal = false">
                            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200 text-left"
                                 @click.outside="editModal = false">
                                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                    <h3 class="text-sm font-bold text-slate-900">Assign Variations & Alt Text</h3>
                                    <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
                                </div>

                                <form method="POST" action="{{ route('admin.images.update', $img) }}" class="space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="tab" value="media">

                                    <div class="flex items-center gap-3">
                                        <img src="{{ $img->url }}" class="w-16 h-16 rounded-lg object-contain border border-slate-200 bg-slate-50 shrink-0">
                                        <div class="flex-1 min-w-0">
                                            <label class="block text-xs font-semibold text-slate-700 mb-1">Alt Text</label>
                                            <input type="text" name="alt_text" value="{{ $img->alt_text }}" maxlength="160"
                                                   class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-2 focus:ring-amber-500">
                                        </div>
                                    </div>

                                    @if($product->options->isNotEmpty())
                                        <div class="space-y-2">
                                            <label class="block text-xs font-semibold text-slate-800">Pin to Color / Option:</label>
                                            <p class="text-[11px] text-slate-500">Selecting a color displays this photo automatically when that color is clicked on the storefront.</p>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                @foreach($product->options as $opt)
                                                    @foreach($opt->values as $oval)
                                                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 bg-slate-50 hover:bg-amber-50 px-2.5 py-1.5 rounded-lg border border-slate-200 cursor-pointer">
                                                            <input type="checkbox" name="option_value_ids[]" value="{{ $oval->id }}"
                                                                   {{ in_array($oval->id, $pinnedOptionValueIds, true) ? 'checked' : '' }}
                                                                   class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                                            <span><strong>{{ $opt->name }}:</strong> {{ $oval->value }}</span>
                                                        </label>
                                                    @endforeach
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if($product->variants->count() > 1)
                                        <div class="space-y-2">
                                            <label class="block text-xs font-semibold text-slate-800">Pin to Specific Variant SKUs:</label>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                                                @foreach($product->variants as $var)
                                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 bg-white p-1.5 rounded border border-slate-200 hover:border-amber-400 cursor-pointer">
                                                        <input type="checkbox" name="variant_ids[]" value="{{ $var->id }}"
                                                               {{ in_array($var->id, $pinnedVariantIds, true) ? 'checked' : '' }}
                                                               class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                                        <div class="truncate">
                                                            <div class="font-mono text-[11px] truncate">{{ $var->sku }}</div>
                                                            <div class="text-slate-400 text-[10px]">{{ $var->display_option_label }}</div>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                                        <button type="button" @click="editModal = false" class="px-3.5 py-2 text-xs font-medium text-slate-600 hover:text-slate-800">
                                            Cancel
                                        </button>
                                        <button type="submit" class="px-5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition-all">
                                            Save Assignments
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-slate-400">
                        No images uploaded yet. Products require at least 1 image to be published.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- TAB 4: SPECIFICATIONS -->
    @php
        $existingSpecs = $product->productAttributes->map(function($a) {
            return ['label' => (string) $a->label, 'value' => (string) $a->value];
        })->values()->all();
        if (empty($existingSpecs)) {
            $existingSpecs = [
                ['label' => 'Fabric', 'value' => 'Pure Cotton'],
                ['label' => 'Country of Origin', 'value' => 'India'],
                ['label' => 'Pattern', 'value' => 'Solid / Printed'],
                ['label' => 'Wash Care', 'value' => 'Hand Wash Separately'],
            ];
        }
    @endphp
    <div x-show="activeTab === 'attributes'" class="space-y-6" x-data="{
        specs: {{ json_encode($existingSpecs) }},
        addSpec(label = '', value = '') {
            this.specs.push({ label: label, value: value });
        },
        removeSpec(index) {
            this.specs.splice(index, 1);
        }
    }">
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200/80 pb-4 gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Product Specifications</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Attributes shown on the product page (e.g. Fabric, Country of Origin, Material, Care). Add or remove rows dynamically.</p>
                </div>
                <button type="button" @click="addSpec('', '')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wide uppercase transition-all shadow-2xs shrink-0 self-start sm:self-auto">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add Specification
                </button>
            </div>

            <!-- Quick Add Chips -->
            <div>
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-2">Quick Add Common Fields:</p>
                <div class="flex flex-wrap gap-1.5">
                    <template x-for="chip in ['Fabric', 'Country of Origin', 'Pattern', 'Fit', 'Sleeve Length', 'Neckline', 'Occasion', 'Wash Care', 'Material', 'Warranty', 'Compatible Devices', 'Package Contents', 'Battery Capacity', 'Dimensions']" :key="chip">
                        <button type="button" @click="addSpec(chip, '')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-900 text-[11px] font-medium transition-colors border border-slate-200/60">
                            <span class="text-amber-600 font-bold">+</span> <span x-text="chip"></span>
                        </button>
                    </template>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.products.update-attributes', $product) }}" class="space-y-4">
                @csrf
                <div class="space-y-2.5" id="specs-container">
                    <template x-for="(spec, index) in specs" :key="index">
                        <div class="flex items-center gap-3 p-2.5 bg-slate-50/80 rounded-lg border border-slate-200 hover:border-slate-300 transition-colors">
                            <span class="text-xs font-mono font-semibold text-slate-400 w-6 text-center select-none" x-text="index + 1"></span>
                            <div class="w-1/3 min-w-[140px]">
                                <input type="text" :name="'attributes[' + index + '][label]'" x-model="spec.label" placeholder="Attribute Title (e.g. Fabric)" required
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-md text-xs font-semibold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                            </div>
                            <div class="flex-1">
                                <input type="text" :name="'attributes[' + index + '][value]'" x-model="spec.value" placeholder="Attribute Value (e.g. 100% Pure Cotton)" required
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-md text-xs text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                            </div>
                            <button type="button" @click="removeSpec(index)"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors" title="Delete this row">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </template>

                    <div x-show="specs.length === 0" class="p-8 text-center border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                        <p class="text-sm font-medium text-slate-600">No specifications added yet</p>
                        <p class="text-xs text-slate-400 mt-1">Click the button above or any quick-add chip to add your first specification.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition-colors">
                        Save Specifications
                    </button>
                    <span class="text-xs text-slate-500" x-text="specs.length + ' specifications configured'"></span>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB: FAQs -->
    @php
        $existingFaqs = ($customFaqs ?? collect())->map(function($f) {
            return [
                'question' => (string) $f->question,
                'answer' => (string) $f->answer,
                'position' => (int) $f->position,
            ];
        })->values()->all();
        $faqMode = old('faq_mode', $productContent?->faq_mode ?? 'INHERIT');
    @endphp
    <div x-show="activeTab === 'faqs'" class="space-y-6" x-data="{
        mode: '{{ $faqMode }}',
        faqs: {{ json_encode($existingFaqs) }},
        addFaq() {
            this.faqs.push({ question: '', answer: '', position: this.faqs.length });
        },
        removeFaq(idx) {
            this.faqs.splice(idx, 1);
        }
    }">
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
            <div class="border-b border-slate-200/80 pb-4">
                <h2 class="text-base font-bold text-slate-900">Product FAQs</h2>
                <p class="text-xs text-slate-500 mt-0.5">Control whether this product inherits default store FAQs, displays custom FAQs, or hides FAQs completely.</p>
            </div>

            <form method="POST" action="{{ route('admin.products.update-faqs', $product) }}" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">FAQ Display Mode</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-start gap-3 p-3.5 rounded-lg border cursor-pointer transition-all"
                               :class="mode === 'INHERIT' ? 'border-amber-500 bg-amber-50/50 text-slate-900' : 'border-slate-200 hover:border-slate-300 text-slate-700'">
                            <input type="radio" name="faq_mode" value="INHERIT" x-model="mode" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs font-bold block">Inherit Default FAQs</span>
                                <span class="text-[11px] text-slate-500">Displays general store FAQs configured in Store &rsaquo; FAQs.</span>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 p-3.5 rounded-lg border cursor-pointer transition-all"
                               :class="mode === 'CUSTOM' ? 'border-amber-500 bg-amber-50/50 text-slate-900' : 'border-slate-200 hover:border-slate-300 text-slate-700'">
                            <input type="radio" name="faq_mode" value="CUSTOM" x-model="mode" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs font-bold block">Custom Product FAQs</span>
                                <span class="text-[11px] text-slate-500">Provide specific questions and answers for this exact product.</span>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 p-3.5 rounded-lg border cursor-pointer transition-all"
                               :class="mode === 'NONE' ? 'border-amber-500 bg-amber-50/50 text-slate-900' : 'border-slate-200 hover:border-slate-300 text-slate-700'">
                            <input type="radio" name="faq_mode" value="NONE" x-model="mode" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs font-bold block">Disable FAQs (Hide)</span>
                                <span class="text-[11px] text-slate-500">Do not display any FAQ section on this product's page.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div x-show="mode === 'CUSTOM'" class="space-y-4 pt-4 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Custom Questions & Answers</h3>
                            <p class="text-xs text-slate-500">Add question and answer pairs specific to this item.</p>
                        </div>
                        <button type="button" @click="addFaq()"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs transition-all shadow-2xs">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add FAQ
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(faq, idx) in faqs" :key="idx">
                            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3 relative group">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-slate-500 uppercase font-mono" x-text="'Q' + (idx + 1)"></span>
                                    <button type="button" @click="removeFaq(idx)" class="text-xs text-rose-500 hover:text-rose-700 font-medium">
                                        Delete
                                    </button>
                                </div>
                                <div>
                                    <input type="text" :name="'faqs[' + idx + '][question]'" x-model="faq.question" placeholder="Question (e.g. Is this fabric see-through?)" required
                                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-semibold text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
                                </div>
                                <div>
                                    <textarea :name="'faqs[' + idx + '][answer]'" x-model="faq.answer" rows="2" placeholder="Answer..." required
                                              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs"></textarea>
                                </div>
                                <input type="hidden" :name="'faqs[' + idx + '][position]'" :value="idx">
                            </div>
                        </template>

                        <div x-show="faqs.length === 0" class="p-6 text-center border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                            <p class="text-xs font-semibold text-slate-600">No custom FAQs added yet.</p>
                            <p class="text-xs text-slate-400 mt-1">Click "Add FAQ" above to add your first question & answer.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-3 border-t border-slate-200">
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition-colors">
                        Save FAQ Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB: QUICK LINKS -->
    @php
        $qlMode = old('quick_links_mode', $productContent?->quick_links_mode ?? 'INHERIT');
        $selectedGroup = old('quick_link_group_id', $productContent?->quick_link_group_id);
    @endphp
    <div x-show="activeTab === 'quick-links'" class="space-y-6" x-data="{ qlMode: '{{ $qlMode }}' }">
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-200/80 pb-4 gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Product Quick Links</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Display horizontal pill navigation buttons on this product's detail page.</p>
                </div>
                <a href="{{ route('admin.quick-links.index') }}" target="_blank"
                   class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 hover:text-amber-700 hover:underline">
                    Manage Quick Link Groups &rarr;
                </a>
            </div>

            <form method="POST" action="{{ route('admin.products.update-quick-links', $product) }}" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Quick Links Mode</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-start gap-3 p-3.5 rounded-lg border cursor-pointer transition-all"
                               :class="qlMode === 'INHERIT' ? 'border-amber-500 bg-amber-50/50 text-slate-900' : 'border-slate-200 hover:border-slate-300 text-slate-700'">
                            <input type="radio" name="quick_links_mode" value="INHERIT" x-model="qlMode" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs font-bold block">Inherit Category / Store Links</span>
                                <span class="text-[11px] text-slate-500">Uses category quick links, or default product group if enabled.</span>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 p-3.5 rounded-lg border cursor-pointer transition-all"
                               :class="qlMode === 'CUSTOM' ? 'border-amber-500 bg-amber-50/50 text-slate-900' : 'border-slate-200 hover:border-slate-300 text-slate-700'">
                            <input type="radio" name="quick_links_mode" value="CUSTOM" x-model="qlMode" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs font-bold block">Assign Specific Group</span>
                                <span class="text-[11px] text-slate-500">Pick a specific Quick Link group defined in Growth &rsaquo; Quick Links.</span>
                            </div>
                        </label>
                        <label class="flex items-start gap-3 p-3.5 rounded-lg border cursor-pointer transition-all"
                               :class="qlMode === 'NONE' ? 'border-amber-500 bg-amber-50/50 text-slate-900' : 'border-slate-200 hover:border-slate-300 text-slate-700'">
                            <input type="radio" name="quick_links_mode" value="NONE" x-model="qlMode" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-xs font-bold block">Disable Quick Links (Hide)</span>
                                <span class="text-[11px] text-slate-500">Do not display any quick link pill bar on this product's page.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div x-show="qlMode === 'CUSTOM'" class="space-y-3 pt-4 border-t border-slate-200">
                    <label class="block text-xs font-semibold text-slate-700">Select Quick Link Group</label>
                    <select name="quick_link_group_id" class="w-full sm:w-80 px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:outline-none focus:border-amber-500 shadow-2xs">
                        <option value="">-- Choose Quick Link Group --</option>
                        @foreach($quickLinkGroups ?? [] as $grp)
                            <option value="{{ $grp->id }}" {{ ($selectedGroup == $grp->id) ? 'selected' : '' }}>
                                {{ $grp->title }} ({{ $grp->quickLinks->count() }} links)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end pt-3 border-t border-slate-200">
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs shadow-xs transition-colors">
                        Save Quick Links Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 5: DANGER ZONE -->
    <div x-show="activeTab === 'danger'" class="space-y-6">
        <div class="rounded-xl border border-rose-200 bg-white shadow-xs p-6 space-y-4">
            <h2 class="text-base font-bold text-rose-700 border-b border-rose-100 pb-3">Danger Zone</h2>

            @if($product->status->value === 'ARCHIVED')
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Restore Product</h3>
                        <p class="text-xs text-slate-500">Restores this archived product to Draft status.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.products.restore', $product) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 text-xs font-semibold transition-colors">
                            Restore to Draft
                        </button>
                    </form>
                </div>
            @else
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Archive Product</h3>
                        <p class="text-xs text-slate-500">
                            Retires the product from catalog. Its URL and SKUs stay reserved and historic order records remain intact.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.products.archive', $product) }}" onsubmit="return confirm('Archive this product?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 text-xs font-semibold transition-colors">
                            Archive Product
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
