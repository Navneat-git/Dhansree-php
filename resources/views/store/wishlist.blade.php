@extends('layouts.store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-14">
    
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-rich-black/50 mb-6" aria-label="Breadcrumb">
        <a href="{{ url('/') }}" class="hover:text-maroon-800 transition-colors">Home</a>
        <span>&rsaquo;</span>
        <span class="text-rich-black font-semibold">My Wishlist</span>
    </nav>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">My Wishlist</h1>
            <p class="text-xs sm:text-sm text-rich-black/60 mt-1">
                Keep track of your favorite styles and easily add them to your shopping bag.
            </p>
        </div>
        @if($products->isNotEmpty())
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                <svg class="w-3.5 h-3.5 fill-rose-500 text-rose-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                </svg>
                <span>{{ $products->count() }} {{ Str::plural('item', $products->count()) }}</span>
            </span>
        @endif
    </div>

    @if($products->isEmpty())
        <!-- Empty Wishlist State -->
        <div class="bg-white rounded-2xl border border-light-gold p-12 sm:p-16 text-center space-y-6 shadow-xs max-w-2xl mx-auto my-8">
            <div class="w-20 h-20 mx-auto rounded-full bg-rose-50 border border-rose-200 flex items-center justify-center text-rose-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
            </div>
            
            <div class="space-y-2">
                <h2 class="font-serif text-2xl sm:text-3xl text-rich-black font-medium">Your wishlist is currently empty</h2>
                <p class="text-xs sm:text-sm text-rich-black/60 max-w-md mx-auto">
                    Save items you love by tapping the heart icon on any product card or detail page.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <a href="{{ url('/') }}"
                   class="w-full sm:w-auto px-6 py-3 rounded-lg bg-maroon-800 text-white text-xs font-semibold uppercase tracking-wider hover:bg-maroon-900 transition-colors shadow-sm">
                    🌸 Explore Women's Fashion
                </a>
                <a href="{{ url('/smart-living') }}"
                   class="w-full sm:w-auto px-6 py-3 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-semibold uppercase tracking-wider hover:bg-maroon-50 transition-colors">
                    ⚡ Explore Smart Living
                </a>
            </div>
        </div>
    @else
        <!-- Products Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            @foreach($products as $product)
                @php
                    $primaryVariant = $product->variants->first();
                    $inStock = $product->variants->contains(fn($v) => ($v->inventoryLevels->sum('on_hand') - $v->inventoryLevels->sum('reserved')) > 0);
                @endphp
                <div class="group bg-white rounded-xl p-3 border border-light-gold/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between relative">
                    
                    <div>
                        <!-- Image Container with Remove Button -->
                        <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-warm-beige mb-3">
                            <a href="{{ url('/product/' . $product->slug) }}">
                                <img src="{{ $product->primary_image_url }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                     loading="lazy">
                            </a>

                            <!-- Remove Button -->
                            <button type="button"
                                    @click="toggleWishlist({{ $product->id }}); window.location.reload();"
                                    class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white/90 backdrop-blur-xs flex items-center justify-center text-rose-500 shadow-sm hover:scale-110 hover:bg-rose-50 transition-all z-10"
                                    title="Remove from wishlist"
                                    aria-label="Remove from wishlist">
                                <svg class="w-4 h-4 fill-rose-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <!-- Product Metadata -->
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
                            <div class="pt-1 flex items-baseline gap-2 flex-wrap">
                                <span class="text-sm sm:text-base font-bold text-maroon-800">
                                    {{ $product->formatted_price }}
                                </span>
                                @if($primaryVariant && $primaryVariant->mrp_rupees > $primaryVariant->price_rupees)
                                    <span class="text-xs text-rich-black/40 line-through">
                                        ₹{{ number_format($primaryVariant->mrp_rupees, 0) }}
                                    </span>
                                    <span class="text-[0.625rem] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                        {{ round((($primaryVariant->mrp_rupees - $primaryVariant->price_rupees) / $primaryVariant->mrp_rupees) * 100) }}% OFF
                                    </span>
                                @endif
                            </div>

                            @if(!$inStock)
                                <p class="text-[0.6875rem] text-rose-600 font-semibold">Out of Stock</p>
                            @endif
                        </div>
                    </div>

                    <!-- Action Button: Add to Bag or Select Options -->
                    <div class="mt-3 pt-2 border-t border-light-gold/40">
                        @if($product->variants->count() === 1 && $inStock && $primaryVariant)
                            <button type="button"
                                    @click="addToCart({{ $primaryVariant->id }}, 1)"
                                    class="w-full py-2.5 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-semibold hover:bg-maroon-800 hover:text-white transition-all flex items-center justify-center gap-1.5 shadow-xs">
                                <span>🛍️</span>
                                <span>Add to Bag</span>
                            </button>
                        @else
                            <a href="{{ url('/product/' . $product->slug) }}"
                               class="w-full py-2.5 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-semibold text-center block hover:bg-maroon-800 hover:text-white transition-all shadow-xs">
                                {{ $inStock ? 'Select Size & Buy' : 'View Product' }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
