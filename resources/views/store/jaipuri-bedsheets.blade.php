@extends('layouts.store')

@section('title', $seoTitle ?? 'Authentic Jaipuri Pure Cotton Bedsheets | Dhansree Trends')
@section('meta_description', $seoDescription ?? 'Shop 100% pure cotton handcrafted Jaipuri bedsheets online. Discover traditional Sanganeri & Bagru handblock prints, 210 TC softness, king & queen sizes with matching pillow covers. Free shipping & COD.')

@section('content')
<div class="bg-ivory/60 min-h-screen" x-data="{
    activeTab: 'all',
    activeSize: 'all',
    selectedVariants: {
        @foreach($products as $prod)
            '{{ $prod->id }}': '{{ $prod->variants->first()?->id }}',
        @endforeach
    },
    selectedPrices: {
        @foreach($products as $prod)
            '{{ $prod->id }}': '{{ $prod->variants->first()?->price_rupees ?? 799 }}',
        @endforeach
    },
    selectedMrps: {
        @foreach($products as $prod)
            '{{ $prod->id }}': '{{ $prod->variants->first()?->mrp_rupees ?? 1599 }}',
        @endforeach
    },
    selectedSizes: {
        @foreach($products as $prod)
            '{{ $prod->id }}': 'King',
        @endforeach
    },
    setProductVariant(productId, variantId, price, mrp, sizeLabel) {
        this.selectedVariants[productId] = variantId;
        this.selectedPrices[productId] = price;
        this.selectedMrps[productId] = mrp;
        this.selectedSizes[productId] = sizeLabel;
    }
}">

    {{-- ─── SECTION 1: TOP BANNER (JAIPURI THEME) ─── --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-[#2d0508] via-[#480c12] to-[#1e0305] text-white">
        <!-- Background Hero Image with Jaipuri Palace Bedroom Aesthetic -->
        <div class="absolute inset-0 z-0">
            <picture class="w-full h-full">
                <source srcset="{{ asset('media/bedsheets/banner.webp') }}" type="image/webp">
                <img src="{{ asset('media/bedsheets/banner.jpg') }}"
                     alt="Authentic Jaipuri Pure Cotton Bedsheets on Royal King Bed"
                     class="w-full h-full object-cover object-center opacity-30 md:opacity-35 scale-105 transform motion-safe:transition-transform motion-safe:duration-1000">
            </picture>
            <!-- Gradient Overlay for Contrast & Readability -->
            <div class="absolute inset-0 bg-gradient-to-r from-[#200305]/95 via-[#38080c]/85 to-[#200305]/90"></div>
            <!-- Traditional Rajasthani Block Print Dot Pattern Grid -->
            <div class="absolute inset-0 bg-[radial-gradient(#d4af37_1px,transparent_1px)] [background-size:28px_28px] opacity-15 pointer-events-none"></div>
            <!-- Decorative Glow Accents -->
            <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-amber-500/20 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-rose-800/30 blur-3xl pointer-events-none"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 md:py-24 text-center">
            <!-- Jaipuri Heritage Eyebrow Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-300 text-xs sm:text-sm font-bold tracking-widest uppercase shadow-md mb-6 backdrop-blur-xs">
                <span>🛏️</span>
                <span>AUTHENTIC JAIPUR TEXTILE ARTISTRY • 100% PURE COMBED COTTON</span>
            </div>

            <!-- Grand Headline with Jaipuri Serif Charm -->
            <h1 class="font-serif text-3xl sm:text-5xl md:text-6xl font-normal tracking-tight text-white max-w-4xl mx-auto leading-tight">
                Authentic Jaipuri<br class="hidden sm:inline">
                <span class="text-amber-300 italic font-medium">Pure Cotton Bedsheets</span>
            </h1>

            <!-- Heritage Tagline -->
            <p class="mt-4 sm:mt-6 text-base sm:text-lg md:text-xl text-rose-100/90 max-w-2xl mx-auto font-light leading-relaxed">
                Handcrafted with timeless Sanganeri and Bagru handblock prints. Breathable 210+ thread count cotton for regal bedrooms and deep, restful sleep.
            </p>

            <!-- Key Feature Badges Strip -->
            <div class="mt-8 flex flex-wrap justify-center items-center gap-2.5 sm:gap-4 max-w-4xl mx-auto text-xs sm:text-sm font-medium text-amber-200">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-black/35 backdrop-blur-md border border-amber-400/30 shadow-xs">
                    <span class="text-amber-400">🌿</span> 100% Breathable Cotton (210 TC)
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-black/35 backdrop-blur-md border border-amber-400/30 shadow-xs">
                    <span class="text-amber-400">🖨️</span> Sanganeri &amp; Bagru Block Prints
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-black/35 backdrop-blur-md border border-amber-400/30 shadow-xs">
                    <span class="text-amber-400">👑</span> King (108"×108") &amp; Queen (90"×100")
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-black/35 backdrop-blur-md border border-amber-400/30 shadow-xs">
                    <span class="text-amber-400">✨</span> Includes 2 Matching Pillow Covers
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-black/35 backdrop-blur-md border border-amber-400/30 shadow-xs">
                    <span class="text-amber-400">🚚</span> Free Shipping &amp; COD Available
                </span>
            </div>

            <!-- Price Guarantee & Quick Anchor CTA -->
            <div class="mt-8 flex flex-wrap justify-center items-center gap-4">
                <a href="#collection-grid"
                   class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 text-xs sm:text-sm font-extrabold uppercase tracking-wider transition-all shadow-xl hover:shadow-amber-500/20 hover:-translate-y-0.5 flex items-center gap-2">
                    <span>Explore Bedsheet Designs</span>
                    <span>↓</span>
                </a>
                <span class="text-xs text-rose-200/80 font-medium">
                    Special Introductory Price: Starting at <strong>₹699</strong> (MRP ₹1,399)
                </span>
            </div>
        </div>

        <!-- Decorative Bottom Jharokha Wave Border -->
        <div class="h-4 bg-gradient-to-r from-amber-600 via-amber-400 to-amber-600 opacity-80"></div>
    </section>

    {{-- ─── SECTION 2: BROWSE & FILTER BAR ─── --}}
    <div id="collection-grid" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 sm:pt-14">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-light-gold/60">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-maroon-800">Jaipur Heritage Collection</span>
                <h2 class="font-serif text-2xl sm:text-3xl text-rich-black mt-0.5">
                    Handcrafted Cotton Bedsheet Designs
                </h2>
                <p class="text-xs sm:text-sm text-rich-black/70 mt-1">
                    Showing {{ $products->count() }} authentic Jaipuri patterns • Each double bedsheet includes 2 matching pillow covers
                </p>
            </div>

            <!-- Size Selector Pills -->
            <div class="flex items-center gap-2 bg-warm-beige/60 p-1.5 rounded-xl border border-light-gold/80 self-start md:self-auto">
                <span class="text-xs font-semibold text-rich-black/70 px-2">Size:</span>
                <button type="button"
                        @click="activeSize = 'all'"
                        :class="activeSize === 'all' ? 'bg-maroon-800 text-white shadow-xs' : 'text-rich-black/80 hover:text-maroon-800'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                    All Sizes
                </button>
                <button type="button"
                        @click="activeSize = 'king'"
                        :class="activeSize === 'king' ? 'bg-maroon-800 text-white shadow-xs' : 'text-rich-black/80 hover:text-maroon-800'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                    King (108" × 108")
                </button>
                <button type="button"
                        @click="activeSize = 'queen'"
                        :class="activeSize === 'queen' ? 'bg-maroon-800 text-white shadow-xs' : 'text-rich-black/80 hover:text-maroon-800'"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                    Queen (90" × 100")
                </button>
            </div>
        </div>

        {{-- ─── SECTION 3: PRODUCT GRID ─── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 py-8 sm:py-12">
            @foreach($products as $product)
                @php
                    $primaryImg = $product->images->first();
                    $kingVariant = $product->variants->first(fn($v) => str_contains(strtolower($v->sku), 'kng')) ?? $product->variants->first();
                    $queenVariant = $product->variants->first(fn($v) => str_contains(strtolower($v->sku), 'qen')) ?? $product->variants->last();
                @endphp

                <div class="bg-white rounded-2xl overflow-hidden border border-light-gold/80 hover:border-maroon-800/40 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col group">
                    <!-- Product Image Container -->
                    <div class="relative aspect-4/3 overflow-hidden bg-warm-beige/30">
                        @if($primaryImg)
                            <img src="{{ $primaryImg->url }}"
                                 alt="{{ $primaryImg->alt_text ?: $product->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                 loading="lazy">
                        @endif

                        <!-- Top Badges -->
                        <div class="absolute top-3 inset-x-3 flex items-center justify-between pointer-events-none z-10">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-maroon-900/90 text-amber-300 text-[10.5px] font-bold backdrop-blur-xs shadow-xs border border-amber-400/30">
                                <span>🌿</span>
                                <span>100% Pure Cotton</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/90 text-maroon-950 text-[10.5px] font-extrabold backdrop-blur-xs shadow-xs">
                                50% OFF
                            </span>
                        </div>

                        <!-- Included In Package Overlay Badge -->
                        <div class="absolute bottom-2.5 inset-x-2.5 px-2.5 py-1 rounded-lg bg-black/65 backdrop-blur-sm text-white text-[10.5px] flex items-center justify-between pointer-events-none">
                            <span>🛏️ 1 Bedsheet + 2 Pillow Covers</span>
                            <span class="text-amber-300 font-bold">210 TC</span>
                        </div>
                    </div>

                    <!-- Product Card Content -->
                    <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <!-- Star Rating -->
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-1 text-amber-500 font-bold">
                                    <span>★★★★★</span>
                                    <span class="text-rich-black/70 text-[11px] font-semibold">(4.9)</span>
                                </div>
                                <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                    ✓ In Stock
                                </span>
                            </div>

                            <!-- Title -->
                            <h3 class="font-serif text-base sm:text-lg font-medium text-rich-black group-hover:text-maroon-800 transition-colors line-clamp-2 leading-snug">
                                <a href="{{ url('/product/' . $product->slug) }}">
                                    {{ $product->name }}
                                </a>
                            </h3>

                            <p class="text-xs text-rich-black/65 line-clamp-2 leading-relaxed">
                                {{ $product->short_description }}
                            </p>
                        </div>

                        <!-- Dynamic Size Selection & Price Display -->
                        <div class="space-y-3 pt-2 border-t border-light-gold/50">
                            <!-- Size Selector Tabs -->
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-rich-black/70 block mb-1.5">
                                    Select Size:
                                </span>
                                <div class="grid grid-cols-2 gap-2">
                                    @if($kingVariant)
                                        <button type="button"
                                                @click="setProductVariant('{{ $product->id }}', '{{ $kingVariant->id }}', '{{ $kingVariant->price_rupees }}', '{{ $kingVariant->mrp_rupees }}', 'King')"
                                                :class="selectedVariants['{{ $product->id }}'] == '{{ $kingVariant->id }}' ? 'border-maroon-800 bg-maroon-50 text-maroon-900 font-bold ring-1 ring-maroon-800' : 'border-light-gold text-rich-black/70 hover:border-maroon-800/40'"
                                                class="px-2 py-1.5 rounded-lg border text-center transition-all text-xs">
                                            <div class="font-semibold">King Size</div>
                                            <div class="text-[10px] text-rich-black/60">108" × 108"</div>
                                        </button>
                                    @endif

                                    @if($queenVariant)
                                        <button type="button"
                                                @click="setProductVariant('{{ $product->id }}', '{{ $queenVariant->id }}', '{{ $queenVariant->price_rupees }}', '{{ $queenVariant->mrp_rupees }}', 'Queen')"
                                                :class="selectedVariants['{{ $product->id }}'] == '{{ $queenVariant->id }}' ? 'border-maroon-800 bg-maroon-50 text-maroon-900 font-bold ring-1 ring-maroon-800' : 'border-light-gold text-rich-black/70 hover:border-maroon-800/40'"
                                                class="px-2 py-1.5 rounded-lg border text-center transition-all text-xs">
                                            <div class="font-semibold">Queen Size</div>
                                            <div class="text-[10px] text-rich-black/60">90" × 100"</div>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Dynamic Price -->
                            <div class="flex items-baseline gap-2">
                                <span class="text-xl sm:text-2xl font-bold text-maroon-800"
                                      x-text="'₹' + selectedPrices['{{ $product->id }}']">
                                    ₹{{ $kingVariant?->price_rupees ?? 799 }}
                                </span>
                                <span class="text-xs text-rich-black/50 line-through"
                                      x-text="'₹' + selectedMrps['{{ $product->id }}']">
                                    ₹{{ $kingVariant?->mrp_rupees ?? 1599 }}
                                </span>
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">
                                    50% OFF
                                </span>
                            </div>

                            <!-- Action Buttons: Quick Add & Buy Now -->
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <button type="button"
                                        @click="addToCart(selectedVariants['{{ $product->id }}'], 1)"
                                        class="w-full py-2.5 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-bold uppercase tracking-wider hover:bg-maroon-50 transition-colors flex items-center justify-center gap-1 shadow-2xs">
                                    <span>+ Bag</span>
                                </button>
                                <button type="button"
                                        @click="buyNow(selectedVariants['{{ $product->id }}'], 1)"
                                        class="w-full py-2.5 rounded-lg bg-maroon-800 hover:bg-maroon-900 text-white text-xs font-bold uppercase tracking-wider transition-colors flex items-center justify-center gap-1 shadow-xs">
                                    <span>Buy Now</span>
                                </button>
                            </div>

                            <!-- Direct Product Link -->
                            <a href="{{ url('/product/' . $product->slug) }}"
                               class="block text-center text-[11px] font-semibold text-rich-black/65 hover:text-maroon-800 transition-colors pt-1">
                                View Full Details &amp; Specifications ➔
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ─── SECTION 4: BED SIZE & DIMENSIONS GUIDE ─── --}}
    <section class="py-12 sm:py-16 bg-warm-beige/40 border-y border-light-gold/60 mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto space-y-3 mb-10">
                <span class="text-xs font-bold uppercase tracking-widest text-maroon-800">Size &amp; Fit Guide</span>
                <h2 class="font-serif text-2xl sm:text-3xl text-rich-black">
                    Choose The Perfect Bedsheet For Your Mattress
                </h2>
                <p class="text-xs sm:text-sm text-rich-black/70">
                    Our Jaipuri bedsheets are intentionally crafted with generous margins to tuck securely under deep mattresses (up to 8 inches) without slipping.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                <!-- King Size Card -->
                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-light-gold shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-maroon-50 text-maroon-800 text-xs font-bold uppercase tracking-wider border border-maroon-200">
                            <span>👑</span> King Size Bedsheet
                        </div>
                        <span class="text-xs font-bold text-maroon-800">Most Popular</span>
                    </div>

                    <div class="space-y-2 text-sm text-rich-black/85">
                        <div class="flex justify-between py-1.5 border-b border-light-gold/40">
                            <span class="font-semibold">Bedsheet Dimensions:</span>
                            <span class="font-mono text-xs font-bold">108" × 108" (274 × 274 cm)</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-light-gold/40">
                            <span class="font-semibold">Pillow Covers (2 Pcs):</span>
                            <span class="font-mono text-xs font-bold">18" × 27" (46 × 69 cm)</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-light-gold/40">
                            <span class="font-semibold">Best Suited Bed Size:</span>
                            <span class="text-xs font-medium">6 ft × 6 ft, 6 ft × 6.5 ft, 6.5 ft × 6.5 ft King Bed</span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="font-semibold">Mattress Thickness:</span>
                            <span class="text-xs font-medium">Up to 8–10 inches deep</span>
                        </div>
                    </div>

                    <div class="pt-2 text-center">
                        <a href="{{ url('/jaipuri-bedsheets?size=king') }}"
                           class="inline-block w-full py-2.5 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-bold uppercase tracking-wider hover:bg-maroon-800 hover:text-white transition-colors">
                            Explore King Size Sets (₹799)
                        </a>
                    </div>
                </div>

                <!-- Queen Size Card -->
                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-light-gold shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-900 text-xs font-bold uppercase tracking-wider border border-amber-200">
                            <span>🛏️</span> Queen Size Bedsheet
                        </div>
                        <span class="text-xs font-semibold text-rich-black/60">Standard Double Bed</span>
                    </div>

                    <div class="space-y-2 text-sm text-rich-black/85">
                        <div class="flex justify-between py-1.5 border-b border-light-gold/40">
                            <span class="font-semibold">Bedsheet Dimensions:</span>
                            <span class="font-mono text-xs font-bold">90" × 100" (228 × 254 cm)</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-light-gold/40">
                            <span class="font-semibold">Pillow Covers (2 Pcs):</span>
                            <span class="font-mono text-xs font-bold">18" × 27" (46 × 69 cm)</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-light-gold/40">
                            <span class="font-semibold">Best Suited Bed Size:</span>
                            <span class="text-xs font-medium">5 ft × 6 ft, 5 ft × 6.5 ft Queen Bed</span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="font-semibold">Mattress Thickness:</span>
                            <span class="text-xs font-medium">Up to 6–8 inches deep</span>
                        </div>
                    </div>

                    <div class="pt-2 text-center">
                        <a href="{{ url('/jaipuri-bedsheets?size=queen') }}"
                           class="inline-block w-full py-2.5 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-bold uppercase tracking-wider hover:bg-maroon-800 hover:text-white transition-colors">
                            Explore Queen Size Sets (₹699)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── SECTION 5: CRAFTSMANSHIP & HERITAGE NARRATIVE ─── --}}
    <section class="py-12 sm:py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            <div class="lg:col-span-5 relative">
                <div class="rounded-2xl overflow-hidden border border-light-gold/80 shadow-xl bg-warm-beige/30 aspect-4/3">
                    <img src="{{ asset('media/bedsheets/bagru_maroon.webp') }}"
                         alt="Traditional Jaipuri Block Printing Craftsmanship"
                         class="w-full h-full object-cover"
                         loading="lazy">
                </div>
                <!-- Floating Heritage Tag -->
                <div class="absolute -bottom-4 -right-4 bg-maroon-900 text-amber-300 p-4 rounded-xl shadow-xl border border-amber-400/30 max-w-xs hidden sm:block">
                    <span class="text-xs font-bold uppercase tracking-wider block">Pink City Handcraft</span>
                    <p class="text-[11px] text-white/80 mt-1">Carved wooden blocks stamped by hand with centuries-old precision.</p>
                </div>
            </div>

            <div class="lg:col-span-7 space-y-5">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gold-400/20 text-maroon-800 text-xs font-bold uppercase tracking-wider border border-gold-400/40">
                    <span>✨</span>
                    <span>The Jaipur Textile Heritage</span>
                </div>

                <h2 class="font-serif text-2xl sm:text-4xl text-maroon-900 leading-tight">
                    Why Authentic Jaipur Cotton Bedsheets Are Loved Worldwide
                </h2>

                <p class="text-sm text-rich-black/80 leading-relaxed">
                    Originating in Sanganer and Bagru villages on the outskirts of Jaipur, Rajasthani handblock printing represents over 500 years of living artisan heritage. Every flower, leaf vine, and Mughal border is hand-stamped onto pure cotton fabric using hand-carved teak wooden blocks.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="p-4 rounded-xl bg-white border border-light-gold/60 shadow-xs space-y-1">
                        <div class="text-lg text-maroon-800">🌿</div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">100% Breathable Cotton</h4>
                        <p class="text-[11.5px] text-rich-black/70">Pure natural cotton weave allows air circulation, keeping you cool during warm Indian summers and pleasantly cozy during winter.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-white border border-light-gold/60 shadow-xs space-y-1">
                        <div class="text-lg text-maroon-800">🛡️</div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">Fast Colors Guarantee</h4>
                        <p class="text-[11.5px] text-rich-black/70">Pre-washed with skin-safe, azo-free dyes so colors remain vivid wash after wash without bleeding onto your clothes.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-white border border-light-gold/60 shadow-xs space-y-1">
                        <div class="text-lg text-maroon-800">🖨️</div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">Artisan Wooden Block Print</h4>
                        <p class="text-[11.5px] text-rich-black/70">Each print carries subtle, authentic handcrafted nuances that machine prints can never replicate, adding unique character to your bedroom.</p>
                    </div>

                    <div class="p-4 rounded-xl bg-white border border-light-gold/60 shadow-xs space-y-1">
                        <div class="text-lg text-maroon-800">✨</div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">Double Bed + Pillow Covers</h4>
                        <p class="text-[11.5px] text-rich-black/70">Every bedsheet arrives with two perfectly matching flapped pillow covers (18" × 27") for a coordinated luxury look.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── SECTION 6: CARE & WASH INSTRUCTIONS ─── --}}
    <section class="py-10 bg-warm-beige/30 border-t border-light-gold/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 p-6 sm:p-8 rounded-2xl bg-white border border-light-gold shadow-xs">
                <div class="space-y-1 text-center md:text-left">
                    <span class="text-xs font-bold uppercase tracking-wider text-maroon-800">Easy Maintenance</span>
                    <h3 class="font-serif text-xl sm:text-2xl text-rich-black">Simple Wash &amp; Care Guide</h3>
                    <p class="text-xs sm:text-sm text-rich-black/70">Follow these 4 simple tips to keep your pure cotton bedsheets soft and vivid for years.</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                    <div class="p-3 rounded-xl bg-ivory border border-light-gold/50">
                        <span class="text-xl">💧</span>
                        <div class="text-xs font-bold text-rich-black mt-1">Cold Water</div>
                        <div class="text-[10px] text-rich-black/60">Gentle cycle</div>
                    </div>
                    <div class="p-3 rounded-xl bg-ivory border border-light-gold/50">
                        <span class="text-xl">🧴</span>
                        <div class="text-xs font-bold text-rich-black mt-1">Mild Detergent</div>
                        <div class="text-[10px] text-rich-black/60">No harsh bleach</div>
                    </div>
                    <div class="p-3 rounded-xl bg-ivory border border-light-gold/50">
                        <span class="text-xl">☀️</span>
                        <div class="text-xs font-bold text-rich-black mt-1">Shade Dry</div>
                        <div class="text-[10px] text-rich-black/60">Preserves colors</div>
                    </div>
                    <div class="p-3 rounded-xl bg-ivory border border-light-gold/50">
                        <span class="text-xl">👔</span>
                        <div class="text-xs font-bold text-rich-black mt-1">Warm Iron</div>
                        <div class="text-[10px] text-rich-black/60">For crisp hotel look</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ─── SECTION 7: FREQUENTLY ASKED QUESTIONS ─── --}}
    <section class="py-12 sm:py-16 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="text-center space-y-2">
            <span class="text-xs font-bold uppercase tracking-widest text-maroon-800">Help &amp; Questions</span>
            <h2 class="font-serif text-2xl sm:text-3xl text-rich-black">
                Frequently Asked Questions
            </h2>
        </div>

        <div class="space-y-3" x-data="{ openFaq: null }">
            <div class="bg-white rounded-xl border border-light-gold/80 p-4 cursor-pointer hover:border-maroon-800/40 transition-colors"
                 @click="openFaq = openFaq === 1 ? null : 1">
                <div class="flex items-center justify-between font-semibold text-sm text-rich-black">
                    <span>What is included in the package?</span>
                    <span class="text-maroon-800 text-lg" x-text="openFaq === 1 ? '−' : '+'">+</span>
                </div>
                <div x-show="openFaq === 1" x-collapse class="mt-2 text-xs sm:text-sm text-rich-black/75 leading-relaxed pt-2 border-t border-light-gold/40">
                    Each package contains 1 double bedsheet (King Size: 108" × 108" or Queen Size: 90" × 100") and 2 matching pillow covers (18" × 27") with envelope flap closures.
                </div>
            </div>

            <div class="bg-white rounded-xl border border-light-gold/80 p-4 cursor-pointer hover:border-maroon-800/40 transition-colors"
                 @click="openFaq = openFaq === 2 ? null : 2">
                <div class="flex items-center justify-between font-semibold text-sm text-rich-black">
                    <span>Does the color bleed or fade after washing?</span>
                    <span class="text-maroon-800 text-lg" x-text="openFaq === 2 ? '−' : '+'">+</span>
                </div>
                <div x-show="openFaq === 2" x-collapse class="mt-2 text-xs sm:text-sm text-rich-black/75 leading-relaxed pt-2 border-t border-light-gold/40">
                    No. Our bedsheets undergo a specialized pre-washing and color-fixing process using skin-friendly azo-free dyes. For the very first wash, wash separately in cold water with a pinch of salt to naturally preserve the artisanal colors.
                </div>
            </div>

            <div class="bg-white rounded-xl border border-light-gold/80 p-4 cursor-pointer hover:border-maroon-800/40 transition-colors"
                 @click="openFaq = openFaq === 3 ? null : 3">
                <div class="flex items-center justify-between font-semibold text-sm text-rich-black">
                    <span>Is Cash on Delivery (COD) available for bedsheets?</span>
                    <span class="text-maroon-800 text-lg" x-text="openFaq === 3 ? '−' : '+'">+</span>
                </div>
                <div x-show="openFaq === 3" x-collapse class="mt-2 text-xs sm:text-sm text-rich-black/75 leading-relaxed pt-2 border-t border-light-gold/40">
                    Yes! We offer Cash on Delivery across 19,000+ pin codes in India via our official logistics partners Delhivery and Shiprocket. You pay only upon delivery.
                </div>
            </div>

            <div class="bg-white rounded-xl border border-light-gold/80 p-4 cursor-pointer hover:border-maroon-800/40 transition-colors"
                 @click="openFaq = openFaq === 4 ? null : 4">
                <div class="flex items-center justify-between font-semibold text-sm text-rich-black">
                    <span>How long does delivery take?</span>
                    <span class="text-maroon-800 text-lg" x-text="openFaq === 4 ? '−' : '+'">+</span>
                </div>
                <div x-show="openFaq === 4" x-collapse class="mt-2 text-xs sm:text-sm text-rich-black/75 leading-relaxed pt-2 border-t border-light-gold/40">
                    Orders are dispatched directly from our Jaipur workshop within 24 hours. Standard delivery takes 3–5 business days for metro cities and 5–7 business days for the rest of India. You will receive live SMS and WhatsApp tracking updates.
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
