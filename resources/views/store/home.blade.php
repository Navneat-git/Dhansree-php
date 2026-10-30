@extends('layouts.store')

@section('content')
<!-- Hero Section (Dynamic Banners or Brand Fallback) -->
@if(!empty($banners) && $banners->isNotEmpty())
<section class="relative bg-warm-beige/40 border-b border-light-gold overflow-hidden"
         x-data="{
             active: 0,
             total: {{ $banners->count() }},
             timer: null,
             start() {
                 this.timer = setInterval(() => { this.next() }, 6000);
             },
             stop() {
                 clearInterval(this.timer);
             },
             next() {
                 this.active = (this.active + 1) % this.total;
             },
             prev() {
                 this.active = (this.active - 1 + this.total) % this.total;
             }
         }"
         x-init="start()"
         @mouseenter="stop()"
         @mouseleave="start()">
    
    <div class="relative w-full aspect-[4/5] sm:aspect-[16/5] overflow-hidden bg-warm-beige flex items-center">
        @foreach($banners as $idx => $b)
            @php
                $veiled = ($b->word_mode === 'ON_LIGHTENED');
                $light = ($b->word_mode === 'LIGHT_ON_PICTURE');
                $hasWords = !empty($b->title) || !empty($b->eyebrow) || !empty($b->subtitle);
            @endphp
            <div x-show="active === {{ $idx }}"
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 -translate-x-4"
                 class="absolute inset-0 w-full h-full flex items-center">
                
                <!-- Background Image (Crisp & High Priority for First Slide) -->
                @if($b->image_url)
                    <picture class="absolute inset-0 w-full h-full">
                        @if($b->mobile_image_url)
                            <source media="(max-width: 640px)" srcset="{{ $b->mobile_image_url }}">
                        @endif
                        <img src="{{ $b->image_url }}"
                             alt="{{ $b->image_alt_text ?? $b->title }}"
                             class="w-full h-full object-cover"
                             loading="{{ $idx === 0 ? 'eager' : 'lazy' }}"
                             fetchpriority="{{ $idx === 0 ? 'high' : 'auto' }}">
                    </picture>
                @endif

                <!-- Readability Overlays: Zero desktop veil for ON_PICTURE and LIGHT_ON_PICTURE -->
                @if($hasWords)
                    {{-- Mobile scrim for cropped 4:5 phone screens --}}
                    @if($light)
                        <div aria-hidden class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/25 to-transparent sm:hidden pointer-events-none"></div>
                    @else
                        <div aria-hidden class="absolute inset-0 bg-gradient-to-b from-ivory/85 via-ivory/35 to-transparent sm:hidden pointer-events-none"></div>
                    @endif

                    {{-- Desktop veil ONLY for ON_LIGHTENED mode --}}
                    @if($veiled)
                        <div aria-hidden class="absolute inset-0 hidden sm:block bg-gradient-to-r from-ivory/90 via-ivory/60 to-transparent w-2/3 pointer-events-none"></div>
                    @endif

                    <!-- Text Overlay Content -->
                    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16 w-full z-10">
                        <div class="max-w-xl space-y-3 sm:space-y-5 text-center sm:text-left {{ $b->word_mode === 'LIGHT_ON_PICTURE' ? 'text-white' : 'text-rich-black' }}">
                            @if($b->eyebrow)
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider {{ $b->word_mode === 'LIGHT_ON_PICTURE' ? 'bg-white/20 text-gold-300 border border-white/30' : 'bg-gold-200/70 border border-gold-400 text-maroon-900' }}">
                                    <span>✨</span>
                                    <span>{{ $b->eyebrow }}</span>
                                </div>
                            @endif

                            <h1 class="font-serif text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-normal leading-[1.15] tracking-tight">
                                {{ $b->title }}
                            </h1>

                            @if($b->subtitle)
                                <p class="text-xs sm:text-sm md:text-base leading-relaxed {{ $b->word_mode === 'LIGHT_ON_PICTURE' ? 'text-slate-200' : 'text-rich-black/75' }}">
                                    {{ $b->subtitle }}
                                </p>
                            @endif

                            @if($b->link_path)
                                <div class="pt-2">
                                    <a href="{{ url($b->link_path) }}"
                                       class="inline-block px-8 py-3.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all shadow-md {{ $b->word_mode === 'LIGHT_ON_PICTURE' ? 'bg-amber-400 text-slate-950 hover:bg-amber-300' : 'bg-maroon-800 text-white hover:bg-maroon-900' }}">
                                        {{ $b->cta_label ?: 'Shop Now' }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif($b->link_path)
                    <!-- Entire slide is clickable if no explicit button -->
                    <a href="{{ url($b->link_path) }}" class="absolute inset-0 z-10" aria-label="{{ $b->image_alt_text ?? 'Promotion' }}"></a>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Navigation Dots & Arrows -->
    @if($banners->count() > 1)
        <div class="absolute bottom-6 left-0 right-0 z-20 flex items-center justify-center gap-2">
            @foreach($banners as $idx => $b)
                <button type="button"
                        @click="active = {{ $idx }}"
                        class="size-2.5 rounded-full transition-all cursor-pointer"
                        :class="active === {{ $idx }} ? 'bg-amber-500 w-8' : 'bg-slate-400/60 hover:bg-slate-500'">
                </button>
            @endforeach
        </div>
        <button type="button"
                @click="prev()"
                class="hidden sm:flex absolute left-4 top-1/2 -translate-y-1/2 z-20 size-10 rounded-full bg-white/80 hover:bg-white text-slate-800 items-center justify-center shadow-md backdrop-blur-xs transition-colors cursor-pointer"
                title="Previous">
            ‹
        </button>
        <button type="button"
                @click="next()"
                class="hidden sm:flex absolute right-4 top-1/2 -translate-y-1/2 z-20 size-10 rounded-full bg-white/80 hover:bg-white text-slate-800 items-center justify-center shadow-md backdrop-blur-xs transition-colors cursor-pointer"
                title="Next">
            ›
        </button>
    @endif
</section>
@endif

@include('store.partials.quick-links')

<!-- Trust & Quality Strip (Positioned immediately below Hero Banner) -->
<section class="border-b border-light-gold/60 bg-warm-beige/35 py-6 sm:py-7">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 text-center">
            <div class="p-3 rounded-xl bg-ivory/50 border border-light-gold/40 shadow-xs hover:border-gold-400 transition-colors">
                <div class="text-xl sm:text-2xl text-maroon-800 mb-1">✨</div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">100% Authentic Quality</h4>
                <p class="text-[0.6875rem] text-rich-black/65 mt-0.5">Finest pure cotton &amp; certified fabrics</p>
            </div>
            <div class="p-3 rounded-xl bg-ivory/50 border border-light-gold/40 shadow-xs hover:border-gold-400 transition-colors">
                <div class="text-xl sm:text-2xl text-maroon-800 mb-1">🚚</div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">Free Express Delivery</h4>
                <p class="text-[0.6875rem] text-rich-black/65 mt-0.5">On all prepaid orders over ₹999</p>
            </div>
            <div class="p-3 rounded-xl bg-ivory/50 border border-light-gold/40 shadow-xs hover:border-gold-400 transition-colors">
                <div class="text-xl sm:text-2xl text-maroon-800 mb-1">🔄</div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">Easy 7-Day Returns</h4>
                <p class="text-[0.6875rem] text-rich-black/65 mt-0.5">Hassle-free replacement &amp; return</p>
            </div>
            <div class="p-3 rounded-xl bg-ivory/50 border border-light-gold/40 shadow-xs hover:border-gold-400 transition-colors">
                <div class="text-xl sm:text-2xl text-maroon-800 mb-1">🛡️</div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">Secure Checkout</h4>
                <p class="text-[0.6875rem] text-rich-black/65 mt-0.5">UPI, Cards, NetBanking &amp; COD</p>
            </div>
        </div>
    </div>
</section>

<!-- Festive Signature Edit / Hero Showcase Section -->
@if(!isset($heroBlock) || $heroBlock->is_visible)
<section class="relative bg-gradient-to-b from-warm-beige/60 via-ivory to-ivory py-12 md:py-20 border-b border-light-gold/60 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            
            <!-- Left Copy -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold-200/70 border border-gold-400 text-maroon-900 text-xs font-semibold uppercase tracking-wider">
                    <span>✨</span>
                    <span>{{ $heroBlock?->eyebrow ?: 'New Season Festive Collection' }}</span>
                </div>

                <h1 class="font-serif text-4xl sm:text-5xl lg:text-6xl font-normal text-maroon-800 leading-[1.15] tracking-tight">
                    @if($heroBlock && !empty($heroBlock->heading))
                        @php
                            $parts = explode('.', $heroBlock->heading, 2);
                        @endphp
                        @if(count($parts) === 2 && trim($parts[1]) !== '')
                            {{ trim($parts[0]) }}.<br>
                            <span class="italic font-medium text-rich-black">{{ trim($parts[1]) }}</span>
                        @else
                            {!! nl2br(e($heroBlock->heading)) !!}
                        @endif
                    @else
                        Ethnic Elegance.<br>
                        <span class="italic font-medium text-rich-black">Timeless You.</span>
                    @endif
                </h1>

                <p class="text-sm sm:text-base text-rich-black/75 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    {{ $heroBlock?->body ?: 'Immerse yourself in handcrafted pure cotton kurta sets, contemporary two-piece co-ords, and regal salwar suits. Designed for graceful everyday comfort and festive radiance.' }}
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
                    <a href="{{ url($heroBlock?->primary_path ?: '/kurta-set') }}"
                       class="w-full sm:w-auto px-8 py-3.5 rounded-lg bg-maroon-800 text-white text-xs font-bold uppercase tracking-wider hover:bg-maroon-900 transition-all shadow-md text-center">
                        {{ $heroBlock?->primary_label ?: 'Shop Kurta Sets' }}
                    </a>
                    @if($heroBlock?->secondary_label || !$heroBlock)
                    <a href="{{ url($heroBlock?->secondary_path ?: '/women-two-piece-sets-co-ord') }}"
                       class="w-full sm:w-auto px-8 py-3.5 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-bold uppercase tracking-wider hover:bg-maroon-50 transition-all text-center">
                        {{ $heroBlock?->secondary_label ?: 'Explore Co-ords' }}
                    </a>
                    @endif
                </div>

                <!-- Trust badges under hero -->
                <div class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs text-rich-black/60 font-medium">
                    <div class="flex items-center gap-1.5">
                        <span class="text-gold-600 font-bold">✓</span>
                        <span>100% Pure Breathable Cotton</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gold-600 font-bold">✓</span>
                        <span>Authentic Handblock &amp; Motifs</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-gold-600 font-bold">✓</span>
                        <span>Sizes S to XXL</span>
                    </div>
                </div>
            </div>

            <!-- Right Visual Collage / Featured Banner -->
            <div class="lg:col-span-5 relative">
                <div class="relative mx-auto max-w-md lg:max-w-none">
                    @php
                        $heroProd = $store->homeHeroProduct ?? $featuredProducts->first();
                        $heroImg = $heroBlock?->image_url ?: ($store->home_hero_image_url ?: ($heroProd?->primary_image_url ?: 'https://assets.dhansree.com/ds-web/products/1/d875fada-3006-41f8-91ed-257d4d47077e.webp'));
                        $heroTag = $store->home_hero_subtitle ?: ($heroBlock?->eyebrow ?: 'Festive Signature Edit');
                        $heroTitle = $store->home_hero_title ?: ($heroBlock?->heading ?: ($heroProd?->name ?: 'DHANSREE Mustard Yellow Embroidered Kurta Pant Set for Women'));
                        $heroPrice = $heroProd ? 'From ' . $heroProd->formatted_price : 'From ₹1,199';
                        $heroLink = $heroBlock?->primary_path ? url($heroBlock->primary_path) : ($heroProd ? url('/product/' . $heroProd->slug) : url('/kurta-set'));
                    @endphp
                    <a href="{{ $heroLink }}" class="block group">
                        <div class="aspect-[3/4] rounded-2xl overflow-hidden shadow-2xl border-2 border-gold-300 bg-warm-beige relative">
                            <img src="{{ $heroImg }}"
                                 alt="{{ $heroTitle }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-rich-black/75 via-transparent to-transparent"></div>
                            <div class="absolute bottom-6 left-6 right-6 text-white space-y-1">
                                <span class="text-[0.625rem] uppercase tracking-widest text-gold-300 font-semibold">{{ $heroTag }}</span>
                                <h3 class="font-serif text-xl sm:text-2xl font-normal leading-snug group-hover:text-gold-200 transition-colors">{{ $heroTitle }}</h3>
                                <p class="text-xs text-gold-200">{{ $heroPrice }} • Inclusive of all taxes</p>
                            </div>
                        </div>
                    </a>
                    <!-- Decorative badge -->
                    <div class="absolute -bottom-4 -left-4 bg-ivory rounded-xl p-3 shadow-lg border border-light-gold flex items-center gap-2 pointer-events-none">
                        <span class="text-xl">🌸</span>
                        <div>
                            <p class="text-[0.6875rem] font-bold text-maroon-800">Direct From Artisans</p>
                            <p class="text-[0.625rem] text-rich-black/60">No Middlemen Markup</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<!-- Curated Categories Row -->
<section class="py-12 md:py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center space-y-2 mb-10">
        <p class="text-xs uppercase tracking-widest text-gold-600 font-semibold">Handpicked Collections</p>
        <h2 class="font-serif text-3xl sm:text-4xl text-maroon-800">Shop By Category</h2>
        <div class="w-16 h-0.5 bg-gold-400 mx-auto mt-2"></div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
        @foreach($categories->take(3) as $cat)
            <a href="{{ url('/' . $cat->slug) }}"
               class="group relative aspect-[3/4] sm:aspect-[4/5] rounded-xl overflow-hidden bg-warm-beige border border-light-gold shadow-sm flex flex-col justify-end p-4 sm:p-6 transition-all duration-300 hover:shadow-lg">
                @php
                    $catImg = $cat->image_url
                        ?? $cat->products->first()?->primary_image_url
                        ?? \App\Models\Product::whereHas('category', function ($q) use ($cat) {
                            $q->where('id', $cat->id)->orWhere('parent_id', $cat->id);
                        })->where('status', \App\Enums\ProductStatus::ACTIVE)->first()?->primary_image_url;
                @endphp
                @if($catImg)
                    <img src="{{ $catImg }}"
                         alt="{{ $cat->name }}"
                         class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105">
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-rich-black/85 via-rich-black/30 to-transparent"></div>

                <div class="relative z-10 text-white space-y-1">
                    <h3 class="font-serif text-xl sm:text-2xl font-normal group-hover:text-gold-300 transition-colors">
                        {{ $cat->name }}
                    </h3>
                    <p class="text-xs text-gold-200/90 line-clamp-1">Explore latest designs</p>
                    <span class="inline-flex items-center text-[0.6875rem] uppercase font-semibold text-white group-hover:translate-x-1 transition-transform pt-1">
                        View Collection ➔
                    </span>
                </div>
            </a>
        @endforeach
    </div>
</section>

<!-- Featured Products Section -->
@if(($featuredBlock?->is_visible ?? true) && (!empty($featuredProducts) && count($featuredProducts) > 0))
<section class="py-12 md:py-16 bg-warm-beige/20 border-t border-b border-light-gold/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-10">
            <div>
                <p class="text-xs uppercase tracking-widest text-gold-600 font-semibold">{{ $featuredBlock?->eyebrow ?: 'Festive Radiance' }}</p>
                <h2 class="font-serif text-3xl sm:text-4xl text-maroon-800">{{ $featuredBlock?->heading ?: 'Featured Ethnic Styles' }}</h2>
            </div>
            <a href="{{ $featuredBlock?->primary_path ?: url('/kurta-set') }}"
               class="text-xs font-semibold uppercase tracking-wider text-maroon-800 hover:text-maroon-900 flex items-center gap-1 group">
                <span>{{ $featuredBlock?->primary_label ?: 'View All Products' }}</span>
                <span class="group-hover:translate-x-1 transition-transform">➔</span>
            </a>
        </div>

        <!-- 4-Column Product Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            @foreach($featuredProducts as $product)
                @php
                    $firstVariant = $product->firstVariant ?? $product->variants->whereNull('archived_at')->first();
                    $hasMultipleVariants = $product->hasMultipleVariants ?? ($product->variants->whereNull('archived_at')->count() > 1);
                    $prodVideo = $product->approved_video;
                @endphp
                <div class="group bg-white rounded-xl p-3 border border-light-gold/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                     x-data="{ selectedVariantId: {{ $firstVariant?->id ?? 'null' }}, isHovered: false }"
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

                            @if($product->discount_percentage)
                                <span class="absolute top-2 left-2 px-2 py-0.5 rounded bg-maroon-800 text-white text-[0.625rem] font-bold tracking-wide uppercase pointer-events-none z-10">
                                    {{ $product->discount_percentage }}% OFF
                                </span>
                            @endif

                            @if($prodVideo)
                                <span class="absolute top-2 right-12 px-2 py-0.5 rounded-full bg-slate-900/85 backdrop-blur-xs text-amber-300 text-[10px] font-bold tracking-wide flex items-center gap-1 border border-amber-400/40 shadow-xs z-10 pointer-events-none"
                                      title="Watch fashion reel in motion">
                                    <span class="size-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                    <span>▶ Reel</span>
                                </span>
                            @endif

                            @if(!empty($product->color_name))
                                <span class="absolute bottom-2 left-2 px-2 py-0.5 rounded-full bg-white/95 backdrop-blur-xs text-[0.625rem] font-bold text-rich-black shadow-xs flex items-center gap-1.5 border border-light-gold/60 pointer-events-none z-10">
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
                                    {{ $product->brand ?? 'DHANSREE ETHNIC' }}
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

                        <!-- Variant Selector (Sizes) -->
                        @if($hasMultipleVariants)
                            <div class="mt-2.5 flex flex-wrap gap-1">
                                @foreach($product->variants->take(6) as $v)
                                    @php
                                        $sizeOption = $v->optionValues?->first(function($ov) {
                                            return strtolower($ov->option?->name ?? '') === 'size';
                                        }) ?? $v->optionValues?->first(function($ov) {
                                            return !in_array(strtolower($ov->option?->name ?? ''), ['color', 'colour']);
                                        }) ?? $v->optionValues?->first();
                                        $label = $sizeOption?->value ?? $v->display_option_label ?? $v->title ?? 'Std';
                                    @endphp
                                    <button type="button"
                                            @click="selectedVariantId = {{ $v->id }}"
                                            :class="selectedVariantId === {{ $v->id }} ? 'border-maroon-800 bg-maroon-50 text-maroon-800 font-bold' : 'border-light-gold text-rich-black/70'"
                                            class="px-2 py-0.5 text-[0.625rem] rounded border transition-colors font-medium">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Quick Add CTA Button -->
                    <div class="mt-3 pt-2 border-t border-light-gold/50">
                        @if($firstVariant)
                            <button type="button"
                                    @click="addToCart(selectedVariantId, 1)"
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
    </div>
</section>
@endif

<!-- Cross-Hub Spotlight Banner: Smart Living & Tech Essentials -->
<section class="py-12 md:py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="rounded-2xl bg-tech-dark text-white p-8 sm:p-12 border border-tech-border relative overflow-hidden shadow-xl">
        <!-- Background aesthetic accents -->
        <div class="absolute -right-20 -bottom-20 w-80 h-80 rounded-full bg-amber-600/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-20 -top-20 w-80 h-80 rounded-full bg-gold-500/10 blur-3xl pointer-events-none"></div>

        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-8 space-y-4">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-semibold tracking-wider uppercase border border-amber-500/30">
                    <span>⚡</span>
                    <span>Direct-To-Customer Tech Hub</span>
                </div>

                <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-normal leading-tight text-white">
                    Upgrade Your Daily Routine.<br>
                    <span class="text-gold-300 italic font-medium">Smart Gadgets &amp; Mobile Accessories.</span>
                </h2>

                <p class="text-sm text-white/70 max-w-xl leading-relaxed">
                    Alongside our ethnic fashion, explore our dedicated tech collection featuring 60W fast cables, compact travel garment steamers, phone holders, and smart lifestyle utilities — quality tested and shipped directly to you.
                </p>

                <div class="pt-2 flex flex-wrap gap-4">
                    <a href="{{ url('/smart-living') }}"
                       class="px-8 py-3.5 rounded-lg bg-gold-500 text-rich-black text-xs font-bold uppercase tracking-wider hover:bg-gold-400 transition-all shadow-md">
                        Explore Smart Living Hub ➔
                    </a>
                    <a href="{{ url('/smart-gadgets') }}"
                       class="px-6 py-3.5 rounded-lg border border-white/30 text-white text-xs font-semibold uppercase tracking-wider hover:bg-white/10 transition-all">
                        Smart Gadgets
                    </a>
                </div>
            </div>

            <div class="lg:col-span-4 flex justify-center">
                <div class="grid grid-cols-2 gap-3 max-w-xs w-full">
                    <div class="bg-tech-card rounded-xl p-4 border border-tech-border text-center space-y-1">
                        <span class="text-2xl">🔌</span>
                        <h4 class="text-xs font-bold text-white">60W Fast Cables</h4>
                        <p class="text-[0.625rem] text-white/50">Multi-connector kit</p>
                    </div>
                    <div class="bg-tech-card rounded-xl p-4 border border-tech-border text-center space-y-1">
                        <span class="text-2xl">💨</span>
                        <h4 class="text-xs font-bold text-white">Travel Steamers</h4>
                        <p class="text-[0.625rem] text-white/50">2-in-1 Dry &amp; Spray</p>
                    </div>
                    <div class="bg-tech-card rounded-xl p-4 border border-tech-border text-center space-y-1">
                        <span class="text-2xl">📱</span>
                        <h4 class="text-xs font-bold text-white">Accessories</h4>
                        <p class="text-[0.625rem] text-white/50">Cases &amp; Mounts</p>
                    </div>
                    <div class="bg-tech-card rounded-xl p-4 border border-tech-border text-center space-y-1">
                        <span class="text-2xl">⚡</span>
                        <h4 class="text-xs font-bold text-white">Direct Value</h4>
                        <p class="text-[0.625rem] text-white/50">Zero Middlemen</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Instagram Reels & Styles Single-Row Slider -->
@php
    $instagramBlock = $instagramBlock ?? null;
    $feedItems = $instagramFeed ?? collect();

    // Check for approved real ProductVideos generated in Video Studio
    $realVideoItems = collect();
    try {
        $approvedVideos = \App\Models\ProductVideo::where('status', 'completed')
            ->approved()
            ->with(['product.defaultVariant'])
            ->latest('id')
            ->take(10)
            ->get();

        $realVideoItems = $approvedVideos->map(function ($v, $idx) {
            $item = new \App\Models\InstagramPost();
            $item->id = 'pv-' . $v->id;
            $item->type = 'reel';
            $item->caption = ($v->product?->name ?? $v->title) . ' • Exclusive Jaipur Motion Reel ✨ #dhansreetrends';
            $item->media_url = $v->thumbnail_url ?: $v->product?->primary_image_url;
            $item->likes_count = 1850 + ($idx * 340);
            $item->views_count = (21 + ($idx * 4)) . '.6K';
            $item->tagged_product_id = $v->product_id;
            if ($v->product) {
                $item->setRelation('taggedProduct', $v->product);
            }
            $item->real_video_url = $v->video_url;
            $item->real_duration = $v->duration_seconds;
            return $item;
        });
    } catch (\Throwable $e) {
        $realVideoItems = collect();
    }

    if ($feedItems->isEmpty()) {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('instagram_posts')) {
                $feedItems = \App\Models\InstagramPost::visible()
                    ->with(['taggedProduct.defaultVariant'])
                    ->orderBy('position')
                    ->orderByDesc('id')
                    ->take(12)
                    ->get();
            }
        } catch (\Throwable $e) {
            $feedItems = collect();
        }
    }

    // Prepend real video reels at the front so users immediately see live model motion
    if ($realVideoItems->isNotEmpty()) {
        $feedItems = $realVideoItems->concat($feedItems)->unique('id')->take(12);
    }

    if ($feedItems->isEmpty()) {
        try {
            $fallbackProducts = \App\Models\Product::with(['defaultVariant', 'primaryImage'])
                ->where('status', \App\Enums\ProductStatus::ACTIVE)
                ->take(8)
                ->get();
            $feedItems = $fallbackProducts->map(function ($p, $idx) {
                $item = new \App\Models\InstagramPost();
                $item->id = $p->id;
                $item->type = 'reel';
                $item->caption = $p->name . ' • Trending Festive Jaipur Look ✨ #dhansreetrends';
                $item->media_url = $p->primary_image_url;
                $item->likes_count = 1450 + ($idx * 310);
                $item->views_count = (18 + ($idx * 3)) . '.5K';
                $item->tagged_product_id = $p->id;
                $item->setRelation('taggedProduct', $p);
                return $item;
            });
        } catch (\Throwable $e) {
            $feedItems = collect();
        }
    }
