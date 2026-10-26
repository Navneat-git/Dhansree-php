@extends('layouts.store')

@section('content')
<div class="bg-tech-dark text-white">
    
    <!-- Hero Section -->
    <section class="relative py-12 md:py-20 border-b border-tech-border overflow-hidden bg-gradient-to-b from-rich-black via-[#231010] to-tech-dark">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                
                <!-- Left Hero Copy -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-gold-300 text-xs font-semibold uppercase tracking-wider">
                        <span>⚡</span>
                        <span>Smart Living &amp; Tech Essentials</span>
                    </div>

                    <h1 class="font-serif text-4xl sm:text-5xl lg:text-6xl font-normal leading-[1.15] text-white">
                        Better Products.<br>
                        <span class="text-gold-400 font-medium italic">Smarter Living.</span>
                    </h1>

                    <p class="text-sm sm:text-base text-white/70 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                        Curated collection of innovative gadgets, multi-cable kits, travel steamers, and phone accessories. Built for high performance and durability — delivered direct to your door.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
                        <a href="#catalogue"
                           class="w-full sm:w-auto px-8 py-3.5 rounded-lg bg-gold-500 text-rich-black text-xs font-bold uppercase tracking-wider hover:bg-gold-400 transition-all shadow-md text-center">
                            Browse Catalogue
                        </a>
                        <a href="{{ url('/') }}"
                           class="w-full sm:w-auto px-8 py-3.5 rounded-lg border border-white/20 text-white text-xs font-semibold uppercase tracking-wider hover:bg-white/10 transition-all text-center">
                            🌸 Women's Ethnic Wear
                        </a>
                    </div>

                    <ul class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs text-white/60 font-medium">
                        <li class="flex items-center gap-1.5">
                            <span class="text-gold-400 font-bold">✓</span>
                            <span>Direct-to-Customer Value</span>
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-gold-400 font-bold">✓</span>
                            <span>100% Quality Inspected</span>
                        </li>
                        <li class="flex items-center gap-1.5">
                            <span class="text-gold-400 font-bold">✓</span>
                            <span>Cash on Delivery Available</span>
                        </li>
                    </ul>
                </div>

                <!-- Right Visual Grid / Showcase -->
                <div class="lg:col-span-5">
                    <div class="bg-tech-card rounded-2xl p-6 border border-tech-border shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-tech-border text-xs text-white/60">
                            <span class="font-semibold uppercase tracking-wider text-gold-400">Featured Inventions</span>
                            <span>Direct Dispatch</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($products->take(4) as $p)
                                <div class="bg-tech-dark rounded-xl p-3 border border-tech-border flex flex-col justify-between">
                                    <div class="aspect-square rounded-lg overflow-hidden bg-tech-card/60 mb-2">
                                        <img src="{{ $p->primary_image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover">
                                    </div>
                                    <h4 class="text-[0.6875rem] font-medium text-white/90 line-clamp-2 h-7 leading-tight">{{ $p->name }}</h4>
                                    <span class="text-xs font-bold text-gold-400 mt-1">{{ $p->formatted_price }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Catalogue & Filtering Section -->
    <section id="catalogue" class="py-12 md:py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center space-y-2 mb-10">
            <p class="text-xs uppercase tracking-widest text-gold-400 font-semibold">Tech &amp; Accessories</p>
            <h2 class="font-serif text-3xl sm:text-4xl text-white">Explore Modern Essentials</h2>
            <p class="text-xs sm:text-sm text-white/60 max-w-md mx-auto">High-utility products and innovative accessories tailored for modern lifestyles.</p>
        </div>

        {{-- Category Filter Pills (Temporarily hidden until more products are added per category)
        <div class="flex flex-wrap items-center justify-center gap-2 mb-10">
            <button type="button"
                    @click="activeFilter = 'all'"
                    :class="activeFilter === 'all' ? 'bg-gold-500 text-rich-black font-bold' : 'bg-tech-card text-white/80 border border-tech-border hover:border-gold-500/50'"
                    class="px-5 py-2.5 rounded-full text-xs transition-colors">
                All Products
            </button>
            @foreach($categories as $cat)
                <button type="button"
                        @click="activeFilter = '{{ $cat->slug }}'"
                        :class="activeFilter === '{{ $cat->slug }}' ? 'bg-gold-500 text-rich-black font-bold' : 'bg-tech-card text-white/80 border border-tech-border hover:border-gold-500/50'"
                        class="px-5 py-2.5 rounded-full text-xs transition-colors">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>
        --}}

        <!-- Products Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            @foreach($products as $product)
                @php
                    $firstVariant = $product->variants->whereNull('archived_at')->first();
                    $catSlug = $product->category?->slug ?? '';
                @endphp
                <div class="bg-tech-card rounded-xl p-3 border border-tech-border shadow-md hover:border-gold-500/50 transition-all flex flex-col justify-between">
                    
                    <div>
                        <!-- Image Container -->
                        <div class="relative aspect-square rounded-lg overflow-hidden bg-tech-dark mb-3">
                            <a href="{{ url('/product/' . $product->slug) }}">
                                <img src="{{ $product->primary_image_url }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
                                     loading="lazy">
                            </a>

                            @if($product->discount_percentage)
                                <span class="absolute top-2 left-2 px-2 py-0.5 rounded bg-gold-500 text-rich-black text-[0.625rem] font-bold tracking-wide uppercase">
                                    {{ $product->discount_percentage }}% OFF
                                </span>
                            @endif
                        </div>

                        <!-- Product Info -->
                        <div class="space-y-1">
                            <p class="text-[0.625rem] uppercase tracking-wider text-gold-400/80 font-semibold truncate">
                                {{ $product->category?->name ?? 'SMART TECH' }}
                            </p>
                            <h3 class="text-xs sm:text-sm font-medium text-white line-clamp-2 h-10 leading-snug">
                                <a href="{{ url('/product/' . $product->slug) }}" class="hover:text-gold-300 transition-colors">
                                    {{ $product->name }}
                                </a>
                            </h3>

                            <!-- Price Display -->
                            <div class="flex items-baseline gap-2 pt-1">
                                <span class="text-sm sm:text-base font-bold text-gold-400">
                                    {{ $product->formatted_price }}
                                </span>
                                @if($product->formatted_mrp)
                                    <del class="text-xs text-white/40">
                                        {{ $product->formatted_mrp }}
                                    </del>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Add CTA -->
                    <div class="mt-3 pt-2 border-t border-tech-border">
                        @if($firstVariant)
                            <button type="button"
                                    @click="addToCart({{ $firstVariant->id }}, 1)"
                                    class="w-full py-2.5 rounded-lg bg-gold-500 text-rich-black text-xs font-bold hover:bg-gold-400 transition-all flex items-center justify-center gap-1.5 shadow-sm">
                                <span>+</span>
                                <span>Add to Bag</span>
                            </button>
                        @else
                            <a href="{{ url('/product/' . $product->slug) }}"
                               class="w-full py-2.5 rounded-lg border border-tech-border text-white/70 text-xs font-semibold text-center block hover:border-gold-500 hover:text-gold-400 transition-colors">
                                View Details
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Cross-Hub Banner to Women's Ethnic Wear -->
    <section class="py-12 md:py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-tech-border">
        @php
            $promoProd = $store->smartLivingPromoProduct ?? $featuredWomenProduct;
            $promoImg = $store->smart_living_promo_image_url ?: ($promoProd?->primary_image_url ?: 'https://assets.dhansree.com/ds-web/products/9/db0d853c-db93-491c-b61b-ddb626099456.webp');
            $promoTitle = $store->smart_living_promo_title ?: "Looking for Women's Fashion?";
            $promoSubtitle = $store->smart_living_promo_subtitle ?: 'Discover Pure Cotton Kurties & Co-ords.';
            $promoLink = $promoProd ? url('/product/' . $promoProd->slug) : url('/');
        @endphp
        <div class="rounded-2xl bg-gradient-to-r from-maroon-900 to-maroon-950 p-8 sm:p-12 border border-maroon-800 text-white relative overflow-hidden shadow-2xl">
            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-8 space-y-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gold-400/20 text-gold-300 text-xs font-semibold uppercase tracking-wider border border-gold-400/30">
                        🌸 Dhansree Ethnic Collections
                    </span>
                    <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-normal leading-tight">
                        {{ $promoTitle }}<br>
                        <span class="italic text-gold-300">{{ $promoSubtitle }}</span>
                    </h2>
                    <p class="text-sm text-white/75 max-w-xl leading-relaxed">
                        Handcrafted with love from pure breathable fabrics. Explore our complete range of Anarkalis, daily wear tunic sets, and festive outfits.
                    </p>
                    <div class="pt-2">
                        <a href="{{ $promoLink }}"
                           class="inline-block px-8 py-3.5 rounded-lg bg-ivory text-maroon-900 text-xs font-bold uppercase tracking-wider hover:bg-white transition-all shadow-md">
                            Visit Women's Fashion Hub ➔
                        </a>
                    </div>
                </div>
                <div class="lg:col-span-4 flex justify-center">
                    <a href="{{ $promoLink }}" class="block group">
                        <div class="w-48 h-60 rounded-xl overflow-hidden border-2 border-gold-400/40 shadow-xl bg-maroon-800 transition-transform duration-500 group-hover:scale-105">
                            <img src="{{ $promoImg }}" alt="{{ $promoTitle }}" class="w-full h-full object-cover">
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
