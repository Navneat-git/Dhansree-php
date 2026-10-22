@extends('layouts.admin')

@section('title', 'Edit Category - ' . $category->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-zinc-400">
        <a href="{{ route('admin.categories.index') }}" class="hover:text-amber-400 transition-colors">Categories</a>
        <span>&rsaquo;</span>
        <span class="text-zinc-200 font-medium">{{ $category->name }}</span>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-800 pb-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-white">{{ $category->name }}</h1>
                @if($category->archived_at)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-950/70 text-red-400 border border-red-800/60">Archived</span>
                @elseif($category->is_visible)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/70 text-emerald-400 border border-emerald-800/60">Visible</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-800 text-zinc-400 border border-zinc-700">Hidden</span>
                @endif
            </div>
            <p class="text-xs text-zinc-500 font-mono mt-1">
                Storefront Address: <a href="{{ url('/' . $category->slug) }}" target="_blank" class="text-amber-400/80 hover:underline">/{{ $category->slug }}</a>
                &bull; Level {{ $category->depth }} &bull; {{ $category->product_count }} Products
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.index', ['category_id' => $category->id]) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg bg-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-700 transition-colors">
                View {{ $category->product_count }} Products
            </a>
        </div>
    </div>

    <!-- Main Edit Form -->
    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="version" value="{{ $category->version }}">

        <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-5">
            <h2 class="text-base font-semibold text-white border-b border-zinc-800/60 pb-3">Basic Information</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">Category Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" id="category-name" value="{{ old('name', $category->name) }}" required maxlength="80"
                           class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70 focus:ring-1 focus:ring-amber-500/50">
                    @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">URL Slug (Changes create 301 SEO permanent redirect)</label>
                    <div class="flex items-center rounded-lg border border-zinc-700/80 bg-zinc-900 overflow-hidden focus-within:border-amber-500/70 focus-within:ring-1 focus-within:ring-amber-500/50">
                        <span class="px-3 text-xs text-zinc-500 font-mono select-none">/</span>
                        <input type="text" name="slug" id="category-slug" value="{{ old('slug', $category->slug) }}" maxlength="100"
                               class="w-full py-2.5 pr-3 bg-transparent text-sm text-white font-mono focus:outline-none">
                    </div>
                    @error('slug')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">Parent Category</label>
                    <select name="parent_id" class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70 focus:ring-1 focus:ring-amber-500/50">
                        <option value="">None (Top-level Root Category)</option>
                        @foreach($categoryOptions as $opt)
                            @if($opt['id'] !== $category->id)
                                <option value="{{ $opt['id'] }}" {{ old('parent_id', $category->parent_id) == $opt['id'] ? 'selected' : '' }}>
                                    {{ str_repeat('— ', $opt['depth'] - 1) }}{{ $opt['name'] }} ({{ $opt['label'] }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('parent_id')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">Description (Optional)</label>
                    <textarea name="description" rows="3" class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70 focus:ring-1 focus:ring-amber-500/50">{{ old('description', $category->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1.5">Storefront Card Shape</label>
                    <select name="card_shape" class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70 focus:ring-1 focus:ring-amber-500/50">
                        <option value="" {{ old('card_shape', $category->card_shape) === null ? 'selected' : '' }}>Inherit from Parent</option>
                        <option value="square" {{ old('card_shape', $category->card_shape) === 'square' ? 'selected' : '' }}>Square (1:1)</option>
                        <option value="tall" {{ old('card_shape', $category->card_shape) === 'tall' ? 'selected' : '' }}>Tall (3:4 - Women Fashion)</option>
                        <option value="wide" {{ old('card_shape', $category->card_shape) === 'wide' ? 'selected' : '' }}>Wide (16:9 - Home Lifestyle)</option>
                    </select>
                </div>
            </div>

            <!-- Switches -->
            <div class="border-t border-zinc-800/80 pt-4 space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="is_visible" value="1" {{ old('is_visible', $category->is_visible) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-zinc-700 bg-zinc-900 text-amber-500 focus:ring-amber-500 focus:ring-offset-zinc-900">
                    <div>
                        <span class="text-sm font-medium text-white">Visible on Storefront</span>
                        <p class="text-xs text-zinc-400">Controls whether customers can access and view products in this category.</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="show_in_browse" value="1" {{ old('show_in_browse', $category->show_in_browse) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-zinc-700 bg-zinc-900 text-amber-500 focus:ring-amber-500 focus:ring-offset-zinc-900">
                    <div>
                        <span class="text-sm font-medium text-white">Show in Browse / Megamenu</span>
                        <p class="text-xs text-zinc-400">Includes this category in header megamenu navigation trees.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- SEO Card -->
        <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
            <h2 class="text-base font-semibold text-white border-b border-zinc-800/60 pb-3">Search Engine Optimization (SEO)</h2>

            <!-- SERP Preview -->
            <div class="p-4 rounded-lg bg-zinc-950/80 border border-zinc-800/80 font-sans space-y-1">
                <div class="text-xs text-zinc-400 font-mono">https://dhansree.com &rsaquo; <span>{{ $category->slug }}</span></div>
                <div class="text-base text-blue-400 font-medium hover:underline">{{ $category->seo_title ?: $category->name . ' | Dhansree' }}</div>
                <div class="text-xs text-zinc-400 line-clamp-2">{{ $category->seo_description ?: 'Explore our curated collection of ' . $category->name . ' at Dhansree. High quality products with fast shipping and COD.' }}</div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1">SEO Title</label>
                    <input type="text" name="seo_title" value="{{ old('seo_title', $category->seo_title) }}" maxlength="120"
                           class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70 focus:ring-1 focus:ring-amber-500/50">
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-300 mb-1">SEO Meta Description</label>
                    <textarea name="seo_description" rows="2" maxlength="320"
                              class="w-full px-3.5 py-2.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-sm text-white focus:outline-none focus:border-amber-500/70 focus:ring-1 focus:ring-amber-500/50">{{ old('seo_description', $category->seo_description) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2 rounded-lg border border-zinc-700 bg-zinc-800 text-zinc-300 hover:text-white text-sm font-medium transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2 rounded-lg bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white font-medium text-sm shadow-lg shadow-amber-950/40 transition-all">
                Save Changes
            </button>
        </div>
    </form>

    <!-- Category Image Section -->
    <div class="rounded-xl border border-zinc-800/80 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
        <h2 class="text-base font-semibold text-white border-b border-zinc-800/60 pb-3">Category Picture</h2>

        @if($category->image_storage_key)
            <div class="flex flex-col sm:flex-row items-start gap-5 p-4 rounded-lg bg-zinc-950/50 border border-zinc-800">
                <img src="{{ $category->image_url }}" alt="{{ $category->image_alt_text ?? $category->name }}"
                     class="w-32 h-32 rounded-lg object-cover border border-zinc-700/80 shadow-md">
                <div class="space-y-2 flex-1">
                    <p class="text-xs text-zinc-400">Dimensions: <span class="text-white font-mono">{{ $category->image_width }} &times; {{ $category->image_height }} px</span> (WebP)</p>
                    <p class="text-xs text-zinc-400">Alt Text: <span class="text-white">{{ $category->image_alt_text ?? 'None' }}</span></p>

                    <form method="POST" action="{{ route('admin.categories.remove-image', $category) }}" onsubmit="return confirm('Remove category picture?');">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-950/40 hover:bg-red-950 text-red-400 text-xs font-medium border border-red-900/50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Remove Picture
                        </button>
                    </form>
                </div>
            </div>
        @else
            <p class="text-xs text-zinc-500">No picture uploaded yet. The storefront card falls back to a product thumbnail.</p>
        @endif

        <form method="POST" action="{{ route('admin.categories.upload-image', $category) }}" enctype="multipart/form-data" class="space-y-3 pt-2">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1">Select Image File (JPEG, PNG, WebP &bull; Max 10MB)</label>
                    <input type="file" name="image" required accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-xs text-zinc-400 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-zinc-800 file:text-zinc-200 hover:file:bg-zinc-700">
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1">Image Alt Text (Accessibility & SEO)</label>
                    <input type="text" name="alt_text" value="{{ $category->image_alt_text ?? $category->name }}" maxlength="160"
                           class="w-full px-3 py-1.5 bg-zinc-900 border border-zinc-700/80 rounded-lg text-xs text-white">
                </div>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-medium transition-colors">
                Upload & Process WebP
            </button>
        </form>
    </div>

    <!-- Danger Zone Card -->
    <div class="rounded-xl border border-red-900/40 bg-zinc-900/60 backdrop-blur-sm p-6 space-y-4">
        <h2 class="text-base font-semibold text-red-400 border-b border-red-900/30 pb-3">Danger Zone</h2>

        @if($category->archived_at)
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-white">Restore Category</h3>
                    <p class="text-xs text-zinc-400">Bring this category back into active status.</p>
                </div>
                <form method="POST" action="{{ route('admin.categories.restore', $category) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-950/60 hover:bg-emerald-900/80 text-emerald-400 border border-emerald-800/60 text-xs font-medium transition-colors">
                        Restore Category
                    </button>
                </form>
            </div>
        @else
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-sm font-medium text-white">Archive Category</h3>
                    <p class="text-xs text-zinc-400">
                        Retires the category. Allowed only when it has 0 active subcategories and 0 active products.
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.categories.archive', $category) }}" onsubmit="return confirm('Are you sure you want to archive this category?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-950/60 hover:bg-red-900/80 text-red-400 border border-red-800/60 text-xs font-medium transition-colors">
                        Archive Category
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
