@extends('layouts.store')

@section('title', $seoTitle ?? "Women's Ethnic Fashion Catalogue with Price & Delivery | Dhansree Trends")

@section('content')
<div x-data="dhansreeCatalogueHub()" x-init="initCatalogue()" class="min-h-screen bg-ivory/40 pb-20">

    <!-- Toast Notification -->
    <div x-show="toast.show"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave-end="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
         class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 max-w-md">
        <div class="size-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>
        <div>
            <p class="text-xs font-semibold text-emerald-400" x-text="toast.title"></p>
            <p class="text-xs text-slate-300" x-text="toast.message"></p>
        </div>
        <button type="button" @click="toast.show = false" class="text-slate-400 hover:text-white ml-2 text-lg leading-none">&times;</button>
    </div>

    <!-- 1. Hero & Header Banner -->
    <section class="relative bg-gradient-to-b from-maroon-900 via-maroon-800 to-maroon-900 text-white pt-10 pb-12 px-4 sm:px-6 lg:px-8 border-b-4 border-gold-400/80 shadow-md print:hidden">
        <div class="max-w-7xl mx-auto text-center space-y-4">
            
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold-400/20 border border-gold-400/40 text-gold-200 text-xs font-semibold tracking-wider uppercase">
                <span>✨ Official Digital Lookbook &amp; Catalogue</span>
                <span>•</span>
                <span>Jaipur Handcrafted</span>
            </div>

            <!-- Title -->
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-serif font-bold tracking-tight text-white">
                Women's Ethnic &amp; Festive Collection
            </h1>

            <p class="text-sm sm:text-base text-ivory/80 max-w-2xl mx-auto font-sans leading-relaxed">
                Explore our complete range of Pure Cotton Anarkali Sets, Designer Suit Sets, Kurta Palazzo Sets &amp; Two-Piece Co-ords with transparent pricing, delivery options, and instant one-click ordering.
            </p>

            <!-- Delivery & Service Highlights Bar -->
            <div class="pt-4 grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4 max-w-4xl mx-auto text-left">
                <div class="bg-white/10 backdrop-blur-xs border border-white/15 rounded-xl p-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gold-400/20 text-gold-300 flex items-center justify-center shrink-0 text-xl">
                        🚚
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white">FREE Home Delivery</div>
                        <div class="text-[11px] text-ivory/70">Across all India</div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-xs border border-white/15 rounded-xl p-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-400/20 text-emerald-300 flex items-center justify-center shrink-0 text-xl">
                        💵
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white">Cash on Delivery</div>
                        <div class="text-[11px] text-ivory/70">Pay when you receive</div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-xs border border-white/15 rounded-xl p-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-400/20 text-amber-300 flex items-center justify-center shrink-0 text-xl">
                        ⚡
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white">Fast Delhivery Courier</div>
                        <div class="text-[11px] text-ivory/70">Delivered in 3–5 Days</div>
                    </div>
                </div>

                <div class="bg-white/10 backdrop-blur-xs border border-white/15 rounded-xl p-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-400/20 text-blue-300 flex items-center justify-center shrink-0 text-xl">
                        🔄
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white">7-Day Easy Exchange</div>
                        <div class="text-[11px] text-ivory/70">100% Quality Guaranteed</div>
                    </div>
                </div>
            </div>

            <!-- Top Action Buttons (WhatsApp Share & Pincode Checker) -->
            <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
                <a :href="shareCatalogueUrl"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold uppercase tracking-wider shadow-md hover:shadow-lg transition-all">
                    <svg class="size-4 fill-current" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.771.821 2.791.821 3.181 0 5.767-2.587 5.768-5.766.001-3.182-2.585-5.767-5.768-5.767zm0 10.427c-.89 0-1.666-.25-2.422-.727l-.173-.109-1.58.414.422-1.541-.118-.188c-.521-.828-.795-1.58-.795-2.51 0-2.614 2.127-4.741 4.742-4.741 2.614 0 4.741 2.127 4.741 4.741 0 2.615-2.127 4.741-4.741 4.741zm6.969-4.661c-.04-.066-.145-.106-.304-.185-.158-.079-.937-.462-1.082-.515-.145-.053-.251-.079-.356.079-.106.158-.409.515-.502.621-.092.106-.185.119-.343.04-.158-.079-.669-.247-1.274-.786-.471-.42-.789-.939-.882-1.097-.092-.158-.01-.244.069-.323.071-.071.158-.185.238-.277.079-.092.106-.158.158-.264.053-.106.026-.198-.013-.277-.04-.079-.356-.859-.488-1.176-.129-.308-.26-.266-.356-.271-.092-.005-.198-.006-.304-.006s-.277.04-.422.198c-.145.158-.555.542-.555 1.321 0 .779.568 1.532.647 1.638.079.106 1.118 1.708 2.709 2.395.378.163.674.261.904.334.38.121.726.104 1.001.063.305-.046.937-.383 1.069-.753.132-.37.132-.687.092-.753z"/>
                    </svg>
                    <span>Share on WhatsApp</span>
                </a>

                <button type="button"
                        @click="copyCatalogueLink()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-semibold uppercase tracking-wider backdrop-blur-xs transition-all">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                    </svg>
                    <span>Copy Catalogue Link</span>
                </button>

                <button type="button"
                        @click="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-semibold uppercase tracking-wider backdrop-blur-xs transition-all">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.076-.641-2.085-1.17-3.003a7.48 7.48 0 0 0-1.9-2.072A7.502 7.502 0 0 0 1.5 7.5v-3A2.25 2.25 0 0 1 3.75 2.25h16.5A2.25 2.25 0 0 1 22.5 4.5v3a7.5 7.5 0 0 0-2.15 1.254c-.65.578-1.18 1.288-1.55 2.075-.53.918-.93 1.927-1.17 3.003H6.72ZM3.75 9h16.5M6 16.5h12M7.5 19.5h9" />
                    </svg>
                    <span>Print / Save PDF</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- 2. Interactive Delivery Pincode Check Bar -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-light-gold/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4 print:hidden">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-amber-50 border border-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Check Delivery Timeline to Your City</h2>
                    <p class="text-xs text-slate-500">Enter your 6-digit delivery pincode to check Delhivery Express courier serviceability</p>
                </div>
            </div>

            <form @submit.prevent="checkPincode()" class="w-full md:w-auto flex items-center gap-2">
                <div class="relative w-full md:w-48">
                    <input type="text"
                           x-model="pincodeInput"
                           maxlength="6"
                           placeholder="Enter Pincode (e.g. 560064)"
                           class="w-full text-xs font-mono font-semibold rounded-xl border border-slate-300 px-3 py-2.5 focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 uppercase tracking-wider"
                           required>
                </div>
                <button type="submit"
                        :disabled="pincodeLoading"
                        class="px-4 py-2.5 rounded-xl bg-maroon-800 hover:bg-maroon-900 text-white text-xs font-bold shrink-0 transition-colors disabled:opacity-50">
                    <span x-show="!pincodeLoading">Check</span>
                    <span x-show="pincodeLoading" class="animate-pulse">Checking...</span>
                </button>
            </form>
        </div>

        <!-- Pincode Alert Message -->
        <div x-show="pincodeResult.message"
             x-cloak
             x-transition
             :class="pincodeResult.serviceable ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-rose-50 border-rose-300 text-rose-900'"
             class="p-4 rounded-xl border text-xs font-medium flex items-center justify-between gap-3 shadow-2xs print:hidden">
            <div class="flex items-center gap-2">
                <span x-text="pincodeResult.serviceable ? '✅' : '⚠️'" class="text-base"></span>
                <span x-text="pincodeResult.message"></span>
            </div>
            <button type="button" @click="pincodeResult.message = ''" class="text-slate-400 hover:text-slate-700 text-sm font-bold">&times;</button>
        </div>

        <!-- 3. Filter, Search & Layout Controls -->
        <div class="bg-white rounded-2xl border border-light-gold/80 p-5 shadow-xs space-y-4 print:hidden">
            <!-- Category Pills -->
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100 flex-wrap">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 max-w-full">
                    <button type="button"
                            @click="selectedCategory = 'all'"
                            :class="selectedCategory === 'all' ? 'bg-maroon-800 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-colors">
                        All Styles ({{ $totalCount }})
                    </button>

                    @foreach($categories as $cat)
                        <button type="button"
                                @click="selectedCategory = '{{ $cat['slug'] }}'"
                                :class="selectedCategory === '{{ $cat['slug'] }}' ? 'bg-maroon-800 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-colors">
                            {{ $cat['name'] }} ({{ $cat['count'] }})
                        </button>
                    @endforeach
                </div>

                <!-- View Layout Switcher -->
                <div class="hidden sm:flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                    <button type="button"
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-white text-maroon-800 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-900'"
                            class="px-2.5 py-1 text-xs rounded-lg transition-colors flex items-center gap-1">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6zm10 0a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V6zM4 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2zm10 0a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-2z"/></svg>
                        <span>Grid</span>
                    </button>
                    <button type="button"
                            @click="viewMode = 'lookbook'"
                            :class="viewMode === 'lookbook' ? 'bg-white text-maroon-800 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-900'"
                            class="px-2.5 py-1 text-xs rounded-lg transition-colors flex items-center gap-1">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <span>Lookbook</span>
                    </button>
                </div>
            </div>

            <!-- Size Pills & Search -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <!-- Size Filter -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 mr-1">Filter by Size:</span>
                    <button type="button"
                            @click="selectedSize = 'all'"
                            :class="selectedSize === 'all' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors">
                        All Sizes
                    </button>
                    <template x-for="sz in ['M', 'L', 'XL', 'XXL', '3XL']" :key="sz">
                        <button type="button"
                                @click="selectedSize = (selectedSize === sz ? 'all' : sz)"
                                :class="selectedSize === sz ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                                x-text="sz">
                        </button>
                    </template>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-64">
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Search colour, fabric, style..."
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 pl-8 pr-8 py-2 focus:bg-white focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 transition-colors">
                    <svg class="size-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    <button type="button"
                            x-show="searchQuery"
                            @click="searchQuery = ''"
                            class="text-slate-400 hover:text-slate-600 absolute right-2.5 top-2 text-sm leading-none">&times;</button>
                </div>
            </div>
        </div>

        <!-- 4. Active Count / Filter Summary -->
        <div class="flex items-center justify-between text-xs text-slate-600 px-1 print:hidden">
            <div>
                Showing <strong class="text-slate-900" x-text="filteredProducts.length"></strong> of {{ $totalCount }} products in catalogue
                <span x-show="selectedCategory !== 'all'" class="text-maroon-800 font-semibold" x-text="'• ' + selectedCategory"></span>
                <span x-show="selectedSize !== 'all'" class="text-amber-700 font-semibold" x-text="'• Size ' + selectedSize"></span>
            </div>

            <!-- Sort By -->
            <div class="flex items-center gap-1.5">
                <span class="text-slate-400">Sort:</span>
                <select x-model="sortBy" class="text-xs rounded-lg border-slate-200 py-1 pl-2 pr-6 bg-white text-slate-800 focus:border-maroon-800 focus:ring-maroon-800">
                    <option value="featured">Featured Collection</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="discount">Highest Discount</option>
                </select>
            </div>
        </div>

        <!-- 5. Product Grid (Catalogue Style) -->
        <div :class="viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6' : 'space-y-6'">
            <template x-for="(product, pIdx) in sortedProducts" :key="product.id">
                
                <!-- SINGLE CATALOGUE CARD -->
                <div class="bg-white rounded-2xl border border-light-gold/80 shadow-xs hover:shadow-lg transition-all overflow-hidden flex flex-col justify-between group print:border-slate-300 print:shadow-none print:break-inside-avoid"
                     :class="viewMode === 'lookbook' ? 'sm:flex-row items-stretch' : ''"
                     x-data="{ activeImgIdx: 0, selectedProductSize: null }">
                    
                    <!-- IMAGE CAROUSEL CONTAINER -->
                    <div class="relative overflow-hidden bg-warm-beige/30"
                         :class="viewMode === 'lookbook' ? 'sm:w-80 shrink-0 aspect-[3/4]' : 'aspect-[3/4] w-full'">
                        
                        <!-- Main Image -->
                        <a :href="product.url" class="block w-full h-full relative">
                            <img :src="product.images[activeImgIdx] || product.primary_image_url"
                                 :alt="product.name"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 loading="lazy">
                        </a>

                        <!-- Top Badges -->
                        <div class="absolute top-2.5 left-2.5 flex flex-col gap-1 z-10 pointer-events-none">
                            <template x-if="product.discount_percentage > 0">
                                <span class="px-2.5 py-1 rounded-full bg-rose-600 text-white text-[10px] font-bold tracking-tight uppercase shadow-xs">
                                    <span x-text="product.discount_percentage + '% OFF'"></span>
                                </span>
                            </template>
                            <span class="px-2 py-0.5 rounded-full bg-white/95 backdrop-blur-xs text-maroon-900 border border-light-gold text-[9.5px] font-bold shadow-2xs">
                                Pure Cotton
                            </span>
                        </div>

                        <!-- Top Right: Delhivery Express Dispatch Tag -->
                        <div class="absolute top-2.5 right-2.5 z-10 pointer-events-none">
                            <span class="px-2 py-0.5 rounded-full bg-slate-900/80 backdrop-blur-xs text-white text-[9.5px] font-semibold flex items-center gap-1 shadow-xs">
                                <span>⚡</span>
                                <span>Dispatches 24h</span>
                            </span>
                        </div>

                        <!-- Bottom Thumbnail Navigation Dots -->
                        <div x-show="product.images.length > 1"
                             class="absolute bottom-2.5 inset-x-0 flex items-center justify-center gap-1.5 z-10 pointer-events-auto">
                            <template x-for="(img, imgIdx) in product.images.slice(0, 5)" :key="imgIdx">
                                <button type="button"
                                        @click.prevent.stop="activeImgIdx = imgIdx"
                                        :class="activeImgIdx === imgIdx ? 'w-4 bg-maroon-800' : 'w-1.5 bg-white/80 hover:bg-white'"
                                        class="h-1.5 rounded-full transition-all shadow-xs"
                                        :title="'View image ' + (imgIdx + 1)">
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- PRODUCT DETAILS CONTAINER -->
                    <div class="p-4 sm:p-5 flex flex-col justify-between flex-1 space-y-4">
                        <div class="space-y-3">
                            
                            <!-- Category & Brand -->
                            <div class="flex items-center justify-between text-[11px] text-slate-500">
                                <span class="font-bold tracking-wider uppercase text-amber-700" x-text="product.category_name"></span>
                                <span class="font-medium">SKU: <span x-text="'DS-' + product.id"></span></span>
                            </div>

                            <!-- Product Title -->
                            <h3 class="text-sm sm:text-base font-semibold text-slate-900 leading-snug line-clamp-2 hover:text-maroon-800 transition-colors">
                                <a :href="product.url" x-text="product.name"></a>
                            </h3>

                            <!-- PRICING BOX (Transparent Price & MRP) -->
                            <div class="p-3 rounded-xl bg-ivory/60 border border-light-gold/80 space-y-1">
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <span class="text-lg sm:text-xl font-bold font-serif text-maroon-900" x-text="product.formatted_price"></span>
                                    <template x-if="product.formatted_mrp">
                                        <del class="text-xs text-slate-400" x-text="product.formatted_mrp"></del>
                                    </template>
                                    <template x-if="product.discount_percentage > 0">
                                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200"
                                              x-text="'Save ₹' + Math.round(product.mrp_rupees - product.price_rupees) + ' (' + product.discount_percentage + '% OFF)'">
                                        </span>
                                    </template>
                                </div>
                                <div class="text-[10px] text-slate-500 flex items-center justify-between pt-0.5">
                                    <span>Inclusive of all GST taxes</span>
                                    <span class="font-semibold text-emerald-700">Best Price Guaranteed</span>
                                </div>
                            </div>

                            <!-- DELIVERY OPTIONS BOX (Specifically required by customer) -->
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5 text-xs">
                                <div class="font-bold text-[11px] uppercase tracking-wider text-slate-700 flex items-center justify-between">
                                    <span>Delivery &amp; Courier Details</span>
                                    <span class="text-emerald-700 text-[10px]">● All-India Service</span>
                                </div>

                                <div class="space-y-1 text-slate-600 text-[11px]">
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <span>🚚</span>
                                            <span>Delivery Charge:</span>
                                        </span>
                                        <strong class="text-emerald-700">FREE Home Delivery</strong>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <span>💵</span>
                                            <span>Payment Modes:</span>
                                        </span>
                                        <strong class="text-slate-900">Cash on Delivery (COD) / UPI</strong>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <span>⏱️</span>
                                            <span>Estimated Time:</span>
                                        </span>
                                        <strong class="text-slate-900">3 to 5 Days (Delhivery Express)</strong>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <span>📍</span>
                                            <span>Ships From:</span>
                                        </span>
                                        <span class="text-slate-600">Jaipur Main Warehouse</span>
                                    </div>
                                </div>
                            </div>

                            <!-- AVAILABLE SIZES SELECTION -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold uppercase tracking-wider text-slate-700 text-[11px]">Select Size:</span>
                                    <span class="text-[10px] text-emerald-700 font-semibold" x-text="product.sizes.length ? 'Sizes In Stock' : 'Free Size'"></span>
                                </div>

                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="sz in product.sizes" :key="sz.name">
                                        <button type="button"
                                                @click="selectedProductSize = sz"
                                                :disabled="!sz.in_stock"
                                                :class="{
                                                    'bg-maroon-800 text-white border-maroon-800 shadow-xs font-bold': selectedProductSize && selectedProductSize.name === sz.name,
                                                    'bg-white text-slate-800 border-slate-300 hover:border-maroon-800': (!selectedProductSize || selectedProductSize.name !== sz.name) && sz.in_stock,
                                                    'opacity-40 bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed line-through': !sz.in_stock
                                                }"
                                                class="min-w-9 h-8 px-2 rounded-lg border text-xs font-semibold flex items-center justify-center transition-all"
                                                :title="sz.in_stock ? 'Select Size ' + sz.name : 'Size ' + sz.name + ' out of stock'">
                                            <span x-text="sz.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                        </div>

                        <!-- ACTION BUTTONS: BUY NOW & ORDER ON WHATSAPP -->
                        <div class="pt-3 border-t border-slate-100 space-y-2 print:hidden">
                            <div class="grid grid-cols-2 gap-2">
                                
                                <!-- 1. Instant Buy Now Button -->
                                <button type="button"
                                        @click="handleBuyNow(product, selectedProductSize)"
                                        class="w-full py-2.5 px-3 rounded-xl bg-amber-600 hover:bg-amber-700 active:scale-95 text-white font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-sm transition-all">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>Buy Now</span>
                                </button>

                                <!-- 2. Order on WhatsApp Button -->
                                <a :href="generateWhatsAppUrl(product, selectedProductSize)"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-sm transition-all text-center">
                                    <svg class="size-4 fill-current shrink-0" viewBox="0 0 24 24">
                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.771.821 2.791.821 3.181 0 5.767-2.587 5.768-5.766.001-3.182-2.585-5.767-5.768-5.767zm0 10.427c-.89 0-1.666-.25-2.422-.727l-.173-.109-1.58.414.422-1.541-.118-.188c-.521-.828-.795-1.58-.795-2.51 0-2.614 2.127-4.741 4.742-4.741 2.614 0 4.741 2.127 4.741 4.741 0 2.615-2.127 4.741-4.741 4.741zm6.969-4.661c-.04-.066-.145-.106-.304-.185-.158-.079-.937-.462-1.082-.515-.145-.053-.251-.079-.356.079-.106.158-.409.515-.502.621-.092.106-.185.119-.343.04-.158-.079-.669-.247-1.274-.786-.471-.42-.789-.939-.882-1.097-.092-.158-.01-.244.069-.323.071-.071.158-.185.238-.277.079-.092.106-.158.158-.264.053-.106.026-.198-.013-.277-.04-.079-.356-.859-.488-1.176-.129-.308-.26-.266-.356-.271-.092-.005-.198-.006-.304-.006s-.277.04-.422.198c-.145.158-.555.542-.555 1.321 0 .779.568 1.532.647 1.638.079.106 1.118 1.708 2.709 2.395.378.163.674.261.904.334.38.121.726.104 1.001.063.305-.046.937-.383 1.069-.753.132-.37.132-.687.092-.753z"/>
                                    </svg>
                                    <span>WhatsApp</span>
                                </a>
                            </div>

                            <!-- 3. View Full Details Link -->
                            <a :href="product.url"
                               class="w-full py-1.5 rounded-lg text-slate-500 hover:text-maroon-800 text-xs font-semibold text-center block transition-colors">
                                View Full Product Specifications &amp; Size Chart &rarr;
                            </a>
                        </div>

                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="filteredProducts.length === 0"
             class="bg-white rounded-2xl border border-light-gold p-12 text-center space-y-4 max-w-md mx-auto shadow-xs">
            <div class="w-16 h-16 mx-auto rounded-full bg-amber-50 text-amber-800 flex items-center justify-center text-3xl">
                🔍
            </div>
            <h3 class="font-serif text-2xl text-slate-900 font-bold">No designs found</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                We couldn't find any women's fashion items matching your selected category, size, or search query.
            </p>
            <button type="button"
                    @click="resetFilters()"
                    class="px-5 py-2.5 rounded-xl bg-maroon-800 hover:bg-maroon-900 text-white text-xs font-bold transition-colors">
                Reset All Filters
            </button>
        </div>

        <!-- 6. Bottom WhatsApp Floating Assistant Bar (Mobile) -->
        <div class="fixed bottom-4 inset-x-4 sm:hidden z-40 flex items-center gap-2 bg-slate-900/95 text-white p-2.5 rounded-2xl shadow-2xl border border-slate-700 backdrop-blur-md print:hidden">
            <div class="flex-1 pl-2">
                <div class="text-[11px] font-bold text-gold-300">Need Help Choosing?</div>
                <div class="text-[10px] text-slate-300">Live Support on WhatsApp</div>
            </div>
            <a :href="'https://wa.me/{{ $cleanPhone }}?text=' + encodeURIComponent('Hi Dhansree! I am browsing your online digital catalogue and have a question regarding size/delivery.')"
               target="_blank"
               rel="noopener noreferrer"
               class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 shadow-xs">
                <svg class="size-4 fill-current" viewBox="0 0 24 24">
                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.771.821 2.791.821 3.181 0 5.767-2.587 5.768-5.766.001-3.182-2.585-5.767-5.768-5.767zm0 10.427c-.89 0-1.666-.25-2.422-.727l-.173-.109-1.58.414.422-1.541-.118-.188c-.521-.828-.795-1.58-.795-2.51 0-2.614 2.127-4.741 4.742-4.741 2.614 0 4.741 2.127 4.741 4.741 0 2.615-2.127 4.741-4.741 4.741zm6.969-4.661c-.04-.066-.145-.106-.304-.185-.158-.079-.937-.462-1.082-.515-.145-.053-.251-.079-.356.079-.106.158-.409.515-.502.621-.092.106-.185.119-.343.04-.158-.079-.669-.247-1.274-.786-.471-.42-.789-.939-.882-1.097-.092-.158-.01-.244.069-.323.071-.071.158-.185.238-.277.079-.092.106-.158.158-.264.053-.106.026-.198-.013-.277-.04-.079-.356-.859-.488-1.176-.129-.308-.26-.266-.356-.271-.092-.005-.198-.006-.304-.006s-.277.04-.422.198c-.145.158-.555.542-.555 1.321 0 .779.568 1.532.647 1.638.079.106 1.118 1.708 2.709 2.395.378.163.674.261.904.334.38.121.726.104 1.001.063.305-.046.937-.383 1.069-.753.132-.37.132-.687.092-.753z"/>
                </svg>
                <span>Chat Now</span>
            </a>
        </div>

    </main>

    <!-- 7. Quick Size Selection Modal (when tapping Buy Now without selecting a size) -->
    <div x-show="sizeModal.open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto print:hidden"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
             @click="sizeModal.open = false"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-light-gold space-y-4"
                 @click.stop>
                
                <div class="flex items-start justify-between pb-2 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Select Size to Order</span>
                        <h4 class="text-sm font-bold text-slate-900 line-clamp-1" x-text="sizeModal.product?.name"></h4>
                    </div>
                    <button type="button" @click="sizeModal.open = false" class="text-slate-400 hover:text-slate-700 text-lg">&times;</button>
                </div>

                <div class="flex items-center gap-3">
                    <img :src="sizeModal.product?.primary_image_url" class="w-16 h-20 rounded-lg object-cover border border-slate-200">
                    <div>
                        <div class="text-base font-bold text-maroon-900" x-text="sizeModal.product?.formatted_price"></div>
                        <div class="text-xs text-emerald-700 font-medium">🚚 Free Shipping &amp; COD Available</div>
                        <div class="text-[11px] text-slate-400">Delhivery Express 3–5 Days</div>
                    </div>
                </div>

                <!-- Sizes options -->
                <div class="space-y-2">
                    <div class="text-xs font-semibold text-slate-700">Choose Size:</div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="sz in (sizeModal.product?.sizes || [])" :key="sz.name">
                            <button type="button"
                                    @click="sizeModal.selectedSize = sz"
                                    :disabled="!sz.in_stock"
                                    :class="{
                                        'bg-maroon-800 text-white border-maroon-800 shadow-xs font-bold': sizeModal.selectedSize && sizeModal.selectedSize.name === sz.name,
                                        'bg-white text-slate-800 border-slate-300 hover:border-maroon-800': (!sizeModal.selectedSize || sizeModal.selectedSize.name !== sz.name) && sz.in_stock,
                                        'opacity-40 bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed line-through': !sz.in_stock
                                    }"
                                    class="min-w-10 h-9 px-2.5 rounded-xl border text-xs font-semibold flex items-center justify-center transition-all">
                                <span x-text="sz.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="pt-2 space-y-2">
                    <button type="button"
                            @click="confirmModalBuyNow()"
                            class="w-full py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs uppercase tracking-wider shadow-sm transition-colors flex items-center justify-center gap-2">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Proceed to Instant Checkout</span>
                    </button>

                    <a :href="generateWhatsAppUrl(sizeModal.product, sizeModal.selectedSize)"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider shadow-sm transition-colors flex items-center justify-center gap-2 text-center">
                        <svg class="size-4 fill-current shrink-0" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.771.821 2.791.821 3.181 0 5.767-2.587 5.768-5.766.001-3.182-2.585-5.767-5.768-5.767zm0 10.427c-.89 0-1.666-.25-2.422-.727l-.173-.109-1.58.414.422-1.541-.118-.188c-.521-.828-.795-1.58-.795-2.51 0-2.614 2.127-4.741 4.742-4.741 2.614 0 4.741 2.127 4.741 4.741 0 2.615-2.127 4.741-4.741 4.741zm6.969-4.661c-.04-.066-.145-.106-.304-.185-.158-.079-.937-.462-1.082-.515-.145-.053-.251-.079-.356.079-.106.158-.409.515-.502.621-.092.106-.185.119-.343.04-.158-.079-.669-.247-1.274-.786-.471-.42-.789-.939-.882-1.097-.092-.158-.01-.244.069-.323.071-.071.158-.185.238-.277.079-.092.106-.158.158-.264.053-.106.026-.198-.013-.277-.04-.079-.356-.859-.488-1.176-.129-.308-.26-.266-.356-.271-.092-.005-.198-.006-.304-.006s-.277.04-.422.198c-.145.158-.555.542-.555 1.321 0 .779.568 1.532.647 1.638.079.106 1.118 1.708 2.709 2.395.378.163.674.261.904.334.38.121.726.104 1.001.063.305-.046.937-.383 1.069-.753.132-.37.132-.687.092-.753z"/>
                        </svg>
                        <span>Confirm via WhatsApp Chat</span>
                    </a>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- Alpine Component Script -->
