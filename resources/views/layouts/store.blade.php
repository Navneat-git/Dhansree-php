<!DOCTYPE html>
<html lang="en-IN" class="h-full scroll-smooth overflow-x-hidden">
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

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @php
        $storeSettings = $store ?? \App\Models\StoreSettings::current();
        $siteVerification = config('services.google.site_verification') ?: ($storeSettings->seo_google_verification ?? null);
        $facebookDomainVerification = $storeSettings->facebook_domain_verification ?: (config('services.meta.domain_verification') ?: 'jaur6cwxsewazavl4qh5goy7ylgewz');
        $analyticsId = config('services.google.analytics_id') ?: ($storeSettings->analytics_google_measurement_id ?? null);
        $gtmId = config('services.google.gtm_id') ?: ($storeSettings->gtm_container_id ?? null);
        $metaPixelId = config('services.meta.pixel_id') ?: ($storeSettings->ads_meta_pixel_id ?? null);
        $topBarCouponCode = $storeSettings->featured_coupon_code ?? 'FESTIVE10';
        $topBarCoupon = \App\Models\Coupon::where('code', $topBarCouponCode)->where('is_active', true)->first();
        if ($topBarCoupon) {
            $isPercentage = ($topBarCoupon->type instanceof \App\Enums\CouponType)
                ? $topBarCoupon->type === \App\Enums\CouponType::PERCENTAGE
                : strtoupper((string) $topBarCoupon->type) === 'PERCENTAGE';
            $num = (float) $topBarCoupon->value;
            $displayVal = ($isPercentage && $num > 100) ? ($num / 100) : (($num >= 100 && !$isPercentage) ? ($num / 100) : $num);
            $formattedVal = rtrim(rtrim(number_format($displayVal, 2), '0'), '.');
            $topBarDiscount = $isPercentage ? "Extra {$formattedVal}% OFF" : "Flat ₹{$formattedVal} OFF";
        } else {
            $topBarDiscount = 'Extra 10% OFF';
        }
    @endphp

    @if($siteVerification)
        <meta name="google-site-verification" content="{{ $siteVerification }}">
    @endif

    @if($facebookDomainVerification)
        <meta name="facebook-domain-verification" content="{{ $facebookDomainVerification }}" />
    @endif

    @if($gtmId)
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
        <!-- End Google Tag Manager -->
    @endif

    @if($analyticsId)
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analyticsId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $analyticsId }}');
        </script>
    @endif

    @if($metaPixelId)
        <!-- Meta Pixel Code -->
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $metaPixelId }}');
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img height="1" width="1" style="display:none"
                 src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"/>
        </noscript>
    @endif

    <!-- Universal E-Commerce Event Tracker Bridge -->
    <script>
        window.dhansreeTrack = function(eventName, payload) {
            payload = payload || {};
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push(Object.assign({ event: eventName }, payload));

            if (typeof window.gtag === 'function') {
                window.gtag('event', eventName, payload);
            }

            if (typeof window.fbq === 'function') {
                const metaMap = {
                    'view_item': 'ViewContent',
                    'add_to_cart': 'AddToCart',
                    'begin_checkout': 'InitiateCheckout',
                    'purchase': 'Purchase'
                };
                const fbEvent = metaMap[eventName] || eventName;
                window.fbq('track', fbEvent, payload);
            }
        };
    </script>

    @stack('head')
