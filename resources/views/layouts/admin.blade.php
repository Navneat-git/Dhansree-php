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

        <!-- Mobile Slide-over Navigation Drawer -->
        <div x-show="mobileMenuOpen" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs"
                 @click="mobileMenuOpen = false"
                 aria-hidden="true"></div>

            <!-- Slide-over panel -->
            <div class="fixed inset-0 flex">
                <div x-show="mobileMenuOpen"
                     x-transition:enter="transition ease-in-out duration-300 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-300 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="relative mr-14 flex w-full max-w-xs flex-1 flex-col bg-slate-950 text-slate-200 border-r border-slate-800/80 shadow-2xl"
                     @click.stop>
                    @include('layouts.admin-nav')
                </div>
            </div>
        </div>

        <!-- Desktop Sidebar -->
        <aside class="hidden lg:block bg-slate-950 text-slate-200 border-r border-slate-800/60">
            <div class="sticky top-0 flex h-screen flex-col">
                @include('layouts.admin-nav')
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