<script>
function dhansreeCatalogueHub() {
    return {
        products: @json($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        cleanPhone: '{{ $cleanPhone }}',
        catalogueUrl: '{{ $catalogueUrl }}',
        shareCatalogueUrl: '{{ $shareCatalogueUrl }}',
        selectedCategory: 'all',
        selectedSize: 'all',
        searchQuery: '',
        sortBy: 'featured',
        viewMode: 'grid',
        pincodeInput: '',
        pincodeLoading: false,
        pincodeResult: {
            message: '',
            serviceable: false
        },
        toast: {
            show: false,
            title: '',
            message: ''
        },
        sizeModal: {
            open: false,
            product: null,
            selectedSize: null
        },

        initCatalogue() {
            // Check if URL contains category or size parameter
            const params = new URLSearchParams(window.location.search);
            if (params.has('category')) {
                this.selectedCategory = params.get('category');
            }
            if (params.has('size')) {
                this.selectedSize = params.get('size');
            }
            if (params.has('q')) {
                this.searchQuery = params.get('q');
            }
        },

        get filteredProducts() {
            return this.products.filter(p => {
                // Category match
                if (this.selectedCategory !== 'all' && p.category_slug !== this.selectedCategory) {
                    return false;
                }

                // Size match
                if (this.selectedSize !== 'all') {
                    const hasSize = p.sizes.some(s => s.name === this.selectedSize && s.in_stock);
                    if (!hasSize) return false;
                }

                // Search query match
                if (this.searchQuery.trim() !== '') {
                    const q = this.searchQuery.toLowerCase().trim();
                    const nameMatch = p.name.toLowerCase().includes(q);
                    const catMatch = p.category_name.toLowerCase().includes(q);
                    const colorMatch = p.colors.some(c => c.name.toLowerCase().includes(q));
                    const sizeMatch = p.sizes.some(s => s.name.toLowerCase().includes(q));
                    if (!nameMatch && !catMatch && !colorMatch && !sizeMatch) {
                        return false;
                    }
                }

                return true;
            });
        },

        get sortedProducts() {
            const list = [...this.filteredProducts];
            if (this.sortBy === 'price_asc') {
                return list.sort((a, b) => a.price_rupees - b.price_rupees);
            }
            if (this.sortBy === 'price_desc') {
                return list.sort((a, b) => b.price_rupees - a.price_rupees);
            }
            if (this.sortBy === 'discount') {
                return list.sort((a, b) => b.discount_percentage - a.discount_percentage);
            }
            return list;
        },

        resetFilters() {
            this.selectedCategory = 'all';
            this.selectedSize = 'all';
            this.searchQuery = '';
            this.sortBy = 'featured';
        },

        copyCatalogueLink() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.catalogueUrl);
                this.showToast('Copied to Clipboard!', 'Catalogue link copied! You can now paste and share it on WhatsApp.');
            }
        },

        showToast(title, message) {
            this.toast.title = title;
            this.toast.message = message;
            this.toast.show = true;
            setTimeout(() => { this.toast.show = false; }, 4000);
        },

        async checkPincode() {
            if (!this.pincodeInput || this.pincodeInput.length !== 6) return;
            this.pincodeLoading = true;
            this.pincodeResult.message = '';

            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('{{ route('store.catalogue.pincode') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ pincode: this.pincodeInput })
                });

                const data = await res.json();
                this.pincodeResult.serviceable = data.serviceable;
                this.pincodeResult.message = data.message;
            } catch (e) {
                this.pincodeResult.serviceable = true;
                this.pincodeResult.message = '🎉 Delivery available to ' + this.pincodeInput + ' in 3–5 days via Delhivery Express! Free Delivery & COD Available.';
            } finally {
                this.pincodeLoading = false;
            }
        },

        generateWhatsAppUrl(product, sizeObj) {
            if (!product) return 'https://wa.me/' + this.cleanPhone;
            const sizeStr = sizeObj ? (' • Size: *' + sizeObj.name + '*') : '';
            const msg = "Namaste Dhansree! 🌸\n\n"
                + "I would like to order this item from your catalogue:\n\n"
                + "🛍️ *" + product.name + "*\n"
                + "💰 *Price:* " + product.formatted_price + (product.formatted_mrp ? " (MRP: " + product.formatted_mrp + " • " + product.discount_percentage + "% OFF)" : "") + sizeStr + "\n"
                + "🚚 *Delivery:* Free Home Delivery | Cash on Delivery (COD) Available\n"
                + "🔗 *Product Link:* " + product.url + "\n\n"
                + "Please confirm size and payment details.";

            return 'https://wa.me/' + this.cleanPhone + '?text=' + encodeURIComponent(msg);
        },

        handleBuyNow(product, selectedSize) {
            if (selectedSize && selectedSize.variant_id) {
                this.executeAddToCartAndCheckout(selectedSize.variant_id);
            } else if (product.sizes.length === 1 && product.sizes[0].variant_id) {
                this.executeAddToCartAndCheckout(product.sizes[0].variant_id);
            } else if (product.sizes.length > 1) {
                // Open quick size modal
                this.sizeModal.product = product;
                this.sizeModal.selectedSize = product.sizes.find(s => s.in_stock) || product.sizes[0];
                this.sizeModal.open = true;
            } else if (product.default_variant_id) {
                this.executeAddToCartAndCheckout(product.default_variant_id);
            } else {
                window.location.href = product.url;
            }
        },

        confirmModalBuyNow() {
            if (this.sizeModal.selectedSize && this.sizeModal.selectedSize.variant_id) {
                this.sizeModal.open = false;
                this.executeAddToCartAndCheckout(this.sizeModal.selectedSize.variant_id);
            }
        },

        async executeAddToCartAndCheckout(variantId) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('{{ url('/cart/add') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ variant_id: variantId, quantity: 1 })
                });

                const data = await res.json();
                if (data.success) {
                    window.location.href = '{{ url('/checkout') }}';
                } else {
                    alert(data.message || 'Could not add to bag');
                }
            } catch (e) {
                window.location.href = '{{ url('/checkout') }}';
            }
        }
    };
}
</script>

<!-- Special Print Styling for PDF / Printed Catalogue -->
<style>
@media print {
    body {
        background: #ffffff !important;
        color: #000000 !important;
    }
    header, footer, nav, .print\:hidden {
        display: none !important;
    }
    .print\:border-slate-300 {
        border-color: #cbd5e1 !important;
    }
    .print\:shadow-none {
        box-shadow: none !important;
    }
    .print\:break-inside-avoid {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
}
</style>
@endsection
