@extends('layouts.store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-14">
    
    <div class="text-center max-w-xl mx-auto space-y-3 mb-10">
        <h1 class="font-serif text-3xl sm:text-4xl text-maroon-800 font-normal">
            @if($query !== '')
                Search results for <span class="italic text-rich-black">"{{ $query }}"</span>
            @else
                Search Our Collections
            @endif
        </h1>
        <p class="text-xs text-rich-black/60">
            @if($query !== '')
                Found {{ $products->total() }} matching {{ Str::plural('item', $products->total()) }}
            @else
                Find ethnic kurtas, co-ords, suit sets, and smart living gadgets.
            @endif
        </p>

        <!-- Inline search input -->
        <form action="{{ url('/search') }}" method="GET" class="relative max-w-md mx-auto pt-2">
            <input type="text"
                   name="q"
                   value="{{ $query }}"
                   placeholder="Search products, fabrics, styles, gadgets..."
                   class="w-full px-4 py-3 pr-12 rounded-xl border border-light-gold bg-white text-xs text-rich-black placeholder-rich-black/40 focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none shadow-xs">
            <button type="submit"
                    class="absolute right-2 top-4 p-1.5 text-rich-black/50 hover:text-maroon-800"
                    aria-label="Submit search">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </button>
        </form>
    </div>

    @if($products->isEmpty())
        <div class="bg-white rounded-2xl border border-light-gold p-12 text-center space-y-4 shadow-xs max-w-lg mx-auto">
            <div class="w-16 h-16 mx-auto rounded-full bg-gold-200/50 flex items-center justify-center text-maroon-800 text-2xl">
                🔎
            </div>
            <h2 class="font-serif text-2xl text-rich-black font-medium">No results found</h2>
            <p class="text-xs text-rich-black/60">
                We couldn't find any items matching "{{ $query }}". Check your spelling or browse our popular categories.
            </p>
            <div class="pt-2 flex flex-wrap justify-center gap-2 text-xs">
                <a href="{{ url('/kurta-set') }}" class="px-4 py-2 rounded-full border border-light-gold bg-ivory hover:border-maroon-800 hover:text-maroon-800 transition-colors">
                    Kurta Sets
                </a>
                <a href="{{ url('/women-two-piece-sets-co-ord') }}" class="px-4 py-2 rounded-full border border-light-gold bg-ivory hover:border-maroon-800 hover:text-maroon-800 transition-colors">
                    Co-ords
                </a>
                <a href="{{ url('/smart-living') }}" class="px-4 py-2 rounded-full border border-light-gold bg-ivory hover:border-amber-800 hover:text-amber-800 transition-colors">
                    Smart Living
                </a>
            </div>
        </div>
    @else
        <!-- Products Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            @foreach($productCards ?? $products as $product)
                @php
                    $firstVariant = $product->firstVariant ?? $product->variants->whereNull('archived_at')->first();
                @endphp
                <div class="group bg-white rounded-xl p-3 border border-light-gold/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                        <!-- Image Container (3:4 ratio) -->
                        <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-warm-beige mb-3">
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

                            @if($product->approved_video)
                                <span class="absolute top-2 right-12 px-2 py-0.5 rounded-full bg-slate-900/80 backdrop-blur-xs text-amber-300 text-[10px] font-bold tracking-wide flex items-center gap-1 border border-amber-400/40 shadow-xs z-10"
                                      title="Watch fashion reel in motion">
                                    <span class="size-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                    <span>▶ Reel</span>
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

        <div class="pt-8">
            {{ $products->links() }}
        </div>
    @endif

</div>
@endsection
