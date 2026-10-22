@extends('layouts.store')

@section('title', 'Item No Longer Available — Dhansree')

@section('content')
<main class="min-h-[65vh] flex items-center justify-center px-4 py-16 sm:py-24 bg-warm-beige/15">
    <div class="max-w-xl w-full text-center space-y-6">
        
        <!-- Brand Badge -->
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-gold-200/80 border border-gold-400 text-maroon-900 text-xs font-bold tracking-widest uppercase">
            <span>410</span>
            <span class="text-gold-500">•</span>
            <span>Permanently Removed</span>
        </div>

        <h1 class="font-serif text-3xl sm:text-5xl font-normal text-maroon-800 leading-tight">
            This collection is no longer available
        </h1>

        <p class="text-xs sm:text-sm text-rich-black/70 max-w-md mx-auto leading-relaxed">
            The design or item you were looking for has been retired or sold out permanently. Explore our newest ethnic arrivals and latest smart living inventions.
        </p>

        <!-- Search input -->
        <div class="max-w-md mx-auto pt-2">
            <form action="{{ url('/search') }}" method="GET" class="relative flex items-center">
                <input type="text"
                       name="q"
                       placeholder="Search latest kurtas, suit sets, smart gadgets..."
                       class="w-full pl-10 pr-24 py-3 rounded-xl border border-light-gold bg-white text-xs text-rich-black placeholder-rich-black/40 focus:border-maroon-800 focus:ring-1 focus:ring-maroon-800 outline-none shadow-xs">
                <svg class="w-4 h-4 text-rich-black/40 absolute left-3.5 top-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <button type="submit"
                        class="absolute right-1.5 top-1.5 px-4 py-1.5 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shadow-2xs">
                    Search
                </button>
            </form>
        </div>

        <!-- Action CTAs -->
        <div class="flex flex-wrap items-center justify-center gap-3 pt-4">
            <a href="{{ url('/') }}"
               class="px-6 py-3 rounded-lg bg-maroon-800 text-white text-xs font-bold uppercase tracking-wider hover:bg-maroon-900 transition-all shadow-sm">
                Explore Women's Fashion
            </a>
            <a href="{{ url('/smart-living') }}"
               class="px-6 py-3 rounded-lg border border-light-gold bg-white text-rich-black text-xs font-semibold uppercase tracking-wider hover:border-maroon-800 hover:text-maroon-800 transition-all shadow-2xs">
                Smart Living Hub ➔
            </a>
        </div>

    </div>
</main>
@endsection