</head>
<body class="min-h-full flex flex-col font-sans antialiased text-rich-black bg-ivory selection:bg-gold-500 selection:text-white overflow-x-hidden w-full max-w-full"
      x-data="dhansreeStore()"
      x-init="initCart()">

    @if($gtmId)
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif

    <!-- Continuous Running Announcement Bar -->
    <div class="marquee-container w-full max-w-full overflow-hidden overflow-x-hidden whitespace-nowrap {{ ($isSmartLivingHub ?? false) ? 'bg-tech-dark text-gold-300' : 'bg-maroon-800 text-gold-200' }} py-2 text-xs font-medium tracking-wide border-b {{ ($isSmartLivingHub ?? false) ? 'border-tech-border' : 'border-maroon-900' }}">
        <div class="inline-flex items-center gap-10 pr-10 animate-marquee">
            <span class="inline-flex items-center gap-1.5"><span class="text-gold-400">🏷️</span> Special Offer: <strong>{{ $topBarDiscount }}</strong> on Every Purchase | Use Code: <span class="bg-gold-500/20 px-1.5 py-0.5 rounded text-gold-300 font-bold border border-gold-400/40">{{ $topBarCouponCode }}</span></span>
            <span class="text-gold-500/50">•</span>
            <span class="inline-flex items-center gap-1.5"><span class="text-gold-400">✨</span> Free Express Delivery on all orders above <strong>₹999</strong></span>
            <span class="text-gold-500/50">•</span>
            <span class="inline-flex items-center gap-1.5">📦 Cash on Delivery (COD) Available Across India</span>
            <span class="text-gold-500/50">•</span>
            <span class="inline-flex items-center gap-1.5">🌸 100% Pure Breathable Fabrics Handcrafted by Artisans</span>
        </div>
        <div class="inline-flex items-center gap-10 pr-10 animate-marquee" aria-hidden="true">
            <span class="inline-flex items-center gap-1.5"><span class="text-gold-400">🏷️</span> Special Offer: <strong>{{ $topBarDiscount }}</strong> on Every Purchase | Use Code: <span class="bg-gold-500/20 px-1.5 py-0.5 rounded text-gold-300 font-bold border border-gold-400/40">{{ $topBarCouponCode }}</span></span>
            <span class="text-gold-500/50">•</span>
            <span class="inline-flex items-center gap-1.5"><span class="text-gold-400">✨</span> Free Express Delivery on all orders above <strong>₹999</strong></span>
            <span class="text-gold-500/50">•</span>
            <span class="inline-flex items-center gap-1.5">📦 Cash on Delivery (COD) Available Across India</span>
            <span class="text-gold-500/50">•</span>
            <span class="inline-flex items-center gap-1.5">🌸 100% Pure Breathable Fabrics Handcrafted by Artisans</span>
        </div>
    </div>

    <!-- Main Navigation Header (Two distinct rows on wide screens) -->
    <header class="sticky top-0 z-40 bg-ivory/95 backdrop-blur-md border-b border-light-gold/60 transition-shadow duration-300"
            :class="{ 'shadow-sm': scrolled }">
        
        <!-- Top Row: Brand Logo Lockup, Centered Search Bar, Account & Cart -->
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 w-full max-w-full">
            <div class="flex h-16 sm:h-20 items-center justify-between gap-1.5 sm:gap-3 lg:grid lg:grid-cols-[1fr_auto_1fr] w-full">

                <!-- Column 1: Mobile Hamburger + Official Brand Logo Lockup -->
                <div class="flex items-center gap-1.5 sm:gap-3 shrink-0 min-w-0">
                    <!-- Mobile hamburger -->
                    <button type="button"
                            @click="mobileNavOpen = true"
                            class="lg:hidden p-1.5 sm:p-2 -ml-1 text-rich-black hover:text-maroon-800 focus:outline-none shrink-0"
                            aria-label="Open mobile menu">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                        </svg>
                    </button>

                    <!-- Professional Brand Lockup -->
                    <a href="{{ url('/') }}" class="flex items-center gap-1.5 sm:gap-3 group py-1 min-w-0" aria-label="Dhansree Home">
                        <!-- High-res Monogram Emblem -->
                        <div class="relative size-8 sm:size-10 rounded-full bg-gradient-to-br from-maroon-800 to-maroon-950 p-0.5 shadow-xs ring-1 ring-gold-400/50 group-hover:ring-gold-400 transition-all flex items-center justify-center shrink-0">
                            <span class="font-serif text-lg sm:text-2xl font-bold text-gold-300 select-none tracking-tighter">d</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-serif text-lg sm:text-2xl lg:text-[1.65rem] font-bold tracking-[0.12em] sm:tracking-[0.18em] text-maroon-900 group-hover:text-maroon-800 transition-colors uppercase leading-none truncate">
                                DHANSREE
                            </span>
                            <span class="text-[0.5rem] sm:text-[0.625rem] tracking-[0.18em] sm:tracking-[0.25em] font-sans font-semibold text-gold-600 uppercase mt-0.5 leading-none truncate">
                                ETHNIC ELEGANCE
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Column 2: Centered Search Bar (Desktop) -->
                <form action="{{ url('/search') }}" method="GET" class="hidden lg:flex items-center w-[25rem] xl:w-[28rem] relative">
                    <input type="text"
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="Search kurties, suit sets, smart gadgets..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-full border border-light-gold/80 bg-white/95 text-xs text-rich-black placeholder-rich-black/45 focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none shadow-2xs transition-all">
                    <svg class="w-4 h-4 text-rich-black/45 absolute left-3.5 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </form>

                <!-- Column 3: Utility Actions (Mobile search trigger, Account, Cart Drawer) -->
                <div class="flex items-center justify-end gap-1 sm:gap-2.5 shrink-0">
                    <!-- Search Trigger (Mobile only) -->
                    <button type="button"
                            @click="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
                            class="lg:hidden p-1.5 sm:p-2 text-rich-black/80 hover:text-maroon-800 rounded-full hover:bg-gold-200/40 transition-colors"
                            aria-label="Search catalog">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                    </button>

                    <!-- Customer Account Link -->
                    <a href="{{ auth('customer')->check() ? route('storefront.account.dashboard') : route('storefront.account.login') }}"
                       class="p-1.5 sm:p-2 text-rich-black/80 hover:text-maroon-800 rounded-full hover:bg-gold-200/40 transition-colors flex items-center"
                       aria-label="{{ auth('customer')->check() ? 'My Account' : 'Sign in' }}"
                       title="{{ auth('customer')->check() ? 'My Account (' . auth('customer')->user()->name . ')' : 'Sign In' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </a>

                    <!-- Wishlist Trigger with Dynamic Badge -->
                    <a href="{{ route('storefront.wishlist.index') }}"
                       class="relative p-1.5 sm:p-2 text-rich-black/80 hover:text-rose-600 rounded-full hover:bg-gold-200/40 transition-colors flex items-center"
                       aria-label="My Wishlist"
                       title="My Wishlist">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                        <span x-show="wishlistCount > 0"
                              x-cloak
                              x-text="wishlistCount"
                              class="absolute -top-1 -right-1 w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-rose-600 text-white text-[0.5625rem] sm:text-[0.625rem] font-bold flex items-center justify-center ring-2 ring-ivory">
                        </span>
                    </a>

                    <!-- Cart Drawer Trigger with Dynamic Badge -->
                    <button type="button"
                            @click="cartDrawerOpen = true"
                            class="relative p-1.5 sm:p-2 text-rich-black/80 hover:text-maroon-800 rounded-full hover:bg-gold-200/40 transition-colors flex items-center"
                            aria-label="Shopping bag">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25c-.669 0-1.189-.578-1.119-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                        <span x-show="cart.total_quantity > 0"
                              x-cloak
                              x-text="cart.total_quantity"
                              class="absolute -top-1 -right-1 w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-maroon-800 text-white text-[0.5625rem] sm:text-[0.625rem] font-bold flex items-center justify-center ring-2 ring-ivory">
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Dedicated Desktop Category Navigation Bar -->
        <nav class="hidden lg:block border-t border-light-gold/50 bg-ivory/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-center gap-8 xl:gap-12 h-12">
                    @if(!($isSmartLivingHub ?? false))
                        <!-- Women Fashion Hub Nav Links -->
                        <a href="{{ url('/kurta-set') }}" class="text-xs uppercase tracking-wider font-semibold hover:text-maroon-800 transition-colors py-2 border-b-2 {{ request()->is('kurta-set*') ? 'border-maroon-800 text-maroon-800' : 'border-transparent text-rich-black/80 hover:border-maroon-800/40' }}">
                            Kurta Sets
                        </a>
                        <a href="{{ url('/women-two-piece-sets-co-ord') }}" class="text-xs uppercase tracking-wider font-semibold hover:text-maroon-800 transition-colors py-2 border-b-2 {{ request()->is('women-two-piece-sets-co-ord*') ? 'border-maroon-800 text-maroon-800' : 'border-transparent text-rich-black/80 hover:border-maroon-800/40' }}">
                            Co-ord Sets
                        </a>
                        <a href="{{ url('/women-suit-set') }}" class="text-xs uppercase tracking-wider font-semibold hover:text-maroon-800 transition-colors py-2 border-b-2 {{ request()->is('women-suit-set*') ? 'border-maroon-800 text-maroon-800' : 'border-transparent text-rich-black/80 hover:border-maroon-800/40' }}">
                            Suit Sets
                        </a>
                        <a href="{{ url('/women-fashion') }}" class="text-xs uppercase tracking-wider font-semibold hover:text-maroon-800 transition-colors py-2 border-b-2 {{ request()->is('women-fashion*') ? 'border-maroon-800 text-maroon-800' : 'border-transparent text-rich-black/80 hover:border-maroon-800/40' }}">
                            All Ethnic Wear
                        </a>

                        <!-- Cross-hub link to Smart Living -->
                        <a href="{{ url('/smart-living') }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gold-200/70 text-maroon-900 border border-gold-400/80 hover:bg-gold-300 hover:border-gold-500 transition-all shadow-xs ml-4">
                            <span class="text-amber-600">⚡</span>
                            <span>Smart Living &amp; Gadgets</span>
                            <span class="text-xs">➔</span>
                        </a>
                    @else
                        <!-- Smart Living Hub Nav Links -->
                        <a href="{{ url('/smart-gadgets') }}" class="text-xs uppercase tracking-wider font-semibold hover:text-amber-800 transition-colors py-2 border-b-2 {{ request()->is('smart-gadgets*') ? 'border-amber-800 text-amber-800' : 'border-transparent text-rich-black/80 hover:border-amber-800/40' }}">
                            Smart Gadgets
                        </a>
                        <a href="{{ url('/mobile-accessories') }}" class="text-xs uppercase tracking-wider font-semibold hover:text-amber-800 transition-colors py-2 border-b-2 {{ request()->is('mobile-accessories*') ? 'border-amber-800 text-amber-800' : 'border-transparent text-rich-black/80 hover:border-amber-800/40' }}">
                            Mobile Accessories
                        </a>
                        <a href="{{ url('/home-and-lifestyle') }}" class="text-xs uppercase tracking-wider font-semibold hover:text-amber-800 transition-colors py-2 border-b-2 {{ request()->is('home-and-lifestyle*') ? 'border-amber-800 text-amber-800' : 'border-transparent text-rich-black/80 hover:border-amber-800/40' }}">
                            Home &amp; Lifestyle
                        </a>
                        <a href="{{ url('/car-accessories') }}" class="text-xs uppercase tracking-wider font-semibold hover:text-amber-800 transition-colors py-2 border-b-2 {{ request()->is('car-accessories*') ? 'border-amber-800 text-amber-800' : 'border-transparent text-rich-black/80 hover:border-amber-800/40' }}">
                            Car Accessories
                        </a>

                        <!-- Cross-hub link to Women's Fashion -->
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-maroon-50 text-maroon-800 border border-maroon-200 hover:bg-maroon-100 transition-all shadow-xs ml-4">
                            <span class="text-maroon-700">🌸</span>
                            <span>Women's Ethnic Fashion</span>
                            <span class="text-xs">➔</span>
                        </a>
                    @endif
                </div>
            </div>
        </nav>
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

                <a href="{{ auth('customer')->check() ? route('storefront.account.dashboard') : route('storefront.account.login') }}"
                   class="flex items-center gap-2.5 py-2.5 px-3 rounded-xl bg-gold-200/50 border border-gold-400 text-maroon-900 text-xs font-semibold">
                    <svg class="w-4 h-4 text-maroon-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>{{ auth('customer')->check() ? 'My Account (' . (auth('customer')->user()->name) . ')' : 'Sign In / My Account' }}</span>
                </a>

                <a href="{{ route('storefront.wishlist.index') }}"
                   class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-white border border-light-gold text-rich-black text-xs font-semibold hover:border-rose-400 transition-colors">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                        <span>My Wishlist</span>
                    </span>
                    <span x-show="wishlistCount > 0" x-text="wishlistCount" class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-bold"></span>
                </a>

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

    <!-- Search Overlay Drawer -->
    <div x-show="searchOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="searchOpen = false">
        
        <!-- Backdrop -->
        <div x-show="searchOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="searchOpen = false"
             class="fixed inset-0 bg-rich-black/70"></div>

        <div class="relative z-10 min-h-full px-4 text-center flex items-start justify-center pt-16 sm:pt-24">
            <div x-show="searchOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-4"
                 @click.stop
                 class="w-full max-w-2xl text-left bg-white rounded-2xl shadow-2xl overflow-hidden border border-light-gold">
                
                <form action="{{ url('/search') }}" method="GET" class="relative flex items-center p-4 border-b border-light-gold gap-2">
                    <svg class="w-5 h-5 text-maroon-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input type="text"
                           name="q"
                           x-ref="searchInput"
                           placeholder="Search kurties, suit sets, smart gadgets, chargers..."
                           class="w-full text-sm sm:text-base bg-transparent border-none outline-none focus:ring-0 text-rich-black placeholder-rich-black/40">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shrink-0 shadow-xs">
                        Search
                    </button>
                    <button type="button"
                            @click="searchOpen = false"
                            class="p-1.5 text-rich-black/40 hover:text-rich-black shrink-0"
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
    <main class="flex-1 w-full max-w-full overflow-x-hidden">
        @yield('content')
    </main>

    <!-- Luxury Store Footer -->
    <footer class="bg-warm-beige text-rich-black pt-16 pb-12 border-t border-light-gold">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12 pb-12 border-b border-light-gold">
                
                <!-- Col 1: Brand Info -->
                <div class="lg:col-span-2 space-y-4">
                    <a href="{{ url('/') }}" class="inline-block" aria-label="Dhansree Home">
                        <img src="{{ asset('brand/logo-header.png') }}"
                             alt="Dhansree - Ethnic Elegance"
                             class="h-8 sm:h-9 w-auto object-contain">
                    </a>
                    <p class="text-xs text-rich-black/70 leading-relaxed max-w-sm">
                        Curating timeless ethnic elegance for women across India. From handcrafted cotton kurties and festive suit sets to smart lifestyle essentials, Dhansree brings authenticity, quality, and direct-to-consumer value.
                    </p>
                    <div class="text-xs text-rich-black/75 space-y-1.5 pt-1">
                        <p><strong>Customer Care:</strong> <a href="mailto:info@dhansree.com" class="hover:text-maroon-800 underline">info@dhansree.com</a></p>
                        <p>
                            <strong>WhatsApp Support:</strong>
                            <a href="https://wa.me/919887776777?text=Hi%20Dhansree%20Support"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-emerald-700 hover:text-emerald-800 font-semibold inline-flex items-center gap-1 underline ml-1">
                                <span>+91 9887776777</span>
                                <span class="text-[0.6875rem]">💬</span>
                            </a>
                        </p>
                        <p><strong>Mon – Sat:</strong> 10:00 AM – 7:00 PM IST</p>
                    </div>

                    <!-- Social Media Links -->
                    <div class="pt-2">
                        <p class="text-[0.6875rem] font-bold uppercase tracking-wider text-rich-black/60 mb-2">Follow Us</p>
                        <ul class="flex items-center gap-2" aria-label="Dhansree social media links">
                            <li>
                                <a href="https://www.instagram.com/dhansree_official" target="_blank" rel="noopener noreferrer" aria-label="Dhansree on Instagram" class="inline-flex size-8 items-center justify-center rounded-full border border-light-gold bg-white text-rich-black/70 hover:border-maroon-800 hover:text-maroon-800 transition-colors">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.2c3.2 0 3.6 0 4.9.07 1.2.05 1.8.25 2.2.42.56.22.96.48 1.38.9.42.42.68.82.9 1.38.17.4.37 1 .42 2.2.06 1.3.07 1.7.07 4.9s0 3.6-.07 4.9c-.05 1.2-.25 1.8-.42 2.2a3.7 3.7 0 0 1-.9 1.38c-.42.42-.82.68-1.38.9-.4.17-1 .37-2.2.42-1.3.06-1.7.07-4.9.07s-3.6 0-4.9-.07c-1.2-.05-1.8-.25-2.2-.42a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.17-.4-.37-1-.42-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.07-4.9c.05-1.2.25-1.8.42-2.2.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.4-.17 1-.37 2.2-.42C8.4 2.2 8.8 2.2 12 2.2Zm0 1.8c-3.14 0-3.5.01-4.74.07-.9.04-1.38.19-1.7.31-.43.17-.73.37-1.05.69-.32.32-.52.62-.69 1.05-.12.32-.27.8-.31 1.7C3.45 8.86 3.44 9.2 3.44 12s.01 3.14.07 4.38c.04.9.19 1.38.31 1.7.17.43.37.73.69 1.05.32.32.62.52 1.05.69.32.12.8.27 1.7.31 1.24.06 1.6.07 4.74.07s3.5-.01 4.74-.07c.9-.04 1.38-.19 1.7-.31.43-.17.73-.37 1.05-.69.32-.32.52-.62.69-1.05.12-.32.27-.8.31-1.7.06-1.24.07-1.58.07-4.38s-.01-3.14-.07-4.38c-.04-.9-.19-1.38-.31-1.7a2.8 2.8 0 0 0-.69-1.05 2.8 2.8 0 0 0-1.05-.69c-.32-.12-.8-.27-1.7-.31C15.5 4.01 15.14 4 12 4Zm0 3.06a4.94 4.94 0 1 1 0 9.88 4.94 4.94 0 0 1 0-9.88Zm0 1.8a3.14 3.14 0 1 0 0 6.28 3.14 3.14 0 0 0 0-6.28Zm5.14-.94a1.15 1.15 0 1 1 0-2.3 1.15 1.15 0 0 1 0 2.3Z"/></svg>
                                </a>
                            </li>
                            <li>
                                <a href="https://www.facebook.com/dhansree" target="_blank" rel="noopener noreferrer" aria-label="Dhansree on Facebook" class="inline-flex size-8 items-center justify-center rounded-full border border-light-gold bg-white text-rich-black/70 hover:border-maroon-800 hover:text-maroon-800 transition-colors">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.52 1.5-3.9 3.77-3.9 1.1 0 2.24.19 2.24.19v2.46h-1.26c-1.24 0-1.63.78-1.63 1.57v1.89h2.78l-.45 2.9h-2.33V22c4.78-.76 8.44-4.92 8.44-9.94Z"/></svg>
                                </a>
                            </li>
                            <li>
                                <a href="https://www.youtube.com/@dhansree" target="_blank" rel="noopener noreferrer" aria-label="Dhansree on YouTube" class="inline-flex size-8 items-center justify-center rounded-full border border-light-gold bg-white text-rich-black/70 hover:border-maroon-800 hover:text-maroon-800 transition-colors">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M21.58 7.19a2.5 2.5 0 0 0-1.77-1.77C18.25 5 12 5 12 5s-6.25 0-7.81.42A2.5 2.5 0 0 0 2.42 7.2C2 8.75 2 12 2 12s0 3.25.42 4.81a2.5 2.5 0 0 0 1.77 1.77C5.75 19 12 19 12 19s6.25 0 7.81-.42a2.5 2.5 0 0 0 1.77-1.77C22 15.25 22 12 22 12s0-3.25-.42-4.81ZM10 15.02V8.98L15.2 12 10 15.02Z"/></svg>
                                </a>
                            </li>
                            <li>
                                <a href="https://pinterest.com/dhansree" target="_blank" rel="noopener noreferrer" aria-label="Dhansree on Pinterest" class="inline-flex size-8 items-center justify-center rounded-full border border-light-gold bg-white text-rich-black/70 hover:border-maroon-800 hover:text-maroon-800 transition-colors">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-3.65 19.31c-.09-.78-.17-1.98.03-2.83.19-.78 1.2-4.97 1.2-4.97s-.3-.61-.3-1.52c0-1.42.82-2.48 1.85-2.48.87 0 1.3.66 1.3 1.45 0 .88-.57 2.2-.86 3.43-.24 1.02.52 1.86 1.53 1.86 1.84 0 3.25-1.94 3.25-4.73 0-2.47-1.78-4.2-4.31-4.2-2.94 0-4.66 2.2-4.66 4.48 0 .89.34 1.84.77 2.36.08.1.1.19.07.29-.08.32-.25.98-.28 1.12-.05.19-.15.23-.35.14-1.3-.6-2.11-2.5-2.11-4.03 0-3.28 2.38-6.29 6.87-6.29 3.6 0 6.41 2.57 6.41 6 0 3.58-2.26 6.47-5.4 6.47-1.05 0-2.04-.55-2.38-1.2l-.65 2.47c-.23.9-.86 2.04-1.29 2.73A10 10 0 1 0 12 2Z"/></svg>
                                </a>
                            </li>
                            <li>
                                <a href="https://x.com/dhansree" target="_blank" rel="noopener noreferrer" aria-label="Dhansree on X" class="inline-flex size-8 items-center justify-center rounded-full border border-light-gold bg-white text-rich-black/70 hover:border-maroon-800 hover:text-maroon-800 transition-colors">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17.53 3h3.04l-6.64 7.59L21.75 21h-6.12l-4.79-6.27L5.26 21H2.22l7.1-8.12L2.25 3h6.27l4.33 5.73L17.53 3Zm-1.07 16.17h1.69L7.62 4.74H5.81l10.65 14.43Z"/></svg>
                                </a>
                            </li>
                        </ul>
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
                        <li><a href="{{ url('/faq') }}" class="hover:text-maroon-800 transition-colors font-semibold text-maroon-900">Frequently Asked Questions (FAQ)</a></li>
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
            <div class="pt-8 flex flex-col md:flex-row items-center justify-between gap-6 text-[0.6875rem] text-rich-black/60">
                <p>© {{ date('Y') }} Dhansree Trends. All rights reserved. Direct-to-Consumer Retail.</p>
                <div class="flex flex-wrap items-center gap-3 sm:gap-5">
                    <span class="px-2.5 py-1 bg-white rounded-full border border-light-gold text-[0.6875rem] font-semibold text-rich-black/70 shrink-0">100% Safe Payments</span>
                    @php
                        $storeSettings = $store ?? \App\Models\StoreSettings::current();
                    @endphp
                    @if(!empty($storeSettings->payment_strip_url))
                        <div class="h-6 max-w-[20rem] shrink-0">
                            <img src="{{ $storeSettings->payment_strip_url }}" alt="Secure Payments" class="h-full w-auto object-contain">
                        </div>
                    @else
                        <ul aria-label="Ways to pay" class="flex flex-wrap items-center gap-x-4 gap-y-2">
                            <li class="flex items-center"><img src="{{ asset('payments/visa.png') }}" alt="Visa" width="46" height="15" loading="lazy" class="h-3.5 sm:h-4 w-auto object-contain"></li>
                            <li class="flex items-center"><img src="{{ asset('payments/mastercard.png') }}" alt="Mastercard" width="36" height="22" loading="lazy" class="h-4.5 sm:h-5 w-auto object-contain"></li>
                            <li class="flex items-center"><img src="{{ asset('payments/rupay.png') }}" alt="RuPay" width="56" height="15" loading="lazy" class="h-3.5 sm:h-4 w-auto object-contain"></li>
                            <li class="flex items-center"><img src="{{ asset('payments/upi.png') }}" alt="UPI" width="57" height="20" loading="lazy" class="h-4 sm:h-4.5 w-auto object-contain"></li>
                            <li class="flex items-center"><img src="{{ asset('payments/google-pay.png') }}" alt="Google Pay" width="41" height="22" loading="lazy" class="h-4.5 sm:h-5 w-auto object-contain"></li>
                            <li class="flex items-center"><img src="{{ asset('payments/phonepe.png') }}" alt="PhonePe" width="59" height="16" loading="lazy" class="h-3.5 sm:h-4 w-auto object-contain"></li>
                            <li class="flex items-center"><img src="{{ asset('payments/paytm.png') }}" alt="Paytm" width="48" height="15" loading="lazy" class="h-3.5 sm:h-4 w-auto object-contain"></li>
                            <li class="flex items-center">
                                <span class="rounded-full border border-light-gold bg-white px-2.5 py-0.5 text-[0.625rem] font-medium tracking-wide text-rich-black uppercase">
                                    Cash on delivery
                                </span>
                            </li>
                        </ul>
                    @endif
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

                // Wishlist state
                wishlist: [],
                wishlistCount: 0,

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
                    this.fetchWishlist();

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
                            if (typeof window.dhansreeTrack === 'function') {
                                window.dhansreeTrack('add_to_cart', {
                                    variant_id: variantId,
                                    quantity: quantity,
                                    currency: 'INR'
                                });
                            }
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

                async fetchWishlist() {
                    try {
                        const res = await fetch('{{ route('storefront.wishlist.ids') }}', { credentials: 'same-origin' });
                        if (res.ok) {
                            const data = await res.json();
                            this.wishlist = data.ids || [];
                            this.wishlistCount = data.count || 0;
                        }
                    } catch (e) {
                        console.error('Failed to load wishlist', e);
                    }
                },

                isInWishlist(productId) {
                    return this.wishlist.includes(Number(productId));
                },

                async toggleWishlist(productId) {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const res = await fetch('{{ route('storefront.wishlist.toggle') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ product_id: productId })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.wishlist = data.ids || [];
                            this.wishlistCount = data.count || 0;
                        }
                    } catch (e) {
                        console.error('Wishlist toggle failed', e);
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
