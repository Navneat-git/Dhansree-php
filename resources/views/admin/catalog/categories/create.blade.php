@extends('layouts.admin')

@section('title', 'Add Category')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('admin.categories.index') }}" class="hover:text-amber-600 transition-colors">Categories</a>
        <span>&rsaquo;</span>
        <span class="text-slate-800 font-medium">Add Category</span>
    </div>

    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Add Category</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Create a new category in your store navigation catalog.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-6">
        @csrf

        <!-- General Info Card -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Basic Information</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Category Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="category-name" value="{{ old('name') }}" required maxlength="80"
                           placeholder="e.g. Kurta Sets"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">URL Slug (Leave blank to auto-generate)</label>
                    <div class="flex items-center rounded-lg border border-slate-300 bg-white overflow-hidden focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500 shadow-2xs">
                        <span class="px-3 text-xs text-slate-400 font-mono select-none bg-slate-50 border-r border-slate-200 py-2.5">/</span>
                        <input type="text" name="slug" id="category-slug" value="{{ old('slug') }}" maxlength="100"
                               placeholder="kurta-sets"
                               class="w-full py-2.5 px-3 bg-transparent text-sm text-slate-900 font-mono placeholder-slate-400 focus:outline-none">
                    </div>
                    @error('slug')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Parent Category</label>
                    <select name="parent_id" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                        <option value="">None (Top-level Root Category)</option>
                        @foreach($categoryOptions as $opt)
                            @if($opt['depth'] < 3)
                                <option value="{{ $opt['id'] }}" {{ old('parent_id') == $opt['id'] ? 'selected' : '' }}>
                                    {{ str_repeat('— ', $opt['depth'] - 1) }}{{ $opt['name'] }} ({{ $opt['label'] }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Maximum category depth allowed is 3 levels.</p>
                    @error('parent_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Description (Optional)</label>
                    <textarea name="description" rows="3" placeholder="Category description displayed on collection landing pages..."
                              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Storefront Card Shape</label>
                    <select name="card_shape" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                        <option value="">Inherit from Parent</option>
                        <option value="square" {{ old('card_shape') === 'square' ? 'selected' : '' }}>Square (1:1 - Smart Gadgets, Accessories)</option>
                        <option value="tall" {{ old('card_shape') === 'tall' ? 'selected' : '' }}>Tall (3:4 - Women Fashion, Kurties)</option>
                        <option value="wide" {{ old('card_shape') === 'wide' ? 'selected' : '' }}>Wide (16:9 - Home Lifestyle)</option>
                    </select>
                </div>
            </div>

            <!-- Switches -->
            <div class="border-t border-slate-200/80 pt-4 space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_visible" value="1" {{ old('is_visible', '1') ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-slate-300 bg-white text-amber-500 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-900">Visible on Storefront</span>
                        <p class="text-xs text-slate-500">When enabled, customers can view and navigate into this category.</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="show_in_browse" value="1" {{ old('show_in_browse', '1') ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-slate-300 bg-white text-amber-500 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-900">Show in Browse / Megamenu</span>
                        <p class="text-xs text-slate-500">Includes this category in the site-wide browse categories and megamenu.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- SEO Card -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3 flex items-center justify-between">
                <span>Search Engine Optimization (SEO)</span>
                <span class="text-xs font-normal text-slate-500">Google SERP Preview</span>
            </h2>

            <!-- SERP Snippet Preview Box -->
            <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 font-sans space-y-1">
                <div class="text-xs text-slate-500 font-mono">https://dhansree.com &rsaquo; <span id="serp-slug">category</span></div>
                <div class="text-base text-blue-600 font-medium hover:underline cursor-pointer" id="serp-title">Category Title | Dhansree</div>
                <div class="text-xs text-slate-600 line-clamp-2" id="serp-desc">Explore our premium selection at Dhansree. Carefully chosen essentials and fashion at honest prices with free delivery.</div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">SEO Title (Recommended: 50–60 characters)</label>
                    <input type="text" name="seo_title" id="seo-title" value="{{ old('seo_title') }}" maxlength="120"
                           placeholder="Women Kurties & Sets | Premium Ethnic Wear Online - Dhansree"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">SEO Meta Description (Recommended: 120–160 characters)</label>
                    <textarea name="seo_description" id="seo-desc" rows="2" maxlength="320"
                              placeholder="Shop high quality women kurties, suit sets and co-ords online at best prices. Fast shipping and COD available across India."
                              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">{{ old('seo_description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Submit Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:text-slate-900 hover:bg-slate-50 text-sm font-semibold transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase shadow-sm transition-all">
                Save Category
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const nameInput = document.getElementById('category-name');
    const slugInput = document.getElementById('category-slug');
    const seoTitle = document.getElementById('seo-title');
    const seoDesc = document.getElementById('seo-desc');
    const serpSlug = document.getElementById('serp-slug');
    const serpTitle = document.getElementById('serp-title');
    const serpDesc = document.getElementById('serp-desc');

    const slugify = (text) => {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/&/g, '-and-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    };

    nameInput?.addEventListener('input', () => {
        if (!slugInput.value || slugInput.dataset.manual !== 'true') {
            const s = slugify(nameInput.value);
            slugInput.placeholder = s || 'category-slug';
            serpSlug.textContent = s || 'category';
        }
        if (!seoTitle.value) {
            serpTitle.textContent = (nameInput.value || 'Category Title') + ' | Dhansree';
        }
    });

    slugInput?.addEventListener('input', () => {
        slugInput.dataset.manual = 'true';
        serpSlug.textContent = slugInput.value || 'category';
    });

    seoTitle?.addEventListener('input', () => {
        serpTitle.textContent = seoTitle.value || (nameInput.value || 'Category Title') + ' | Dhansree';
    });

    seoDesc?.addEventListener('input', () => {
        serpDesc.textContent = seoDesc.value || 'Explore our premium selection at Dhansree. Carefully chosen essentials and fashion at honest prices.';
    });
});
</script>
@endsection
