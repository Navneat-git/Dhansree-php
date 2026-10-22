@extends('layouts.store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    
    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-rich-black/50 mb-6">
        <a href="{{ $isSmartLivingHub ? url('/smart-living') : url('/') }}" class="hover:text-maroon-800 transition-colors">
            {{ $isSmartLivingHub ? 'Smart Living' : 'Home' }}
        </a>
        <span>/</span>
        @if($category->parent)
            <a href="{{ url('/' . $category->parent->slug) }}" class="hover:text-maroon-800 transition-colors">
                {{ $category->parent->name }}
            </a>
            <span>/</span>
        @endif
        <span class="text-rich-black/80 font-medium">{{ $category->name }}</span>
    </nav>

    <!-- Header & Description -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-6 border-b border-light-gold/60 mb-8">
        <div class="space-y-2 max-w-2xl">
            <span class="text-[0.6875rem] uppercase tracking-widest text-gold-600 font-bold">Collection</span>
            <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl text-maroon-800 font-normal leading-tight">
                {{ $category->name }}
            </h1>
            @if($category->description)
                <p class="text-xs sm:text-sm text-rich-black/70 leading-relaxed">
                    {{ Str::limit(strip_tags($category->description), 280) }}
                </p>
            @endif
        </div>
        <div class="text-xs text-rich-black/50 shrink-0 font-medium">
            Showing {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Filter Rail (Desktop) -->
        <aside class="hidden lg:block lg:col-span-3 bg-white p-6 rounded-xl border border-light-gold space-y-6 sticky top-28 shadow-xs">
            <form action="{{ url()->current() }}" method="GET" class="space-y-6">
                
                <!-- Sort option -->
                <div class="space-y-2">
                    <label for="sort" class="block text-xs font-bold uppercase tracking-wider text-rich-black/80">Sort By</label>
                    <select name="sort" id="sort" onchange="this.form.submit()"
                            class="w-full text-xs rounded-lg border-light-gold bg-ivory/50 focus:border-maroon-800 focus:ring-maroon-800 py-2">
                        <option value="popular" {{ ($filters['sort'] ?? '') === 'popular' ? 'selected' : '' }}>Most Popular</option>
                        <option value="newest" {{ ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' }}>Newest Arrivals</option>
                        <option value="price_asc" {{ ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    </select>
                </div>

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
                           id="in_stock"
                           name="in_stock"
                           value="1"
                           {{ !empty($filters['in_stock']) ? 'checked' : '' }}
                           onchange="this.form.submit()"
                           class="rounded border-light-gold text-maroon-800 focus:ring-maroon-800">
                    <label for="in_stock" class="text-xs font-medium text-rich-black/80">In Stock Only</label>
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="submit"
                            class="flex-1 py-2 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shadow-xs">
                        Apply
                    </button>
                    @if(!empty($filters))
                        <a href="{{ url()->current() }}"
                           class="px-3 py-2 rounded-lg border border-light-gold text-rich-black/60 text-xs font-medium hover:bg-slate-50 transition-colors text-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </aside>

        <!-- Right: Products Grid -->
        <main class="lg:col-span-9 space-y-8">
            
            @if($products->isEmpty())
                <div class="bg-white rounded-2xl border border-light-gold p-12 text-center space-y-4 shadow-xs">
                    <div class="w-16 h-16 mx-auto rounded-full bg-gold-200/50 flex items-center justify-center text-maroon-800 text-2xl">
                        🔍
                    </div>
                    <h3 class="font-serif text-2xl text-rich-black font-medium">No products match your filters</h3>
                    <p class="text-xs text-rich-black/60 max-w-sm mx-auto">Try clearing your price filters or browsing another category to find what you're looking for.</p>
                    <a href="{{ url()->current() }}" class="inline-block px-6 py-2.5 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shadow-xs">
                        Reset Filters
                    </a>
                </div>
            @else
                <!-- Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
                    @foreach($products as $product)
                        @php
                            $firstVariant = $product->variants->whereNull('archived_at')->first();
                        @endphp
                        <div class="group bg-white rounded-xl p-3 border border-light-gold/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                            
                            <div>
                                <!-- Image Container (3:4 ratio) -->
                                <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-warm-beige mb-3">
                                    <a href="{{ url('/product/' . $product->slug) }}">
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
                                </div>

                                <!-- Product Info -->
                                <div class="space-y-1">
                                    <p class="text-[0.625rem] uppercase tracking-wider text-rich-black/50 font-semibold truncate">
                                        {{ $product->brand ?? 'DHANSREE' }}
                                    </p>
                                    <h3 class="text-xs sm:text-sm font-medium text-rich-black line-clamp-2 h-10 leading-snug">
                                        <a href="{{ url('/product/' . $product->slug) }}" class="hover:text-maroon-800 transition-colors">
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
                                    <a href="{{ url('/product/' . $product->slug) }}"
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
