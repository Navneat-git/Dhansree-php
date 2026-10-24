<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') | Dhansree Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-800" x-data="{ mobileMenuOpen: false }">
    @php
        /** @var \App\Models\AdminUser|null $admin */
        $admin = auth('admin')->user();
        $isSuperAdmin = $admin ? $admin->hasSuperAdmin() : false;
    @endphp

    <div class="min-h-screen lg:grid lg:grid-cols-[16.5rem_minmax(0,1fr)]">
        <!-- Skip link -->
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 z-50 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
            Skip to content
        </a>

        <!-- Desktop Sidebar -->
        <aside class="hidden lg:block bg-slate-950 text-slate-200 border-r border-slate-800/60">
            <div class="sticky top-0 flex h-screen flex-col">
                <!-- Brand header -->
                <div class="flex h-16 items-center justify-between px-6 border-b border-slate-800/80">
                    <a href="{{ url('/admin/dashboard') }}" class="flex items-center gap-2">
                        <span class="text-xl font-bold tracking-tight text-white flex items-center gap-1.5">
                            <span class="text-amber-500">Dhansree</span>
                        </span>
                    </a>
                    <span class="rounded-full bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 text-[0.625rem] font-semibold tracking-wider text-amber-400 uppercase">
                        Admin
                    </span>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-6 scrollbar-thin scrollbar-thumb-slate-800">
                    <!-- Dashboard -->
                    <div>
                        <a href="{{ url('/admin/dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/dashboard') ? 'bg-amber-500 text-slate-950 font-semibold' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                            <svg class="size-4.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            Dashboard
                        </a>
                    </div>

                    <!-- Store Section -->
                    @if($isSuperAdmin || ($admin && ($admin->hasPermission('PRODUCT_VIEW') || $admin->hasPermission('ORDER_VIEW') || $admin->hasPermission('INVENTORY_VIEW') || $admin->hasPermission('CUSTOMER_VIEW'))))
                    <div>
                        <p class="px-3 text-[0.6875rem] font-semibold tracking-wider text-slate-400 uppercase mb-2">Store</p>
                        <div class="space-y-1">
                            @if($isSuperAdmin || $admin->hasPermission('PRODUCT_VIEW'))
                            <a href="{{ url('/admin/catalog/products') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/catalog/products*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                </svg>
                                Products
                            </a>
                            <a href="{{ url('/admin/catalog/categories') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/catalog/categories*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                </svg>
                                Categories
                            </a>
                            <a href="{{ url('/admin/catalog/collections') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/catalog/collections*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                </svg>
                                Collections
                            </a>
                            <a href="{{ url('/admin/catalog/wishlists') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/catalog/wishlists*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                                </svg>
                                Wishlists
                            </a>
                            <a href="{{ url('/admin/marketplace') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/marketplace*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.614A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72m-13.5 8.65h.01m0 0h.01" />
                                </svg>
                                Marketplace Feeds
                            </a>
                            <a href="{{ url('/admin/wholesale') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/wholesale*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                Wholesale Purchases
                            </a>
                            @endif

                            @if($isSuperAdmin || $admin->hasPermission('ORDER_VIEW'))
                            <a href="{{ url('/admin/orders') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/orders*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                                Orders
                            </a>
                            <a href="{{ url('/admin/shipments') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/shipments*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.375c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75" />
                                </svg>
                                Shipments
                            </a>
                            <a href="{{ url('/admin/payments') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/payments*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                                </svg>
                                Payments
                            </a>
                            @endif

                            @if($isSuperAdmin || $admin->hasPermission('INVENTORY_VIEW'))
                            <a href="{{ url('/admin/inventory') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/inventory*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                                </svg>
                                Inventory
                            </a>
                            @endif

                            @if($isSuperAdmin || $admin->hasPermission('CUSTOMER_VIEW'))
                            <a href="{{ url('/admin/customers') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/customers*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                Customers
                            </a>
                            <a href="{{ url('/admin/analytics/journeys') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/analytics/journeys*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                </svg>
                                <span>Customer Journeys</span>
                                <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">GA4</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Growth Section -->
                    @if($isSuperAdmin || ($admin && ($admin->hasPermission('COUPON_VIEW') || $admin->hasPermission('BANNER_VIEW') || $admin->hasPermission('REVIEW_VIEW') || $admin->hasPermission('SEO_VIEW'))))
                    <div>
                        <p class="px-3 text-[0.6875rem] font-semibold tracking-wider text-slate-400 uppercase mb-2">Growth</p>
                        <div class="space-y-1">
                            @if($isSuperAdmin || $admin->hasPermission('COUPON_VIEW'))
                            <a href="{{ url('/admin/coupons') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/coupons*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                                </svg>
                                Coupons
                            </a>
                            @endif

                            @if($isSuperAdmin || $admin->hasPermission('BANNER_VIEW'))
                            <a href="{{ url('/admin/banners') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/banners*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                                Banners
                            </a>
                            <a href="{{ url('/admin/homepage') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/homepage*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                </svg>
                                Home Page Sections
                            </a>
                            @endif

                            @if($isSuperAdmin || $admin->hasPermission('REVIEW_VIEW'))
                            <a href="{{ url('/admin/reviews') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/reviews*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                </svg>
                                Reviews
                            </a>
                            @endif

                            @if($isSuperAdmin || $admin->hasPermission('SEO_VIEW'))
                            <a href="{{ url('/admin/seo') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/seo*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                                SEO & Redirects
                            </a>
                            @endif

                            <a href="{{ url('/admin/faqs') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/faqs*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                                </svg>
                                FAQs
                            </a>

                            <a href="{{ url('/admin/quick-links') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/quick-links*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                </svg>
                                Quick Links
                            </a>

                            @if($isSuperAdmin || $admin->hasPermission('CUSTOMER_VIEW'))
                            <a href="{{ url('/admin/subscribers') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/subscribers*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                Subscribers
                            </a>
                            <a href="{{ url('/admin/analytics/journeys') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/analytics/journeys*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                </svg>
                                Customer Journeys
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Settings & Administration -->
                    @if($isSuperAdmin || ($admin && ($admin->hasPermission('SETTINGS_VIEW') || $admin->hasPermission('ADMIN_USER_VIEW') || $admin->hasPermission('AUDIT_LOG_VIEW'))))
                    <div>
                        <p class="px-3 text-[0.6875rem] font-semibold tracking-wider text-slate-400 uppercase mb-2">Settings & Security</p>
                        <div class="space-y-1">
                            @if($isSuperAdmin || $admin->hasPermission('SETTINGS_VIEW'))
                            <a href="{{ url('/admin/settings') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/settings*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Store Settings
                            </a>
                            <a href="{{ url('/admin/emails') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/emails*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                                Email Templates
                            </a>
                            @endif

                            @if($isSuperAdmin || $admin->hasPermission('ADMIN_USER_VIEW'))
                            <a href="{{ url('/admin/users') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/users*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                                </svg>
                                Admin Users & Roles
                            </a>
                            @endif

                            @if($isSuperAdmin || $admin->hasPermission('AUDIT_LOG_VIEW'))
                            <a href="{{ url('/admin/audit-logs') }}"
                               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/audit-logs*') ? 'bg-slate-800 text-white font-semibold border-l-2 border-amber-500' : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}">
                                <svg class="size-4.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                Audit Logs
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </nav>

                <!-- Storefront quick link footer -->
                <div class="p-4 border-t border-slate-800/80 bg-slate-950/50">
                    <a href="{{ url('/') }}" target="_blank" class="flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-900 rounded-lg transition-colors">
                        <span>View Storefront</span>
                        <svg class="size-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Column -->
        <div class="flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-4 backdrop-blur-md sm:px-6 lg:px-8">
                <!-- Mobile burger button -->
                <button type="button" @click="mobileMenuOpen = true" class="lg:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100">
                    <span class="sr-only">Open navigation</span>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Page heading / breadcrumbs -->
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-semibold text-slate-900">@yield('page_title', 'Dashboard')</h1>
                </div>

                <!-- Account dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center gap-3 p-1.5 rounded-full hover:bg-slate-100 transition-colors">
                        <span class="size-8 rounded-full bg-slate-900 text-amber-400 font-bold text-xs flex items-center justify-center ring-2 ring-amber-500/30">
                            {{ substr($admin->name ?? 'A', 0, 1) }}
                        </span>
                        <div class="hidden sm:block text-left">
                            <p class="text-xs font-semibold text-slate-800 leading-tight">{{ $admin->name ?? 'Administrator' }}</p>
                            <p class="text-[0.6875rem] text-slate-500 leading-tight">{{ $admin->email ?? '' }}</p>
                        </div>
                        <svg class="size-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <!-- Dropdown panel -->
                    <div x-show="open" @click.away="open = false" x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 rounded-xl bg-white shadow-xl ring-1 ring-slate-900/5 py-1 z-50 divide-y divide-slate-100">
                        <div class="px-4 py-3">
                            <p class="text-xs text-slate-500">Signed in as</p>
                            <p class="text-sm font-semibold text-slate-900 truncate">{{ $admin->email ?? '' }}</p>
                            <div class="mt-1 flex items-center gap-1.5">
                                <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                    {{ $isSuperAdmin ? 'Super Admin' : 'Staff' }}
                                </span>
                            </div>
                        </div>

                        <div class="py-1">
                            <a href="{{ url('/admin/change-password') }}" class="flex items-center gap-2 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">
                                <svg class="size-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                                </svg>
                                Change Password
                            </a>
                        </div>

                        <div class="py-1">
                            <form method="POST" action="{{ url('/admin/logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-xs text-rose-700 hover:bg-rose-50 text-left font-medium">
                                    <svg class="size-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash alerts -->
            @if(session('success'))
            <div class="mx-4 sm:mx-6 lg:mx-8 mt-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center justify-between" x-data="{ show: true }" x-show="show">
                <div class="flex items-center gap-2.5">
                    <svg class="size-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-900">&times;</button>
            </div>
            @endif

            @if(session('error') || $errors->any())
            <div class="mx-4 sm:mx-6 lg:mx-8 mt-4 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800 flex items-center justify-between" x-data="{ show: true }" x-show="show">
                <div class="flex items-center gap-2.5">
                    <svg class="size-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span>{{ session('error') ?? $errors->first() }}</span>
                </div>
                <button type="button" @click="show = false" class="text-rose-600 hover:text-rose-900">&times;</button>
            </div>
            @endif

            <!-- Main Page Content -->
            <main id="main-content" class="flex-1 p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
