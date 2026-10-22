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

<section class="relative bg-gradient-to-b from-warm-beige/60 via-ivory to-ivory py-12 md:py-20 border-b border-light-gold/60 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            
            <!-- Left Copy -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold-200/70 border border-gold-400 text-maroon-900 text-xs font-semibold uppercase tracking-wider">
                    <span>✨</span>
                    <span>New Season Festive Collection</span>
                </div>

                <h1 class="font-serif text-4xl sm:text-5xl lg:text-6xl font-normal text-maroon-800 leading-[1.15] tracking-tight">
                    Ethnic Elegance.<br>
                    <span class="italic font-medium text-rich-black">Timeless You.</span>
                </h1>

                <p class="text-sm sm:text-base text-rich-black/75 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    Immerse yourself in handcrafted pure cotton kurta sets, contemporary two-piece co-ords, and regal salwar suits. Designed for graceful everyday comfort and festive radiance.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
                    <a href="{{ url('/kurta-set') }}"
                       class="w-full sm:w-auto px-8 py-3.5 rounded-lg bg-maroon-800 text-white text-xs font-bold uppercase tracking-wider hover:bg-maroon-900 transition-all shadow-md text-center">
                        Shop Kurta Sets
                    </a>
                    <a href="{{ url('/women-two-piece-sets-co-ord') }}"
                       class="w-full sm:w-auto px-8 py-3.5 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-bold uppercase tracking-wider hover:bg-maroon-50 transition-all text-center">
                        Explore Co-ords
                    </a>
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
                        $heroImg = $store->home_hero_image_url ?: ($heroProd?->primary_image_url ?: 'https://assets.dhansree.com/ds-web/products/1/d875fada-3006-41f8-91ed-257d4d47077e.webp');
                        $heroTag = $store->home_hero_subtitle ?: 'Festive Signature Edit';
                        $heroTitle = $store->home_hero_title ?: ($heroProd?->name ?: 'Pure Cotton Flared Kurta & Pant Set');
                        $heroPrice = $heroProd ? 'From ' . $heroProd->formatted_price : 'From ₹1,199';
                        $heroLink = $heroProd ? url('/product/' . $heroProd->slug) : url('/women-cotton-suits-sets');
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
                    $catImg = $cat->image_url ?? $cat->products->first()?->primary_image_url;
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
<section class="py-12 md:py-16 bg-warm-beige/20 border-t border-b border-light-gold/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-10">
            <div>
                <p class="text-xs uppercase tracking-widest text-gold-600 font-semibold">Festive Radiance</p>
                <h2 class="font-serif text-3xl sm:text-4xl text-maroon-800">Featured Ethnic Styles</h2>
            </div>
            <a href="{{ url('/kurta-set') }}"
               class="text-xs font-semibold uppercase tracking-wider text-maroon-800 hover:text-maroon-900 flex items-center gap-1 group">
                <span>View All Products</span>
                <span class="group-hover:translate-x-1 transition-transform">➔</span>
            </a>
        </div>

        <!-- 4-Column Product Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            @foreach($featuredProducts as $product)
                @php
                    $firstVariant = $product->variants->whereNull('archived_at')->first();
                    $hasMultipleVariants = $product->variants->whereNull('archived_at')->count() > 1;
                @endphp
                <div class="group bg-white rounded-xl p-3 border border-light-gold/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                     x-data="{ selectedVariantId: {{ $firstVariant?->id ?? 'null' }} }">
                    
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
                                {{ $product->brand ?? 'DHANSREE ETHNIC' }}
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

                        <!-- Variant Selector (Sizes) -->
                        @if($hasMultipleVariants)
                            <div class="mt-2.5 flex flex-wrap gap-1">
                                @foreach($product->variants->whereNull('archived_at')->take(6) as $v)
                                    @php
                                        $sizeOption = $v->optionValues->first(function($ov) {
                                            return strtolower($ov->option?->name ?? '') === 'size';
                                        }) ?? $v->optionValues->first(function($ov) {
                                            return !in_array(strtolower($ov->option?->name ?? ''), ['color', 'colour']);
                                        }) ?? $v->optionValues->first();
                                        $label = $sizeOption?->value ?? $v->title ?? 'Std';
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
                            <a href="{{ url('/product/' . $product->slug) }}"
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
