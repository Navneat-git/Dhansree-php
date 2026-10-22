@extends('layouts.store')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16">
    
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-rich-black/50 mb-8">
        <a href="{{ url('/') }}" class="hover:text-maroon-800 transition-colors">Home</a>
        <span>/</span>
        <span class="text-rich-black/80 font-medium">{{ $page->title }}</span>
    </nav>

    <!-- Page Header -->
    <div class="space-y-4 mb-10 pb-8 border-b border-light-gold">
        <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-normal text-maroon-800 leading-tight">
            {{ $page->title }}
        </h1>
        
        @if($page->intro)
            <div class="p-6 rounded-2xl bg-warm-beige/40 border border-light-gold text-xs sm:text-sm text-rich-black/80 leading-relaxed space-y-3">
                {!! Str::markdown((string) ($page->intro ?? '')) !!}
            </div>
        @endif
    </div>

    <!-- Sections -->
    <div class="space-y-10">
        @foreach($page->contentPageSections as $section)
            <article class="space-y-3">
                @if($section->heading)
                    <h2 class="font-serif text-xl sm:text-2xl text-rich-black font-semibold border-b border-light-gold/40 pb-2">
                        {{ $section->heading }}
                    </h2>
                @endif
                <div class="prose prose-sm max-w-none text-rich-black/75 text-xs sm:text-sm leading-relaxed space-y-3">
                    {!! Str::markdown((string) ($section->body ?? '')) !!}
                </div>
            </article>
        @endforeach
    </div>

    <!-- Customer Support Callout -->
    <div class="mt-14 p-6 sm:p-8 rounded-2xl bg-ivory border border-light-gold flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xs">
        <div class="space-y-1 text-center sm:text-left">
            <h3 class="font-serif text-lg font-semibold text-rich-black">Have any questions or need assistance?</h3>
            <p class="text-xs text-rich-black/60">Our customer support team is available Monday through Saturday, 10 AM – 7 PM IST.</p>
        </div>
        <a href="mailto:care@dhansree.com" class="px-5 py-2.5 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shadow-xs shrink-0">
            Email Support
        </a>
    </div>

</div>
@endsection
