@extends('layouts.admin')

@section('title', 'Edit Category - ' . $category->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('admin.categories.index') }}" class="hover:text-amber-600 transition-colors">Categories</a>
        <span>&rsaquo;</span>
        <span class="text-slate-800 font-medium">{{ $category->name }}</span>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $category->name }}</h1>
                @if($category->archived_at)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Archived</span>
                @elseif($category->is_visible)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Visible</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">Hidden</span>
                @endif
            </div>
            <p class="text-xs text-slate-500 font-mono mt-1">
                Storefront Address: <a href="{{ url('/' . $category->slug) }}" target="_blank" class="text-amber-600 hover:underline">/{{ $category->slug }}</a>
                &bull; Level {{ $category->depth }} &bull; {{ $category->product_count }} Products
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.index', ['category_id' => $category->id]) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-100 text-slate-700 hover:text-slate-900 hover:bg-slate-200 transition-colors">
                View {{ $category->product_count }} Products
            </a>
        </div>
    </div>

    <!-- Main Edit Form -->
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="version" value="{{ $category->version }}">

        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Basic Information</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Category Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="category-name" value="{{ old('name', $category->name) }}" required maxlength="80"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">URL Slug (Changes create 301 SEO permanent redirect)</label>
                    <div class="flex items-center rounded-lg border border-slate-300 bg-white overflow-hidden focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500 shadow-2xs">
                        <span class="px-3 text-xs text-slate-400 font-mono select-none bg-slate-50 border-r border-slate-200 py-2.5">/</span>
                        <input type="text" name="slug" id="category-slug" value="{{ old('slug', $category->slug) }}" maxlength="100"
                               class="w-full py-2.5 px-3 bg-transparent text-sm text-slate-900 font-mono focus:outline-none">
                    </div>
                    @error('slug')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Parent Category</label>
                    <select name="parent_id" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                        <option value="">None (Top-level Root Category)</option>
                        @foreach($categoryOptions as $opt)
                            @if($opt['id'] !== $category->id)
                                <option value="{{ $opt['id'] }}" {{ old('parent_id', $category->parent_id) == $opt['id'] ? 'selected' : '' }}>
                                    {{ str_repeat('— ', $opt['depth'] - 1) }}{{ $opt['name'] }} ({{ $opt['label'] }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('parent_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Description (Optional)</label>
                    <textarea name="description" rows="3" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">{{ old('description', $category->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Storefront Card Shape</label>
                    <select name="card_shape" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                        <option value="" {{ old('card_shape', $category->card_shape) === null ? 'selected' : '' }}>Inherit from Parent</option>
                        <option value="square" {{ old('card_shape', $category->card_shape) === 'square' ? 'selected' : '' }}>Square (1:1)</option>
                        <option value="tall" {{ old('card_shape', $category->card_shape) === 'tall' ? 'selected' : '' }}>Tall (3:4 - Women Fashion)</option>
                        <option value="wide" {{ old('card_shape', $category->card_shape) === 'wide' ? 'selected' : '' }}>Wide (16:9 - Home Lifestyle)</option>
                    </select>
                </div>
            </div>

            <!-- Switches -->
            <div class="border-t border-slate-200/80 pt-4 space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_visible" value="1" {{ old('is_visible', $category->is_visible) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-slate-300 bg-white text-amber-500 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-900">Visible on Storefront</span>
                        <p class="text-xs text-slate-500">Controls whether customers can access and view products in this category.</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="show_in_browse" value="1" {{ old('show_in_browse', $category->show_in_browse) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-slate-300 bg-white text-amber-500 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-900">Show in Browse / Megamenu</span>
                        <p class="text-xs text-slate-500">Includes this category in header megamenu navigation trees.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- SEO Card -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Search Engine Optimization (SEO)</h2>

            <!-- SERP Preview -->
            <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 font-sans space-y-1">
                <div class="text-xs text-slate-500 font-mono">https://dhansree.com &rsaquo; <span>{{ $category->slug }}</span></div>
                <div class="text-base text-blue-600 font-medium hover:underline">{{ $category->seo_title ?: $category->name . ' | Dhansree' }}</div>
                <div class="text-xs text-slate-600 line-clamp-2">{{ $category->seo_description ?: 'Explore our curated collection of ' . $category->name . ' at Dhansree. High quality products with fast shipping and COD.' }}</div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">SEO Title</label>
                    <input type="text" name="seo_title" value="{{ old('seo_title', $category->seo_title) }}" maxlength="120"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">SEO Meta Description</label>
                    <textarea name="seo_description" rows="2" maxlength="320"
                              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">{{ old('seo_description', $category->seo_description) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Quick Links Settings -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Quick Navigation Links</h2>
            <p class="text-xs text-slate-500">Configure pill navigation links to display on this category's listing page.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Quick Links Mode</label>
                    <select name="quick_links_mode" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs">
                        <option value="INHERIT" {{ (old('quick_links_mode', $categoryContent?->quick_links_mode) === 'INHERIT' || !$categoryContent) ? 'selected' : '' }}>Inherit Global Listing Links</option>
                        <option value="CUSTOM" {{ old('quick_links_mode', $categoryContent?->quick_links_mode) === 'CUSTOM' ? 'selected' : '' }}>Assign Specific Quick Link Group</option>
                        <option value="NONE" {{ old('quick_links_mode', $categoryContent?->quick_links_mode) === 'NONE' ? 'selected' : '' }}>Disable Quick Links (Hide)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Assigned Group (if Custom)</label>
                    <select name="quick_link_group_id" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs">
                        <option value="">-- Choose Quick Link Group --</option>
                        @foreach($quickLinkGroups ?? [] as $grp)
                            <option value="{{ $grp->id }}" {{ ($categoryContent?->quick_link_group_id == $grp->id) ? 'selected' : '' }}>
                                {{ $grp->title }} ({{ $grp->quickLinks->count() }} links)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:text-slate-900 hover:bg-slate-50 text-sm font-semibold transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold text-xs tracking-wider uppercase shadow-sm transition-all">
                Save Changes
            </button>
        </div>
    </form>

    <!-- Category Image Section -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-4">
        <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Category Picture</h2>

        @if($category->image_storage_key)
            <div class="flex flex-col sm:flex-row items-start gap-5 p-4 rounded-lg bg-slate-50 border border-slate-200">
                <img src="{{ $category->image_url }}" alt="{{ $category->image_alt_text ?? $category->name }}"
                     class="w-32 h-32 rounded-lg object-cover border border-slate-300 shadow-sm bg-white">
                <div class="space-y-2 flex-1">
                    <p class="text-xs text-slate-600">Dimensions: <span class="text-slate-900 font-mono font-bold">{{ $category->image_width }} &times; {{ $category->image_height }} px</span> (WebP)</p>
                    <p class="text-xs text-slate-600">Alt Text: <span class="text-slate-900 font-medium">{{ $category->image_alt_text ?? 'None' }}</span></p>

                    <form method="POST" action="{{ route('admin.categories.remove-image', $category) }}" onsubmit="return confirm('Remove category picture?');">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold border border-rose-200 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Remove Picture
                        </button>
                    </form>
                </div>
            </div>
        @else
            <p class="text-xs text-slate-500">No picture uploaded yet. The storefront card falls back to a product thumbnail.</p>
        @endif

        <form method="POST" action="{{ route('admin.categories.upload-image', $category) }}" enctype="multipart/form-data" class="space-y-3 pt-2">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Select Image File (JPEG, PNG, WebP &bull; Max 10MB)</label>
                    <input type="file" name="image" required accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Image Alt Text (Accessibility & SEO)</label>
                    <input type="text" name="alt_text" value="{{ $category->image_alt_text ?? $category->name }}" maxlength="160"
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs">
                </div>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition-colors">
                Upload & Process WebP
            </button>
        </form>
    </div>

    <!-- Danger Zone Card -->
    <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-6 space-y-4">
        <h2 class="text-base font-bold text-rose-700 border-b border-rose-200 pb-3">Danger Zone</h2>

        @if($category->archived_at)
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Restore Category</h3>
                    <p class="text-xs text-slate-500">Bring this category back into active status.</p>
                </div>
                <form method="POST" action="{{ route('admin.categories.restore', $category) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition-colors">
                        Restore Category
                    </button>
                </form>
            </div>
        @else
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Archive Category</h3>
                    <p class="text-xs text-slate-500">
                        Retires the category. Allowed only when it has 0 active subcategories and 0 active products.
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.categories.archive', $category) }}" onsubmit="return confirm('Are you sure you want to archive this category?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition-colors">
                        Archive Category
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
