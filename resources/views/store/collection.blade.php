@extends('layouts.store')

@section('content')
@php
    $hasActiveFilters = !empty($filters['min_price']) 
        || !empty($filters['max_price']) 
        || !empty($filters['in_stock']) 
        || !empty($selectedOptions);

    $activeFilterCount = (!empty($filters['min_price']) ? 1 : 0)
        + (!empty($filters['max_price']) ? 1 : 0)
        + (!empty($filters['in_stock']) ? 1 : 0)
        + collect($selectedOptions ?? [])->sum(fn($v) => count($v));

    $colorHexMap = [
        'pink' => '#f472b6',
        'white' => '#ffffff',
        'off-white' => '#faf7f2',
        'off white' => '#faf7f2',
        'blue' => '#3b82f6',
        'slate blue' => '#64748b',
        'yellow' => '#eab308',
        'red' => '#dc2626',
        'green' => '#16a34a',
        'black' => '#0f172a',
        'maroon' => '#800000',
        'beige' => '#f5f5dc',
        'navy' => '#1e3a8a',
        'orange' => '#ea580c',
        'purple' => '#9333ea',
        'grey' => '#9ca3af',
        'gray' => '#9ca3af',
        'teal' => '#0d9488',
        'olive' => '#84cc16',
        'gold' => '#d97706',
        'magenta' => '#d946ef',
    ];
@endphp

@include('store.partials.quick-links')