@endphp

@if(($instagramBlock?->is_visible ?? true) && $feedItems->isNotEmpty())
<style>
.insta-slider-card {
    width: 170px !important;
    min-width: 170px !important;
    max-width: 170px !important;
    height: 300px !important;
    flex: 0 0 170px !important;
}
@media (min-width: 640px) {
    .insta-slider-card {
        width: 205px !important;
        min-width: 205px !important;
        max-width: 205px !important;
        height: 360px !important;
        flex: 0 0 205px !important;
    }
}
@media (min-width: 1024px) {
    .insta-slider-card {
        width: 220px !important;
        min-width: 220px !important;
        max-width: 220px !important;
        height: 380px !important;
        flex: 0 0 220px !important;
    }
}
</style>
<section class="py-12 md:py-16 bg-white border-t border-b border-light-gold/60 overflow-hidden"
         x-data="{
             scrollLeft() {
                 this.$refs.instaSlider.scrollBy({ left: -240, behavior: 'smooth' });
             },
             scrollRight() {
                 this.$refs.instaSlider.scrollBy({ left: 240, behavior: 'smooth' });
             }
         }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
            <div class="space-y-1">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 border border-rose-200/70 text-rose-700 text-xs font-semibold uppercase tracking-wider">
                    <span class="inline-block size-2 rounded-full bg-gradient-to-tr from-amber-400 via-rose-500 to-purple-600"></span>
                    <span>{{ $instagramBlock?->eyebrow ?: 'As Seen On Instagram • @dhansreetrends' }}</span>
                </div>
                <h2 class="font-serif text-3xl sm:text-4xl text-maroon-800 tracking-tight">
                    {{ $instagramBlock?->heading ?: 'Shop Our Instagram Reels & Styles' }}
                </h2>
                <p class="text-xs sm:text-sm text-rich-black/70 max-w-xl">
                    {{ $instagramBlock?->body ?: 'Tap any reel to explore trending Jaipur fits, festive styling & shop the exact looks.' }}
                </p>
            </div>

            <!-- Action CTAs & Slider Controls -->
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ url($instagramBlock?->primary_path ?: '/instragram') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider text-white bg-maroon-800 hover:bg-maroon-900 transition-all shadow-xs">
                    <span>{{ $instagramBlock?->primary_label ?: 'View All Reels' }} ➔</span>
                </a>

                <a href="https://www.instagram.com/dhansreetrends" target="_blank" rel="noopener noreferrer"
                   class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold border border-light-gold text-rich-black hover:bg-warm-beige transition-colors">
                    <svg class="size-3.5 fill-current text-rose-600" viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    <span>Follow</span>
                </a>

                <!-- Slider Arrows -->
                <div class="flex items-center gap-1.5 pl-1">
                    <button type="button" @click="scrollLeft()"
                            class="size-9 rounded-full border border-light-gold bg-white hover:bg-warm-beige text-rich-black flex items-center justify-center transition-colors shadow-2xs cursor-pointer"
                            title="Scroll Left">
                        ‹
                    </button>
                    <button type="button" @click="scrollRight()"
                            class="size-9 rounded-full border border-light-gold bg-white hover:bg-warm-beige text-rich-black flex items-center justify-center transition-colors shadow-2xs cursor-pointer"
                            title="Scroll Right">
                        ›
                    </button>
                </div>
            </div>
        </div>

        <!-- Single Row Horizontal Slider Container -->
        <div x-ref="instaSlider"
             class="flex gap-4 sm:gap-5 overflow-x-auto scrollbar-none snap-x snap-mandatory py-2 scroll-smooth -mx-4 px-4 sm:mx-0 sm:px-0">
            @foreach($feedItems as $item)
                @php
                    $targetUrl = $item->taggedProduct
                        ? ($item->taggedProduct->url ?? url('/product/' . $item->taggedProduct->slug))
                        : url($instagramBlock?->primary_path ?: '/instragram');
                    $hasRealVideo = !empty($item->real_video_url);
                @endphp
                <a href="{{ $targetUrl }}"
                   class="insta-slider-card group relative rounded-2xl overflow-hidden bg-warm-beige snap-start border border-light-gold/60 shadow-sm hover:shadow-xl transition-all duration-300 block"
                   x-data="{ isVideoHovered: false }"
                   @mouseenter="isVideoHovered = true; if ($refs.reelVideo) $refs.reelVideo.play();"
                   @mouseleave="isVideoHovered = false; if ($refs.reelVideo) { $refs.reelVideo.pause(); $refs.reelVideo.currentTime = 0; }">
                    
                    @if($hasRealVideo)
                        <!-- Real Playable Video with auto-play on hover -->
                        <video x-ref="reelVideo"
                               poster="{{ $item->display_media_url }}"
                               playsinline
                               loop
                               muted
                               preload="metadata"
                               class="w-full h-full object-cover">
                            @if(!empty($item->taggedProduct?->approved_video?->webm_url))
                                <source src="{{ $item->taggedProduct->approved_video->webm_url }}" type="video/webm">
                            @endif
                            <source src="{{ $item->real_video_url }}" type="video/mp4">
                        </video>
                    @else
                        <!-- Cover Image -->
                        <img src="{{ $item->display_media_url }}"
                             alt="{{ $item->caption ?: 'Instagram Reel' }}"
                             class="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                             loading="lazy">
                    @endif

                    <!-- Top Scrim with Reel Icon & Views (Only shown when real video exists) -->
                    <div class="absolute inset-x-0 top-0 p-2.5 sm:p-3 bg-gradient-to-b from-black/60 via-black/20 to-transparent flex items-center justify-between z-10">
                        @if($hasRealVideo)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full bg-amber-500/90 text-slate-950 font-bold backdrop-blur-xs text-[10px] sm:text-[11px]">
                                <span class="text-rose-700">🎬</span>
                                <span>Model Reel</span>
                            </span>
                            <span class="inline-flex items-center gap-1 text-[10px] sm:text-[11px] font-bold text-white drop-shadow-sm">
                                <span>▶</span>
                                <span>{{ $item->formatted_views }}</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-full bg-black/50 text-white backdrop-blur-xs text-[10px] sm:text-[11px] font-semibold">
                                <span>📸</span>
                                <span>Look</span>
                            </span>
                            <span class="inline-flex items-center gap-1 text-[10px] sm:text-[11px] font-bold text-white/90 drop-shadow-sm">
                                @if($item->formatted_views)
                                    <svg class="size-3 text-white/80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    </svg>
                                    <span>{{ $item->formatted_views }}</span>
                                @else
                                    <span>❤️</span>
                                    <span>{{ $item->formatted_likes }}</span>
                                @endif
                            </span>
                        @endif
                    </div>

                    @if($hasRealVideo)
                        <!-- Center Play Button Overlay on Hover (ONLY shown if product has real video) -->
                        <div class="absolute inset-0 flex items-center justify-center transition-opacity duration-300 pointer-events-none z-10"
                             :class="isVideoHovered ? 'opacity-0' : 'opacity-100 sm:opacity-80 group-hover:opacity-100'">
                            <span class="w-11 h-11 rounded-full bg-black/60 backdrop-blur-md text-white flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform">
                                <svg class="size-5 sm:size-6 fill-white translate-x-0.5" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </span>
                        </div>
                    @endif

                    <!-- Bottom Gradient Overlay with Tagged Product, Likes & Caption -->
                    <div class="absolute inset-x-0 bottom-0 p-2.5 sm:p-3 bg-gradient-to-t from-black/85 via-black/50 to-transparent text-white space-y-1.5 z-10">
                        
                        @if($item->taggedProduct)
                            <!-- Tagged Product Floating Pill -->
                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/95 backdrop-blur-xs text-maroon-800 text-[10px] font-bold shadow-md max-w-full">
                                <span class="shrink-0">🛍️</span>
                                <span class="truncate">{{ $item->taggedProduct->name }}</span>
                                <span class="text-slate-900 shrink-0">• {{ $item->taggedProduct->formatted_price }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between text-[11px] text-white/90 font-medium pt-0.5">
                            <span class="flex items-center gap-1 text-rose-300 font-bold">
                                <span>❤️</span>
                                <span>{{ $item->formatted_likes }}</span>
                            </span>
                            <span class="text-[10px] text-gold-300 font-semibold group-hover:underline">
                                {{ $hasRealVideo ? 'Shop Look ➔' : 'Watch ➔' }}
                            </span>
                        </div>

                        @if(!empty($item->caption))
                            <p class="text-[10px] sm:text-[11px] text-white/80 line-clamp-1 leading-snug">
                                {{ $item->caption }}
                            </p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Banner Strip linking to /instragram -->
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-3 p-4 rounded-xl bg-warm-beige/50 border border-light-gold text-xs text-rich-black">
            <div class="flex items-center gap-2">
                <span class="text-base">📸</span>
                <span>Want to see all our looks? Explore full videos, reels &amp; tagged collections on our dedicated Instagram lookbook.</span>
            </div>
            <a href="{{ url($instagramBlock?->primary_path ?: '/instragram') }}"
               class="font-bold text-maroon-800 hover:text-maroon-900 underline whitespace-nowrap">
                Explore All Dhansree Reels &amp; Posts ➔
            </a>
        </div>

    </div>
</section>
@endif

<!-- Brand Craftsmanship / Heritage Narrative -->
<section class="py-12 md:py-16 bg-warm-beige/40 border-t border-light-gold/60">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
        <span class="text-xs uppercase tracking-[0.2em] text-gold-600 font-semibold">The Dhansree Philosophy</span>
        <h2 class="font-serif text-3xl sm:text-4xl text-maroon-800 leading-snug">
            Crafted for Comfort, Styled for Celebration.
        </h2>
        <p class="text-sm text-rich-black/75 leading-relaxed">
            At Dhansree, we believe that true elegance lies in effortless comfort. Every stitch, paisley print, and embroidered motif is thoughtfully created with breathable natural fabrics so you feel poised from morning meetings to festive family evenings.
        </p>
        <div class="pt-2 flex justify-center items-center gap-8 text-xs font-semibold text-maroon-800 uppercase tracking-wider">
            <span>Natural Fibers</span>
            <span>•</span>
            <span>Artisan Heritage</span>
            <span>•</span>
            <span>Direct to Customer</span>
        </div>
    </div>
</section>
@endsection
