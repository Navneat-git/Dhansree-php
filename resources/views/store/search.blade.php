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
                    $prodVideo = $product->approved_video;
                @endphp
                <div class="group bg-white rounded-xl p-3 border border-light-gold/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                     x-data="{ isHovered: false }"
                     @mouseenter="isHovered = true; if ($refs.cardVideo) { $refs.cardVideo.play().catch(() => {}); }"
                     @mouseleave="isHovered = false; if ($refs.cardVideo) { $refs.cardVideo.pause(); $refs.cardVideo.currentTime = 0; }">
                    <div>
                        <!-- Image Container (3:4 ratio) with video play on hover -->
                        <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-warm-beige mb-3">
                            <a href="{{ $product->url ?? url('/product/' . $product->slug) }}" class="block w-full h-full relative">
                                <img src="{{ $product->primary_image_url }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                     loading="lazy">

                                @if($prodVideo)
                                    <video x-ref="cardVideo"
                                           x-show="isHovered"
                                           playsinline
                                           loop
                                           muted
                                           preload="none"
                                           class="absolute inset-0 w-full h-full object-cover">
                                        @if($prodVideo->webm_url)
                                            <source src="{{ $prodVideo->webm_url }}" type="video/webm">
                                        @endif
                                        <source src="{{ $prodVideo->video_url }}" type="video/mp4">
                                    </video>
                                @endif
                            </a>

                            <!-- Top Left: Reel Video Button -->
                            @if($prodVideo)
                                <button type="button"
                                        @click.stop.prevent="window.dhansreeOpenReel({
                                            videoUrl: '{{ addslashes($prodVideo->video_url) }}',
                                            webmUrl: '{{ addslashes($prodVideo->webm_url ?? '') }}',
                                            poster: '{{ addslashes($prodVideo->thumbnail_url ?? $product->primary_image_url) }}',
                                            productName: '{{ addslashes($product->name) }}',
                                            productUrl: '{{ addslashes($product->url ?? url('/product/' . $product->slug)) }}',
                                            price: '{{ addslashes($product->formatted_price) }}',
                                            mrp: '{{ addslashes($product->formatted_mrp ?? '') }}',
                                            discount: '{{ $product->discount_percentage ? $product->discount_percentage . '% OFF' : '' }}',
                                            isAi: {{ $prodVideo->isAiGenerated() ? 'true' : 'false' }},
                                            variantId: {{ $firstVariant?->id ?? 'null' }}
                                        })"
                                        class="absolute top-2 left-2 z-20 pointer-events-auto h-5 sm:h-6 px-1.5 sm:px-2 rounded-full bg-white/90 hover:bg-white text-maroon-800 text-[10px] font-bold tracking-wide flex items-center justify-center gap-1 border border-light-gold/80 shadow-xs hover:scale-105 active:scale-95 transition-all cursor-pointer backdrop-blur-xs"
                                        title="Watch video reel in motion"
                                        aria-label="Play product video"
                                        style="line-height: 1;">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 fill-maroon-800 shrink-0 translate-x-[0.5px]" viewBox="0 0 24 24" style="width: 10px; height: 10px;"><path d="M8 5v14l11-7z"/></svg>
                                    <span class="hidden sm:inline text-[9.5px] font-bold text-rich-black uppercase tracking-tight">Reel</span>
                                </button>
                            @endif

                            <!-- Top Right: Wishlist Heart Button -->
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

                            <!-- Bottom Left: Color Badge -->
                            @if(!empty($product->color_name))
                                <div class="absolute bottom-2 left-2 z-10 pointer-events-none max-w-[calc(100%-80px)]">
                                    <span class="px-1.5 py-0.5 rounded-full bg-white/95 backdrop-blur-xs font-bold text-rich-black shadow-xs flex items-center gap-1 border border-light-gold/60 shrink-0"
                                          style="font-size: 9px; line-height: 1.2;">
                                        <span class="w-1.5 h-1.5 rounded-full border border-black/10 inline-block shrink-0" style="background-color: {{ $product->color_hex ?? '#8b4513' }};"></span>
                                        <span class="truncate max-w-[50px]">{{ $product->color_name }}</span>
                                    </span>
                                </div>
                            @endif

                            <!-- Bottom Right: % OFF Badge -->
                            @if($product->discount_percentage)
                                <div class="absolute bottom-2 right-2 z-10 pointer-events-none">
                                    <span class="px-3 py-0.9 rounded-full bg-maroon-800 text-white font-bold tracking-tight uppercase shadow-xs shrink-0 inline-block"
                                          style="font-size: 8.5px; line-height: 1.2; padding-top: 2px; padding-bottom: 2px;">
                                        {{ $product->discount_percentage }}% OFF
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Product Info -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between gap-1">
                                <p class="text-[0.625rem] uppercase tracking-wider text-rich-black/50 font-semibold truncate">
                                    {{ $product->brand ?? 'DHANSREE TRENDS' }}
                                </p>
                            </div>
                            <h3 class="text-xs sm:text-sm font-medium text-rich-black leading-snug line-clamp-2 min-h-[2.5rem]"
                                style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"
                                title="{{ $product->name }}">
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
                                @if($product->discount_percentage)
                                    <span class="font-bold text-emerald-700" style="font-size: 11px;">
                                        {{ $product->discount_percentage }}% OFF
                                    </span>
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