<div x-data="{ mobileFiltersOpen: false }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    
    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-rich-black/50 mb-6">
        <a href="{{ $isSmartLivingHub ? url('/smart-living') : url('/') }}" class="hover:text-maroon-800 transition-colors">
            {{ $isSmartLivingHub ? 'Smart Living' : 'Home' }}
        </a>
        <span>/</span>
        @if(false)
            <a href="{{ url('/' . false->slug) }}" class="hover:text-maroon-800 transition-colors">
                {{ false->name }}
            </a>
            <span>/</span>
        @endif
        <a href="{{ url('/collections/' . $collection->slug) }}" class="hover:text-maroon-800 transition-colors">Collections</a><span>/</span><span class="text-rich-black/80 font-medium">{{ $collection->name }}</span>
    </nav>

    <!-- Header & Description -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-6 border-b border-light-gold/60 mb-8">
        <div class="space-y-2 max-w-2xl">
            <span class="text-[0.6875rem] uppercase tracking-widest text-gold-600 font-bold">Curated Collection</span>
            <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl text-maroon-800 font-normal leading-tight">
                {{ ($collection->heading ?? $collection->name) }}
            </h1>
            @if($collection->intro)
                <p class="text-xs sm:text-sm text-rich-black/70 leading-relaxed">
                    {{ Str::limit(strip_tags($collection->intro), 280) }}
                </p>
            @endif
        </div>
        <div class="text-xs text-rich-black/50 shrink-0 font-medium">
            Showing {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
        </div>
    </div>

    <!-- Mobile Filter & Sort Bar (Visible on mobile/tablet) -->
    <div class="lg:hidden flex items-center justify-between gap-3 mb-6 p-3 bg-white rounded-xl border border-light-gold shadow-xs">
        <button type="button"
                @click="mobileFiltersOpen = true"
                class="flex items-center gap-2 px-3.5 py-2 rounded-lg bg-ivory text-rich-black text-xs font-semibold border border-light-gold hover:border-maroon-800 transition-colors">
            <svg class="w-4 h-4 text-maroon-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <span>Filters &amp; Sort</span>
            @if($activeFilterCount > 0)
                <span class="w-5 h-5 rounded-full bg-maroon-800 text-white text-[0.625rem] font-bold flex items-center justify-center">
                    {{ $activeFilterCount }}
                </span>
            @endif
        </button>

        <div class="flex items-center gap-2">
            <label for="mobile-quick-sort" class="text-xs text-rich-black/60 font-medium hidden sm:inline">Sort:</label>
            <select id="mobile-quick-sort"
                    onchange="location.href = this.value"
                    class="text-xs rounded-lg border-light-gold bg-ivory/50 focus:border-maroon-800 focus:ring-maroon-800 py-2 pl-2 pr-7">
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'popular']) }}" {{ ($filters['sort'] ?? '') === 'popular' ? 'selected' : '' }}>Most Popular</option>
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest</option>
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_asc']) }}" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_desc']) }}" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
            </select>
        </div>
    </div>

    <!-- Mobile Drawer Modal -->
    <div x-cloak
         x-show="mobileFiltersOpen"
         class="fixed inset-0 z-50 lg:hidden"
         role="dialog"
         aria-modal="true">
        <!-- Backdrop -->
        <div x-show="mobileFiltersOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileFiltersOpen = false"
             class="fixed inset-0 bg-black/50 backdrop-blur-xs"></div>

        <!-- Slide Drawer -->
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-8">
            <div x-show="mobileFiltersOpen"
                 x-transition:enter="transition ease-in-out duration-300 transform"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in-out duration-300 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-xs sm:max-w-sm bg-white shadow-2xl flex flex-col justify-between">
                
                <div class="flex items-center justify-between px-5 py-4 border-b border-light-gold">
                    <div class="flex items-center gap-2">
                        <h2 class="font-serif text-lg font-bold text-maroon-800">Filter &amp; Sort</h2>
                        @if($activeFilterCount > 0)
                            <span class="px-2 py-0.5 rounded-full bg-maroon-100 text-maroon-800 text-[0.6875rem] font-bold">
                                {{ $activeFilterCount }} active
                            </span>
                        @endif
                    </div>
                    <button type="button" @click="mobileFiltersOpen = false" class="p-1 rounded-lg hover:bg-slate-100 text-rich-black/50 hover:text-rich-black">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Drawer Content -->
                <form action="{{ url()->current() }}" method="GET" class="overflow-y-auto p-5 space-y-6 flex-1">
                    
                    <!-- Sort Option -->
                    <div class="space-y-2">
                        <label for="mobile_sort" class="block text-xs font-bold uppercase tracking-wider text-rich-black/80">Sort By</label>
                        <select name="sort" id="mobile_sort"
                                class="w-full text-xs rounded-lg border-light-gold bg-ivory/50 focus:border-maroon-800 focus:ring-maroon-800 py-2">
                            <option value="popular" {{ ($filters['sort'] ?? '') === 'popular' ? 'selected' : '' }}>Most Popular</option>
                            <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest Arrivals</option>
                            <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        </select>
                    </div>

                    <!-- Dynamic Color Filter -->
                    @if(isset($availableOptions['Color']) && count($availableOptions['Color']) > 0)
                        <hr class="border-light-gold/60">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-rich-black/80">Color</h3>
                                @if(!empty($selectedOptions['Color']))
                                    <span class="text-[0.6875rem] text-maroon-800 font-semibold">{{ count($selectedOptions['Color']) }} selected</span>
                                @endif
                            </div>
                            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                @foreach($availableOptions['Color'] as $color)
                                    @php
                                        $hex = $colorHexMap[strtolower(trim($color))] ?? '#cbd5e1';
                                        $isWhite = in_array(strtolower(trim($color)), ['white', 'off-white', 'off white', 'ivory']);
                                        $checked = in_array($color, $selectedOptions['Color'] ?? []);
                                    @endphp
                                    <label class="flex items-center gap-2.5 cursor-pointer py-1 text-xs text-rich-black/80 hover:text-maroon-800 transition-colors">
                                        <input type="checkbox" name="color[]" value="{{ $color }}" {{ $checked ? 'checked' : '' }}
                                               onchange="this.form.submit()"
                                               class="rounded border-light-gold text-maroon-800 focus:ring-maroon-800">
                                        <span class="w-3.5 h-3.5 rounded-full inline-block shrink-0 shadow-xs {{ $isWhite ? 'border border-slate-300' : '' }}" style="background-color: {{ $hex }};"></span>
                                        <span class="truncate">{{ $color }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Dynamic Size Filter -->
                    @if(isset($availableOptions['Size']) && count($availableOptions['Size']) > 0)
                        <hr class="border-light-gold/60">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-rich-black/80">Size</h3>
                                @if(!empty($selectedOptions['Size']))
                                    <span class="text-[0.6875rem] text-maroon-800 font-semibold">{{ count($selectedOptions['Size']) }} selected</span>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($availableOptions['Size'] as $size)
                                    @php
                                        $checked = in_array($size, $selectedOptions['Size'] ?? []);
                                    @endphp
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="size[]" value="{{ $size }}" {{ $checked ? 'checked' : '' }} onchange="this.form.submit()" class="peer sr-only">
                                        <span class="min-w-[2.5rem] h-8 px-2.5 rounded-lg border text-xs font-semibold flex items-center justify-center transition-all peer-checked:bg-maroon-800 peer-checked:text-white peer-checked:border-maroon-800 border-light-gold text-rich-black/80 hover:border-maroon-800 bg-white shadow-xs">
                                            {{ $size }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Dynamic Other Variations -->
                    @foreach($availableOptions as $dimName => $dimValues)
                        @if(!in_array(strtolower($dimName), ['color', 'size']))
                            <hr class="border-light-gold/60">
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-bold uppercase tracking-wider text-rich-black/80">{{ $dimName }}</h3>
                                    @if(!empty($selectedOptions[$dimName]))
                                        <span class="text-[0.6875rem] text-maroon-800 font-semibold">{{ count($selectedOptions[$dimName]) }} selected</span>
                                    @endif
                                </div>
                                <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                    @foreach($dimValues as $val)
                                        @php
                                            $checked = in_array($val, $selectedOptions[$dimName] ?? []);
                                        @endphp
                                        <label class="flex items-center gap-2.5 cursor-pointer py-1 text-xs text-rich-black/80 hover:text-maroon-800 transition-colors">
                                            <input type="checkbox" name="{{ Str::slug($dimName, '_') }}[]" value="{{ $val }}" {{ $checked ? 'checked' : '' }}
                                                   onchange="this.form.submit()"
                                                   class="rounded border-light-gold text-maroon-800 focus:ring-maroon-800">
                                            <span class="truncate">{{ $val }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                    <!-- Price Range -->
                    <hr class="border-light-gold/60">
                    <div class="space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-rich-black/80">Price Range (₹)</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number"
                                   name="min_price"
                                   placeholder="Min ₹"
                                   value="{{ $filters['min_price'] ?? '' }}"
                                   class="w-full text-xs rounded-lg border-light-gold bg-ivory/50 px-2.5 py-2 focus:border-maroon-800 focus:ring-maroon-800">
                            <input type="number"
                                   name="max_price"
                                   placeholder="Max ₹"
                                   value="{{ $filters['max_price'] ?? '' }}"
                                   class="w-full text-xs rounded-lg border-light-gold bg-ivory/50 px-2.5 py-2 focus:border-maroon-800 focus:ring-maroon-800">
                        </div>
                    </div>

                    <!-- In-Stock Only -->
                    <hr class="border-light-gold/60">
                    <div class="flex items-center gap-2.5">
                        <input type="checkbox"
                                id="mobile_in_stock"
                                name="in_stock"
                                value="1"
                                {{ !empty($filters['in_stock']) ? 'checked' : '' }}
                                onchange="this.form.submit()"
                                class="rounded border-light-gold text-maroon-800 focus:ring-maroon-800">
                        <label for="mobile_in_stock" class="text-xs font-medium text-rich-black/80 cursor-pointer">In Stock Only</label>
                    </div>

                    <!-- Bottom Buttons -->
                    <div class="pt-4 border-t border-light-gold/60 flex gap-2">
                        <button type="submit"
                                class="flex-1 py-2.5 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shadow-xs">
                            Apply Filters
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ url()->current() }}"
                               class="px-3.5 py-2.5 rounded-lg border border-light-gold text-rich-black/60 text-xs font-medium hover:bg-slate-50 transition-colors text-center">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Grid: Sidebar + Products -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Filter Rail (Desktop) -->
        <aside class="hidden lg:block lg:col-span-3 bg-white p-6 rounded-xl border border-light-gold space-y-6 sticky top-28 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-light-gold/60">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-rich-black/80">Filters</span>
                    @if($activeFilterCount > 0)
                        <span class="w-5 h-5 rounded-full bg-maroon-800 text-white text-[0.625rem] font-bold flex items-center justify-center">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                </div>
                @if($hasActiveFilters)
                    <a href="{{ url()->current() . (!empty($filters['sort']) && $filters['sort'] !== 'popular' ? '?sort=' . $filters['sort'] : '') }}"
                       class="text-[0.6875rem] text-maroon-800 hover:underline font-semibold">
                        Reset All
                    </a>
                @endif
            </div>

            <form action="{{ url()->current() }}" method="GET" class="space-y-6">
                
                <!-- Sort option -->
                <div class="space-y-2">
                    <label for="desktop_sort" class="block text-xs font-bold uppercase tracking-wider text-rich-black/80">Sort By</label>
                    <select name="sort" id="desktop_sort" onchange="this.form.submit()"
                            class="w-full text-xs rounded-lg border-light-gold bg-ivory/50 focus:border-maroon-800 focus:ring-maroon-800 py-2">
                        <option value="popular" {{ ($filters['sort'] ?? '') === 'popular' ? 'selected' : '' }}>Most Popular</option>
                        <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest Arrivals</option>
                        <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    </select>
                </div>

                <!-- Dynamic Color Filter -->
                @if(isset($availableOptions['Color']) && count($availableOptions['Color']) > 0)
                    <hr class="border-light-gold/60">
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-rich-black/80">Color</h3>
                            @if(!empty($selectedOptions['Color']))
                                <span class="text-[0.6875rem] text-maroon-800 font-semibold">{{ count($selectedOptions['Color']) }} selected</span>
                            @endif
                        </div>
                        <div class="space-y-1.5 max-h-52 overflow-y-auto pr-1">
                            @foreach($availableOptions['Color'] as $color)
                                @php
                                    $hex = $colorHexMap[strtolower(trim($color))] ?? '#cbd5e1';
                                    $isWhite = in_array(strtolower(trim($color)), ['white', 'off-white', 'off white', 'ivory']);
                                    $checked = in_array($color, $selectedOptions['Color'] ?? []);
                                @endphp
                                <label class="flex items-center gap-2.5 cursor-pointer py-1 text-xs text-rich-black/80 hover:text-maroon-800 transition-colors">
                                    <input type="checkbox" name="color[]" value="{{ $color }}" {{ $checked ? 'checked' : '' }}
                                           onchange="this.form.submit()"
                                           class="rounded border-light-gold text-maroon-800 focus:ring-maroon-800">
                                    <span class="w-3.5 h-3.5 rounded-full inline-block shrink-0 shadow-xs {{ $isWhite ? 'border border-slate-300' : '' }}" style="background-color: {{ $hex }};"></span>
                                    <span class="truncate">{{ $color }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Dynamic Size Filter -->
                @if(isset($availableOptions['Size']) && count($availableOptions['Size']) > 0)
                    <hr class="border-light-gold/60">
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-rich-black/80">Size</h3>
                            @if(!empty($selectedOptions['Size']))
                                <span class="text-[0.6875rem] text-maroon-800 font-semibold">{{ count($selectedOptions['Size']) }} selected</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($availableOptions['Size'] as $size)
                                @php
                                    $checked = in_array($size, $selectedOptions['Size'] ?? []);
                                @endphp
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="size[]" value="{{ $size }}" {{ $checked ? 'checked' : '' }} onchange="this.form.submit()" class="peer sr-only">
                                    <span class="min-w-[2.5rem] h-8 px-2.5 rounded-lg border text-xs font-semibold flex items-center justify-center transition-all peer-checked:bg-maroon-800 peer-checked:text-white peer-checked:border-maroon-800 border-light-gold text-rich-black/80 hover:border-maroon-800 bg-white shadow-xs">
                                        {{ $size }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Dynamic Other Variations -->
                @foreach($availableOptions as $dimName => $dimValues)
                    @if(!in_array(strtolower($dimName), ['color', 'size']))
                        <hr class="border-light-gold/60">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-rich-black/80">{{ $dimName }}</h3>
                                @if(!empty($selectedOptions[$dimName]))
                                    <span class="text-[0.6875rem] text-maroon-800 font-semibold">{{ count($selectedOptions[$dimName]) }} selected</span>
                                @endif
                            </div>
                            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                @foreach($dimValues as $val)
                                    @php
                                        $checked = in_array($val, $selectedOptions[$dimName] ?? []);
                                    @endphp
                                    <label class="flex items-center gap-2.5 cursor-pointer py-1 text-xs text-rich-black/80 hover:text-maroon-800 transition-colors">
                                        <input type="checkbox" name="{{ Str::slug($dimName, '_') }}[]" value="{{ $val }}" {{ $checked ? 'checked' : '' }}
                                               onchange="this.form.submit()"
                                               class="rounded border-light-gold text-maroon-800 focus:ring-maroon-800">
                                        <span class="truncate">{{ $val }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                <hr class="border-light-gold/60">

                <!-- Price Range -->
                <div class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-rich-black/80">Price Range (₹)</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number"
                               name="min_price"
                               placeholder="Min ₹"
                               value="{{ $filters['min_price'] ?? '' }}"
                               class="w-full text-xs rounded-lg border-light-gold bg-ivory/50 px-2.5 py-1.5 focus:border-maroon-800 focus:ring-maroon-800">
                        <input type="number"
                               name="max_price"
                               placeholder="Max ₹"
                               value="{{ $filters['max_price'] ?? '' }}"
                               class="w-full text-xs rounded-lg border-light-gold bg-ivory/50 px-2.5 py-1.5 focus:border-maroon-800 focus:ring-maroon-800">
                    </div>
                </div>

                <hr class="border-light-gold/60">

                <!-- In-Stock Only -->
                <div class="flex items-center gap-2">
                    <input type="checkbox"
                           id="desktop_in_stock"
                           name="in_stock"
                           value="1"
                           {{ !empty($filters['in_stock']) ? 'checked' : '' }}
                           onchange="this.form.submit()"
                           class="rounded border-light-gold text-maroon-800 focus:ring-maroon-800">
                    <label for="desktop_in_stock" class="text-xs font-medium text-rich-black/80 cursor-pointer">In Stock Only</label>
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shadow-xs">
                        Apply Filters
                    </button>
                    @if($hasActiveFilters)
                        <a href="{{ url()->current() }}"
                           class="px-3 py-2.5 rounded-lg border border-light-gold text-rich-black/60 text-xs font-medium hover:bg-slate-50 transition-colors text-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </aside>

        <!-- Right: Products Grid & Active Filter Pills -->
        <main class="lg:col-span-9 space-y-6">
            
            <!-- Active Filter Chips -->
            @if($hasActiveFilters)
                <div class="flex flex-wrap items-center gap-2 p-3 bg-white rounded-xl border border-light-gold/80 shadow-xs">
                    <span class="text-[0.6875rem] font-bold uppercase tracking-wider text-rich-black/50 mr-1">Active:</span>
                    
                    @if(!empty($filters['min_price']) || !empty($filters['max_price']))
                        <a href="{{ request()->fullUrlWithQuery(['min_price' => null, 'max_price' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-ivory border border-light-gold text-xs text-rich-black/80 hover:border-maroon-800 hover:text-maroon-800 transition-colors"
                           title="Remove price filter">
                            <span>₹{{ $filters['min_price'] ?? '0' }} – ₹{{ $filters['max_price'] ?? 'Above' }}</span>
                            <span class="font-bold text-rich-black/40 hover:text-maroon-800">×</span>
                        </a>
                    @endif

                    @if(!empty($filters['in_stock']))
                        <a href="{{ request()->fullUrlWithQuery(['in_stock' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-ivory border border-light-gold text-xs text-rich-black/80 hover:border-maroon-800 hover:text-maroon-800 transition-colors"
                           title="Remove in-stock filter">
                            <span>In Stock Only</span>
                            <span class="font-bold text-rich-black/40 hover:text-maroon-800">×</span>
                        </a>
                    @endif

                    @foreach($selectedOptions as $dim => $vals)
                        @foreach($vals as $val)
                            @php
                                $remVals = array_values(array_diff($vals, [$val]));
                                $paramKey = strtolower($dim);
                                $clearUrl = request()->fullUrlWithQuery([
                                    $paramKey => !empty($remVals) ? $remVals : null,
                                ]);
                            @endphp
                            <a href="{{ $clearUrl }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-maroon-50 border border-maroon-200 text-xs text-maroon-800 hover:bg-maroon-100 transition-colors"
                               title="Remove {{ $dim }}: {{ $val }}">
                                <span class="font-semibold">{{ $dim }}:</span>
                                <span>{{ $val }}</span>
                                <span class="font-bold text-maroon-600">×</span>
                            </a>
                        @endforeach
                    @endforeach

                    <a href="{{ url()->current() . (!empty($filters['sort']) && $filters['sort'] !== 'popular' ? '?sort=' . $filters['sort'] : '') }}"
                       class="text-xs text-maroon-800 hover:underline font-semibold ml-auto">
                        Clear All
                    </a>
                </div>
            @endif

            @if($products->isEmpty())
                <div class="bg-white rounded-2xl border border-light-gold p-12 text-center space-y-4 shadow-xs">
                    <div class="w-16 h-16 mx-auto rounded-full bg-gold-200/50 flex items-center justify-center text-maroon-800 text-2xl">
                        🔍
                    </div>
                    <h3 class="font-serif text-2xl text-rich-black font-medium">No products match your filters</h3>
                    <p class="text-xs text-rich-black/60 max-w-sm mx-auto">Try clearing your filters or choosing different options to find what you're looking for.</p>
                    <a href="{{ url()->current() }}" class="inline-block px-6 py-2.5 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shadow-xs">
                        Reset All Filters
                    </a>
                </div>
            @else
                <!-- Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
                    @foreach($productCards ?? $products as $product)
                        @php
                            $firstVariant = $product->firstVariant ?? $product->variants->whereNull('archived_at')->first();
                        @endphp
                        <div class="group bg-white rounded-xl p-3 border border-light-gold/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                            
                            <div>
                                <!-- Image Container (3:4 ratio for fashion, 1:1 square for smart living) -->
                                <div class="relative {{ $isSmartLivingHub ? 'aspect-square' : 'aspect-[3/4]' }} rounded-lg overflow-hidden bg-warm-beige mb-3">
                                    <a href="{{ $product->url ?? url('/product/' . $product->slug) }}">
                                        <img src="{{ $product->primary_image_url }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                             loading="lazy">
                                    </a>

                                    @if($product->discount_percentage)
                                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded bg-maroon-800 text-white text-[0.625rem] font-bold tracking-wide uppercase">
                                            {{ $product->discount_percentage }}% OFF
                                        </span>
                                    @endif

                                    @if(!empty($product->color_name))
                                        <span class="absolute bottom-2 left-2 px-2 py-0.5 rounded-full bg-white/95 backdrop-blur-xs text-[0.625rem] font-bold text-rich-black shadow-xs flex items-center gap-1.5 border border-light-gold/60">
                                            <span class="w-2 h-2 rounded-full border border-black/10 inline-block" style="background-color: {{ $product->color_hex ?? '#8b4513' }};"></span>
                                            <span>{{ $product->color_name }}</span>
                                        </span>
                                    @endif

                                    <!-- Wishlist Heart Button -->
                                    <button type="button"
                                            @click.stop.prevent="toggleWishlist({{ $product->id }})"
                                            class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white/90 backdrop-blur-xs flex items-center justify-center shadow-xs hover:scale-110 transition-transform z-10"
                                            :title="isInWishlist({{ $product->id }}) ? 'Remove from wishlist' : 'Add to wishlist'"
                                            :aria-label="isInWishlist({{ $product->id }}) ? 'Remove from wishlist' : 'Add to wishlist'">
                                        <svg class="w-4 h-4 transition-colors"
                                             :class="isInWishlist({{ $product->id }}) ? 'fill-rose-500 text-rose-500' : 'text-slate-400 hover:text-rose-500'"
                                             viewBox="0 0 24 24"
                                             fill="none"
                                             stroke="currentColor"
                                             stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Product Info -->
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between gap-1">
                                        <p class="text-[0.625rem] uppercase tracking-wider text-rich-black/50 font-semibold truncate">
                                            {{ $product->brand ?? 'DHANSREE' }}
                                        </p>
                                        @if(!empty($product->color_name))
                                            <span class="text-[0.625rem] font-medium text-maroon-800 truncate">
                                                {{ $product->color_name }}
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="text-xs sm:text-sm font-medium text-rich-black line-clamp-2 h-10 leading-snug">
                                        <a href="{{ $product->url ?? url('/product/' . $product->slug) }}" class="hover:text-maroon-800 transition-colors">
                                            {{ $product->name }}
                                        </a>
                                    </h3>

                                    <!-- Price Display -->
                                    <div class="flex items-baseline gap-2 pt-1">
                                        <span class="text-sm sm:text-base font-bold text-maroon-800">
                                            {{ $product->formatted_price }}
                                        </span>
                                        @if($product->formatted_mrp)
                                            <del class="text-xs text-rich-black/40">
                                                {{ $product->formatted_mrp }}
                                            </del>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Add CTA Button -->
                            <div class="mt-3 pt-2 border-t border-light-gold/50">
                                @if($firstVariant)
                                    <button type="button"
                                            @click="addToCart({{ $firstVariant->id }}, 1)"
                                            class="w-full py-2.5 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-semibold hover:bg-maroon-800 hover:text-white transition-all flex items-center justify-center gap-1.5 shadow-xs">
                                        <span>+</span>
                                        <span>Quick Add</span>
                                    </button>
                                @else
                                    <a href="{{ $product->url ?? url('/product/' . $product->slug) }}"
                                       class="w-full py-2.5 rounded-lg border border-light-gold text-rich-black/60 text-xs font-semibold text-center block hover:border-maroon-800 hover:text-maroon-800 transition-colors">
                                        View Details
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="pt-8">
                    {{ $products->links() }}
                </div>
            @endif

        </main>
    </div>
</div>
@endsection
