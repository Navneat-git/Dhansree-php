<!DOCTYPE html>
<html lang="en-IN" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seoTitle ?? 'Dhansree Trends — Ethnic Elegance. Timeless You.' }}</title>
    <meta name="description" content="{{ $seoDescription ?? 'Discover premium handcrafted women ethnic wear, kurties, and modern smart living essentials at Dhansree.' }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $seoTitle ?? 'Dhansree Trends' }}">
    <meta property="og:description" content="{{ $seoDescription ?? 'Ethnic Elegance. Timeless You.' }}">
    <meta property="og:site_name" content="Dhansree">
    <meta property="og:locale" content="en_IN">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle ?? 'Dhansree Trends' }}">
    <meta name="twitter:description" content="{{ $seoDescription ?? 'Ethnic Elegance. Timeless You.' }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @stack('head')
</head>
<body class="min-h-full flex flex-col font-sans antialiased text-rich-black bg-ivory selection:bg-gold-500 selection:text-white"
      x-data="dhansreeStore()"
      x-init="initCart()">

    <!-- Announcement Bar -->
    <div class="{{ ($isSmartLivingHub ?? false) ? 'bg-tech-dark text-gold-300' : 'bg-maroon-800 text-gold-200' }} text-center text-xs py-2 px-4 font-medium tracking-wide flex items-center justify-center gap-2 border-b {{ ($isSmartLivingHub ?? false) ? 'border-tech-border' : 'border-maroon-900' }}">
        <span class="inline-block animate-pulse">✨</span>
        <span>Free Express Delivery on all orders above <strong>₹999</strong> | Cash on Delivery available across India</span>
        <span class="hidden md:inline-block text-gold-400">|</span>
        <span class="hidden md:inline-block">100% Quality Guaranteed</span>
    </div>

    <!-- Main Navigation Header -->
    <header class="sticky top-0 z-40 bg-ivory/95 backdrop-blur-md border-b border-light-gold/60 transition-shadow duration-300"
            :class="{ 'shadow-sm': scrolled }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-4">

                <!-- Left: Mobile Menu Toggle & Brand Logo -->
                <div class="flex items-center gap-3 lg:gap-8">
                    <!-- Mobile hamburger -->
                    <button type="button"
                            @click="mobileNavOpen = true"
                            class="lg:hidden p-2 -ml-2 text-rich-black hover:text-maroon-800 focus:outline-none"
                            aria-label="Open mobile menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                        </svg>
                    </button>

                    <!-- Brand Logo -->
                    <a href="{{ url('/') }}" class="flex flex-col group">
                        <span class="font-serif text-2xl sm:text-3xl font-semibold tracking-wider text-maroon-800 group-hover:text-maroon-900 transition-colors">
                            DHANSREE
                        </span>
                        <span class="text-[0.625rem] sm:text-[0.6875rem] uppercase tracking-[0.2em] -mt-1 font-medium text-gold-600">
                            {{ ($isSmartLivingHub ?? false) ? 'Smart Living & Tech' : 'Ethnic Elegance' }}
                        </span>
                    </a>
                </div>

                <!-- Center: Hub Megamenu Navigation -->
                <nav class="hidden lg:flex items-center gap-6 xl:gap-8">
                    @if(!($isSmartLivingHub ?? false))
                        <!-- Women Fashion Hub Nav Links -->
                        <a href="{{ url('/kurta-set') }}" class="text-sm font-medium hover:text-maroon-800 transition-colors {{ request()->is('kurta-set*') ? 'text-maroon-800 font-semibold' : 'text-rich-black/80' }}">
                            Kurta Sets
                        </a>
                        <a href="{{ url('/women-two-piece-sets-co-ord') }}" class="text-sm font-medium hover:text-maroon-800 transition-colors {{ request()->is('women-two-piece-sets-co-ord*') ? 'text-maroon-800 font-semibold' : 'text-rich-black/80' }}">
                            Co-ord Sets
                        </a>
                        <a href="{{ url('/women-suit-set') }}" class="text-sm font-medium hover:text-maroon-800 transition-colors {{ request()->is('women-suit-set*') ? 'text-maroon-800 font-semibold' : 'text-rich-black/80' }}">
                            Suit Sets
                        </a>
                        <a href="{{ url('/women-fashion') }}" class="text-sm font-medium hover:text-maroon-800 transition-colors {{ request()->is('women-fashion*') ? 'text-maroon-800 font-semibold' : 'text-rich-black/80' }}">
                            All Ethnic Wear
                        </a>

                        <!-- Cross-hub link to Smart Living -->
                        <a href="{{ url('/smart-living') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-gold-200/60 text-maroon-900 border border-gold-400/80 hover:bg-gold-300 hover:border-gold-500 transition-all shadow-xs">
                            <span class="text-amber-600">⚡</span>
                            <span>Smart Living &amp; Gadgets</span>
                            <span class="text-xs">➔</span>
                        </a>
                    @else
                        <!-- Smart Living Hub Nav Links -->
                        <a href="{{ url('/smart-gadgets') }}" class="text-sm font-medium hover:text-amber-800 transition-colors {{ request()->is('smart-gadgets*') ? 'text-amber-800 font-semibold' : 'text-rich-black/80' }}">
                            Smart Gadgets
                        </a>
                        <a href="{{ url('/mobile-accessories') }}" class="text-sm font-medium hover:text-amber-800 transition-colors {{ request()->is('mobile-accessories*') ? 'text-amber-800 font-semibold' : 'text-rich-black/80' }}">
                            Mobile Accessories
                        </a>
                        <a href="{{ url('/home-and-lifestyle') }}" class="text-sm font-medium hover:text-amber-800 transition-colors {{ request()->is('home-and-lifestyle*') ? 'text-amber-800 font-semibold' : 'text-rich-black/80' }}">
                            Home &amp; Lifestyle
                        </a>
                        <a href="{{ url('/car-accessories') }}" class="text-sm font-medium hover:text-amber-800 transition-colors {{ request()->is('car-accessories*') ? 'text-amber-800 font-semibold' : 'text-rich-black/80' }}">
                            Car Accessories
                        </a>

                        <!-- Cross-hub link to Women's Fashion -->
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-maroon-50 text-maroon-800 border border-maroon-200 hover:bg-maroon-100 transition-all shadow-xs">
                            <span class="text-maroon-700">🌸</span>
                            <span>Women's Ethnic Fashion</span>
                            <span class="text-xs">➔</span>
                        </a>
                    @endif
                </nav>

                <!-- Right: Utility Actions (Search, Account, Cart Drawer) -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Search Trigger -->
                    <button type="button"
                            @click="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
                            class="p-2 text-rich-black/80 hover:text-maroon-800 rounded-full hover:bg-gold-200/40 transition-colors"
                            aria-label="Search catalog">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                    </button>

                    <!-- Cart Drawer Trigger with Dynamic Badge -->
                    <button type="button"
                            @click="cartDrawerOpen = true"
                            class="relative p-2 text-rich-black/80 hover:text-maroon-800 rounded-full hover:bg-gold-200/40 transition-colors flex items-center"
                            aria-label="Shopping bag">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25c-.669 0-1.189-.578-1.119-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                        <span x-show="cart.total_quantity > 0"
                              x-cloak
                              x-text="cart.total_quantity"
                              class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-maroon-800 text-white text-[0.625rem] font-bold flex items-center justify-center ring-2 ring-ivory">
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer Navigation -->
    <div x-show="mobileNavOpen"
         x-cloak
         class="fixed inset-0 z-50 lg:hidden"
         role="dialog"
         aria-modal="true">
        <!-- Backdrop -->
        <div x-show="mobileNavOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileNavOpen = false"
             class="fixed inset-0 bg-rich-black/50 backdrop-blur-xs"></div>

        <!-- Slide-out panel -->
        <div x-show="mobileNavOpen"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="relative flex flex-col w-full max-w-xs h-full bg-ivory shadow-xl overflow-y-auto">
            
            <div class="flex items-center justify-between p-4 border-b border-light-gold/60">
                <span class="font-serif text-xl font-semibold text-maroon-800">DHANSREE</span>
                <button type="button"
                        @click="mobileNavOpen = false"
                        class="p-1 text-rich-black/60 hover:text-rich-black"
                        aria-label="Close menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Hub Switcher in Mobile Drawer -->
            <div class="p-4 bg-gold-200/30 border-b border-light-gold/60 space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-rich-black/60">Browse Category Hub</p>
                <div class="grid grid-cols-2 gap-2 text-xs font-medium">
                    <a href="{{ url('/') }}"
                       class="py-2 px-3 text-center rounded-lg border {{ !($isSmartLivingHub ?? false) ? 'bg-maroon-800 text-white border-maroon-800 font-semibold' : 'bg-white text-rich-black border-light-gold' }}">
                        🌸 Ethnic Wear
                    </a>
                    <a href="{{ url('/smart-living') }}"
                       class="py-2 px-3 text-center rounded-lg border {{ ($isSmartLivingHub ?? false) ? 'bg-tech-dark text-white border-tech-dark font-semibold' : 'bg-white text-rich-black border-light-gold' }}">
                        ⚡ Smart Tech
                    </a>
                </div>
            </div>

            <div class="flex-1 px-4 py-6 space-y-6">
                @if(!($isSmartLivingHub ?? false))
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-maroon-800 mb-3">Women's Ethnic Fashion</h3>
                        <div class="space-y-3">
                            <a href="{{ url('/kurta-set') }}" class="block text-sm text-rich-black hover:text-maroon-800 font-medium">Kurta Sets</a>
                            <a href="{{ url('/women-two-piece-sets-co-ord') }}" class="block text-sm text-rich-black hover:text-maroon-800 font-medium">Co-ord Sets</a>
                            <a href="{{ url('/women-suit-set') }}" class="block text-sm text-rich-black hover:text-maroon-800 font-medium">Suit Sets</a>
                            <a href="{{ url('/women-fashion') }}" class="block text-sm text-rich-black hover:text-maroon-800 font-medium">View All Collections</a>
                        </div>
                    </div>
                @else
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-amber-800 mb-3">Smart Living &amp; Tech</h3>
                        <div class="space-y-3">
                            <a href="{{ url('/smart-gadgets') }}" class="block text-sm text-rich-black hover:text-amber-800 font-medium">Smart Gadgets</a>
                            <a href="{{ url('/mobile-accessories') }}" class="block text-sm text-rich-black hover:text-amber-800 font-medium">Mobile Accessories</a>
                            <a href="{{ url('/home-and-lifestyle') }}" class="block text-sm text-rich-black hover:text-amber-800 font-medium">Home &amp; Lifestyle</a>
                            <a href="{{ url('/car-accessories') }}" class="block text-sm text-rich-black hover:text-amber-800 font-medium">Car Accessories</a>
                        </div>
                    </div>
                @endif

                <hr class="border-light-gold/60">

                <div class="space-y-3 text-sm text-rich-black/70 font-medium">
                    <a href="{{ url('/about') }}" class="block hover:text-maroon-800">Our Story &amp; Craft</a>
                    <a href="{{ url('/contact') }}" class="block hover:text-maroon-800">Help &amp; Contact Us</a>
                    <a href="{{ url('/faq') }}" class="block hover:text-maroon-800">FAQs</a>
                    <a href="{{ url('/shipping-policy') }}" class="block hover:text-maroon-800">Shipping Policy</a>
                    <a href="{{ url('/return-policy') }}" class="block hover:text-maroon-800">Returns &amp; Exchanges</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Search Overlay Drawer -->
    <div x-show="searchOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="searchOpen = false">
        
        <div x-show="searchOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="searchOpen = false"
             class="fixed inset-0 bg-rich-black/60 backdrop-blur-xs"></div>

        <div class="min-h-full px-4 text-center">
            <div x-show="searchOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-4"
                 class="inline-block w-full max-w-2xl my-12 text-left align-middle bg-ivory rounded-2xl shadow-2xl overflow-hidden border border-light-gold">
                
                <form action="{{ url('/search') }}" method="GET" class="relative flex items-center p-4 border-b border-light-gold">
                    <svg class="w-5 h-5 text-rich-black/40 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input type="text"
                           name="q"
                           x-ref="searchInput"
                           placeholder="Search kurties, suit sets, smart gadgets, chargers..."
                           class="w-full text-base bg-transparent border-none outline-none focus:ring-0 text-rich-black placeholder-rich-black/40">
                    <button type="button"
                            @click="searchOpen = false"
                            class="p-1 text-rich-black/40 hover:text-rich-black"
                            aria-label="Close search">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>

                <div class="p-6 bg-warm-beige/30">
                    <p class="text-xs font-semibold uppercase tracking-wider text-rich-black/60 mb-3">Popular Searches</p>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <a href="{{ url('/search?q=kurta') }}" class="px-3 py-1.5 rounded-full bg-white border border-light-gold hover:border-maroon-800 hover:text-maroon-800 transition-colors">Cotton Kurta</a>
                        <a href="{{ url('/search?q=co-ord') }}" class="px-3 py-1.5 rounded-full bg-white border border-light-gold hover:border-maroon-800 hover:text-maroon-800 transition-colors">Floral Co-ord</a>
                        <a href="{{ url('/search?q=suit') }}" class="px-3 py-1.5 rounded-full bg-white border border-light-gold hover:border-maroon-800 hover:text-maroon-800 transition-colors">Suit Set with Dupatta</a>
                        <a href="{{ url('/search?q=cable') }}" class="px-3 py-1.5 rounded-full bg-white border border-light-gold hover:border-amber-800 hover:text-amber-800 transition-colors">60W Fast Charging Cable</a>
                        <a href="{{ url('/search?q=iron') }}" class="px-3 py-1.5 rounded-full bg-white border border-light-gold hover:border-amber-800 hover:text-amber-800 transition-colors">Portable Travel Steamer</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide-out Shopping Bag Cart Drawer -->
    <div x-show="cartDrawerOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="cartDrawerOpen = false">
        
        <div x-show="cartDrawerOpen"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="cartDrawerOpen = false"
             class="fixed inset-0 bg-rich-black/50 backdrop-blur-xs transition-opacity"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="cartDrawerOpen"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-md bg-ivory shadow-2xl flex flex-col">
                
                <!-- Drawer Header -->
                <div class="p-4 sm:p-6 border-b border-light-gold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h2 class="font-serif text-xl font-semibold text-rich-black">Shopping Bag</h2>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-maroon-800/10 text-maroon-800 font-semibold"
                              x-text="'(' + cart.total_quantity + ')'"></span>
                    </div>
                    <button type="button"
                            @click="cartDrawerOpen = false"
                            class="p-2 text-rich-black/60 hover:text-rich-black rounded-lg"
                            aria-label="Close bag">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Free Shipping Progress Tracker -->
                <div class="px-4 sm:px-6 py-3 bg-warm-beige/40 border-b border-light-gold text-xs">
                    <template x-if="cart.is_free_shipping">
                        <div class="flex items-center gap-1.5 text-emerald-800 font-medium">
                            <span>🎉</span>
                            <span>You've unlocked <strong>FREE Express Shipping</strong>!</span>
                        </div>
                    </template>
                    <template x-if="!cart.is_free_shipping">
                        <div>
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-rich-black/80 font-medium">
                                    Add <strong class="text-maroon-800" x-text="'₹' + cart.remaining_for_free_shipping_rupees"></strong> more for <strong>FREE Delivery</strong>
                                </span>
                                <span class="font-semibold text-gold-600" x-text="cart.free_shipping_progress_percentage + '%'"></span>
                            </div>
                            <div class="w-full h-1.5 bg-light-gold/50 rounded-full overflow-hidden">
                                <div class="h-full bg-gold-500 rounded-full transition-all duration-300"
                                     :style="'width: ' + cart.free_shipping_progress_percentage + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Cart Line Items -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
                    <template x-if="cart.items.length === 0">
                        <div class="text-center py-12 space-y-4">
                            <div class="w-16 h-16 mx-auto rounded-full bg-gold-200/50 flex items-center justify-center text-maroon-800">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25c-.669 0-1.189-.578-1.119-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                                </svg>
                            </div>
                            <h3 class="font-serif text-lg font-medium text-rich-black">A little room for something lovely</h3>
                            <p class="text-xs text-rich-black/60 max-w-xs mx-auto">Your shopping bag is empty. Explore our latest arrivals and festive collections.</p>
                            <a href="{{ url('/') }}"
                               @click="cartDrawerOpen = false"
                               class="inline-block mt-2 px-5 py-2.5 rounded-lg bg-maroon-800 text-white text-xs font-semibold tracking-wide hover:bg-maroon-900 transition-colors shadow-xs">
                                Discover Women's Fashion
                            </a>
                        </div>
                    </template>

                    <template x-for="item in cart.items" :key="item.variant_id">
                        <div class="flex gap-4 p-3 bg-white rounded-xl border border-light-gold/80 shadow-xs relative">
                            <!-- Image -->
                            <div class="w-16 h-20 bg-warm-beige rounded-lg overflow-hidden shrink-0">
                                <img :src="item.image_url" :alt="item.product_name" class="w-full h-full object-cover">
                            </div>

                            <!-- Details -->
                            <div class="flex-1 min-w-0 flex flex-col justify-between">
                                <div>
                                    <h4 class="text-xs font-medium text-rich-black truncate" x-text="item.product_name"></h4>
                                    <p class="text-[0.6875rem] text-rich-black/60" x-text="'Size: ' + item.variant_title"></p>
                                </div>

                                <div class="flex items-center justify-between mt-2">
                                    <!-- Stepper -->
                                    <div class="flex items-center border border-light-gold rounded-md">
                                        <button type="button"
                                                @click="updateCartQty(item.variant_id, item.quantity - 1)"
                                                class="px-2 py-0.5 text-xs text-rich-black/60 hover:text-maroon-800 font-bold">−</button>
                                        <span class="px-2 text-xs font-semibold" x-text="item.quantity"></span>
                                        <button type="button"
                                                @click="updateCartQty(item.variant_id, item.quantity + 1)"
                                                class="px-2 py-0.5 text-xs text-rich-black/60 hover:text-maroon-800 font-bold">+</button>
                                    </div>

                                    <!-- Price -->
                                    <div class="text-right">
                                        <span class="text-xs font-bold text-maroon-800" x-text="'₹' + item.line_total_rupees"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete button -->
                            <button type="button"
                                    @click="removeCartItem(item.variant_id)"
                                    class="p-1 text-rich-black/30 hover:text-rose-600 transition-colors self-start"
                                    aria-label="Remove item">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Drawer Footer & Checkout CTA -->
                <div x-show="cart.items.length > 0" class="p-4 sm:p-6 bg-white border-t border-light-gold space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-rich-black/70">Subtotal</span>
                        <strong class="text-base text-rich-black" x-text="'₹' + cart.subtotal_rupees"></strong>
                    </div>
                    <div class="flex items-center justify-between text-xs text-rich-black/60">
                        <span>Shipping</span>
                        <span x-text="cart.is_free_shipping ? 'FREE' : '₹' + cart.shipping_rupees"></span>
                    </div>
                    <p class="text-[0.6875rem] text-rich-black/50 text-center">Taxes included. Discounts applied at checkout.</p>
                    
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <a href="{{ url('/cart') }}"
                           class="py-3 text-center rounded-lg border border-maroon-800 text-maroon-800 text-xs font-semibold tracking-wide hover:bg-maroon-50 transition-colors">
                            View Bag
                        </a>
                        <a href="{{ url('/cart') }}"
                           class="py-3 text-center rounded-lg bg-maroon-800 text-white text-xs font-semibold tracking-wide hover:bg-maroon-900 transition-colors shadow-sm">
                            Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5-Second Homepage Promo Newsletter Modal -->
    <div x-show="promoModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="promoModalOpen = false">
        
        <div x-show="promoModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="promoModalOpen = false"
             class="fixed inset-0 bg-rich-black/60 backdrop-blur-xs transition-opacity"></div>

        <div x-show="promoModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="relative w-full max-w-md bg-ivory rounded-2xl p-6 sm:p-8 shadow-2xl border-t-4 border-gold-500 border border-light-gold text-center overflow-hidden">
            
            <button type="button"
                    @click="promoModalOpen = false; localStorage.setItem('dhansree_modal_dismissed', '1')"
                    class="absolute top-4 right-4 p-1 text-rich-black/40 hover:text-rich-black rounded-lg"
                    aria-label="Close dialog">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Decorative envelope icon -->
            <div class="w-12 h-12 mx-auto rounded-full bg-gold-200/50 flex items-center justify-center text-maroon-800 mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                </svg>
            </div>

            <h3 class="font-serif text-2xl font-bold text-maroon-800 mb-2">Get ₹50 Off Your First Order</h3>
            <p class="text-xs text-rich-black/70 mb-5 leading-relaxed">
                Join our private VIP circle for insider drops, handpicked festive edits, and enjoy an instant <strong>₹50 discount</strong> on orders above ₹799.
            </p>

            <template x-if="newsletterSuccess">
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-xs text-left mb-4">
                    <p class="font-bold mb-1">🎉 You're on the list!</p>
                    <p class="mb-2" x-text="newsletterMessage"></p>
                    <div class="flex items-center justify-between p-2 bg-white rounded border border-emerald-300 font-mono font-bold text-maroon-800">
                        <span x-text="newsletterCode"></span>
                        <span class="text-[0.625rem] text-emerald-700 uppercase font-sans">Active</span>
                    </div>
                </div>
            </template>

            <template x-if="!newsletterSuccess">
                <form @submit.prevent="submitNewsletter" class="space-y-4 text-left">
                    <div>
                        <label for="newsletter-email" class="block text-xs font-semibold text-rich-black/80 mb-1">Email Address</label>
                        <input type="email"
                               id="newsletter-email"
                               x-model="newsletterEmail"
                               required
                               placeholder="you@domain.com"
                               class="w-full px-3.5 py-2.5 rounded-lg border border-light-gold bg-white text-xs text-rich-black placeholder-rich-black/40 focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none">
                    </div>

                    <div class="flex items-start gap-2">
                        <input type="checkbox"
                               id="newsletter-consent"
                               x-model="newsletterConsent"
                               required
                               class="mt-0.5 rounded border-light-gold text-maroon-800 focus:ring-maroon-800">
                        <label for="newsletter-consent" class="text-[0.6875rem] text-rich-black/70 leading-snug">
                            I agree to receive personalized updates and offers from Dhansree. Unsubscribe anytime.
                        </label>
                    </div>

                    <template x-if="newsletterError">
                        <p class="text-xs text-rose-600 font-medium" x-text="newsletterError"></p>
                    </template>

                    <button type="submit"
                            :disabled="newsletterSubmitting || !newsletterConsent"
                            class="w-full py-3 rounded-lg bg-maroon-800 text-white text-xs font-semibold tracking-wider uppercase hover:bg-maroon-900 disabled:opacity-50 transition-colors shadow-sm">
                        <span x-show="!newsletterSubmitting">Subscribe &amp; Get ₹50 Off</span>
                        <span x-show="newsletterSubmitting">Subscribing...</span>
                    </button>

                    <div class="text-center pt-2">
                        <button type="button"
                                @click="promoModalOpen = false; localStorage.setItem('dhansree_modal_dismissed', '1')"
                                class="text-[0.6875rem] text-rich-black/50 hover:text-rich-black underline">
                            No thanks, I'll keep browsing
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <!-- Main Content Slot -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Trust & Quality Strip -->
    <section class="border-t border-b border-light-gold/60 bg-warm-beige/30 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div class="space-y-1">
                    <div class="text-xl text-maroon-800">✨</div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">100% Authentic Quality</h4>
                    <p class="text-[0.6875rem] text-rich-black/60">Finest pure cotton &amp; certified fabrics</p>
                </div>
                <div class="space-y-1">
                    <div class="text-xl text-maroon-800">🚚</div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">Free Express Delivery</h4>
                    <p class="text-[0.6875rem] text-rich-black/60">On all prepaid orders over ₹999</p>
                </div>
                <div class="space-y-1">
                    <div class="text-xl text-maroon-800">🔄</div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">Easy 7-Day Returns</h4>
                    <p class="text-[0.6875rem] text-rich-black/60">Hassle-free replacement &amp; return</p>
                </div>
                <div class="space-y-1">
                    <div class="text-xl text-maroon-800">🛡️</div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-rich-black">Secure Checkout</h4>
                    <p class="text-[0.6875rem] text-rich-black/60">UPI, Cards, NetBanking &amp; COD</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Luxury Store Footer -->
    <footer class="bg-warm-beige text-rich-black pt-16 pb-12 border-t border-light-gold">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12 pb-12 border-b border-light-gold">
                
                <!-- Col 1: Brand Info -->
                <div class="lg:col-span-2 space-y-4">
                    <span class="font-serif text-3xl font-semibold tracking-wider text-maroon-800">DHANSREE</span>
                    <p class="text-xs text-rich-black/70 leading-relaxed max-w-sm">
                        Curating timeless ethnic elegance for women across India. From handcrafted cotton kurties and festive suit sets to smart lifestyle essentials, Dhansree brings authenticity, quality, and direct-to-consumer value.
                    </p>
                    <div class="text-xs text-rich-black/60 space-y-1">
                        <p><strong>Customer Care:</strong> care@dhansree.com</p>
                        <p><strong>Mon – Sat:</strong> 10:00 AM – 7:00 PM IST</p>
                    </div>
                </div>

                <!-- Col 2: Women Fashion Hub -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-maroon-800">Women Fashion</h4>
                    <ul class="space-y-2 text-xs text-rich-black/75">
                        <li><a href="{{ url('/kurta-set') }}" class="hover:text-maroon-800 transition-colors">Women Kurta Sets</a></li>
                        <li><a href="{{ url('/women-two-piece-sets-co-ord') }}" class="hover:text-maroon-800 transition-colors">Co-ord Sets</a></li>
                        <li><a href="{{ url('/women-suit-set') }}" class="hover:text-maroon-800 transition-colors">Suit Sets with Dupatta</a></li>
                        <li><a href="{{ url('/women-fashion') }}" class="hover:text-maroon-800 transition-colors">All Ethnic Wear</a></li>
                    </ul>
                </div>

                <!-- Col 3: Smart Living Hub -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-amber-800">Smart Living</h4>
                    <ul class="space-y-2 text-xs text-rich-black/75">
                        <li><a href="{{ url('/smart-gadgets') }}" class="hover:text-amber-800 transition-colors">Smart Tech Gadgets</a></li>
                        <li><a href="{{ url('/mobile-accessories') }}" class="hover:text-amber-800 transition-colors">Fast Cables &amp; Chargers</a></li>
                        <li><a href="{{ url('/home-and-lifestyle') }}" class="hover:text-amber-800 transition-colors">Home &amp; Lifestyle</a></li>
                        <li><a href="{{ url('/car-accessories') }}" class="hover:text-amber-800 transition-colors">Car Accessories</a></li>
                        <li><a href="{{ url('/smart-living') }}" class="text-amber-700 font-semibold hover:underline">Explore Tech Hub ➔</a></li>
                    </ul>
                </div>

                <!-- Col 4: Support & Policies -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-rich-black">Help &amp; Legal</h4>
                    <ul class="space-y-2 text-xs text-rich-black/75">
                        <li><a href="{{ url('/about') }}" class="hover:text-maroon-800 transition-colors">About Us</a></li>
                        <li><a href="{{ url('/contact') }}" class="hover:text-maroon-800 transition-colors">Contact Support</a></li>
                        <li><a href="{{ url('/shipping-policy') }}" class="hover:text-maroon-800 transition-colors">Shipping &amp; Delivery</a></li>
                        <li><a href="{{ url('/return-policy') }}" class="hover:text-maroon-800 transition-colors">Return &amp; Exchange</a></li>
                        <li><a href="{{ url('/privacy-policy') }}" class="hover:text-maroon-800 transition-colors">Privacy Policy</a></li>
                        <li><a href="{{ url('/terms') }}" class="hover:text-maroon-800 transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Sub-footer -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-[0.6875rem] text-rich-black/60">
                <p>© {{ date('Y') }} Dhansree Trends. All rights reserved. Direct-to-Consumer Retail.</p>
                <div class="flex items-center gap-3">
                    <span class="px-2 py-1 bg-white rounded border border-light-gold text-[0.625rem] font-semibold text-rich-black/70">100% Safe Payments</span>
                    <span>Razorpay • UPI • Cards • NetBanking • COD</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Alpine.js Storefront State Manager -->
    <script>
        function dhansreeStore() {
            return {
                scrolled: false,
                mobileNavOpen: false,
                searchOpen: false,
                cartDrawerOpen: false,
                promoModalOpen: false,

                // Newsletter state
                newsletterEmail: '',
                newsletterConsent: false,
                newsletterSubmitting: false,
                newsletterSuccess: false,
                newsletterMessage: '',
                newsletterCode: '',
                newsletterError: null,

                // Cart state
                cart: {
                    items: [],
                    total_quantity: 0,
                    subtotal_paise: 0,
                    subtotal_rupees: 0.0,
                    remaining_for_free_shipping_rupees: 999.0,
                    free_shipping_progress_percentage: 0,
                    is_free_shipping: false,
                    shipping_rupees: 0.0,
                    total_rupees: 0.0
                },

                initCart() {
                    this.fetchCart();

                    // Scroll listener for sticky header styling
                    window.addEventListener('scroll', () => {
                        this.scrolled = window.scrollY > 20;
                    });

                    // 5-second promo popup on homepage
                    @if(request()->is('/'))
                        setTimeout(() => {
                            if (!localStorage.getItem('dhansree_modal_dismissed')) {
                                this.promoModalOpen = true;
                            }
                        }, 5000);
                    @endif
                },

                async fetchCart() {
                    try {
                        const res = await fetch('{{ url('/cart/data') }}', { credentials: 'same-origin' });
                        if (res.ok) {
                            this.cart = await res.json();
                        }
                    } catch (e) {
                        console.error('Failed to load cart', e);
                    }
                },

                async addToCart(variantId, quantity = 1) {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const res = await fetch('{{ url('/cart/add') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ variant_id: variantId, quantity: quantity })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.cart = data.cart;
                            this.cartDrawerOpen = true;
                        } else {
                            alert(data.message || 'Unable to add item to bag.');
                        }
                    } catch (e) {
                        console.error('Add to cart failed', e);
                    }
                },

                async updateCartQty(variantId, qty) {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const res = await fetch('{{ url('/cart/update') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ variant_id: variantId, quantity: qty })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.cart = data.cart;
                        }
                    } catch (e) {
                        console.error('Update cart failed', e);
                    }
                },

                async removeCartItem(variantId) {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const res = await fetch('{{ url('/cart/remove') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ variant_id: variantId })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.cart = data.cart;
                        }
                    } catch (e) {
                        console.error('Remove item failed', e);
                    }
                },

                async submitNewsletter() {
                    this.newsletterSubmitting = true;
                    this.newsletterError = null;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const res = await fetch('{{ url('/newsletter/subscribe') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                email: this.newsletterEmail,
                                consent: this.newsletterConsent
                            })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.newsletterSuccess = true;
                            this.newsletterMessage = data.message;
                            this.newsletterCode = data.code || 'WELCOME50';
                            localStorage.setItem('dhansree_modal_dismissed', '1');
                        } else {
                            this.newsletterError = data.message || 'Please check your email address and consent.';
                        }
                    } catch (e) {
                        this.newsletterError = 'An error occurred. Please try again.';
                    } finally {
                        this.newsletterSubmitting = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
