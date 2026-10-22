@extends('layouts.store')

@push('head')
    @php
        $allFaqs = collect();
        if ($faqGroups->isNotEmpty()) {
            foreach ($faqGroups as $g) {
                foreach ($g->faqEntries as $e) {
                    $allFaqs->push($e);
                }
            }
        }
        if (!empty($ungroupedEntries)) {
            foreach ($ungroupedEntries as $e) {
                $allFaqs->push($e);
            }
        }

        $faqSchema = null;
        if ($allFaqs->isNotEmpty()) {
            $faqSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $allFaqs->map(fn($f) => [
                    '@type' => 'Question',
                    'name' => $f->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $f->answer,
                    ],
                ])->values()->all(),
            ];
        }
    @endphp
    @if($faqSchema)
        <script type="application/ld+json">
        {!! json_encode($faqSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endif
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16">
    
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-rich-black/50 mb-8">
        <a href="{{ url('/') }}" class="hover:text-maroon-800 transition-colors">Home</a>
        <span>/</span>
        <span class="text-rich-black/80 font-medium">Frequently Asked Questions</span>
    </nav>

    <!-- Page Header -->
    <div class="space-y-4 mb-10 pb-8 border-b border-light-gold">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold-200/70 border border-gold-400 text-maroon-900 text-xs font-semibold uppercase tracking-wider">
            <span>💡</span>
            <span>Help Center &amp; Support</span>
        </div>
        <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-normal text-maroon-800 leading-tight">
            Frequently Asked Questions
        </h1>
        <p class="text-xs sm:text-sm text-rich-black/75 max-w-2xl leading-relaxed">
            Find answers to commonly asked questions regarding our handcrafted kurties, sizing, delivery timelines, COD payments, returns, and easy exchanges.
        </p>
    </div>

    <!-- Dynamic FAQ Accordion Groups -->
    @if($faqGroups->isNotEmpty())
        <div class="space-y-8 mb-12">
            @foreach($faqGroups as $grp)
                @if($grp->faqEntries->isNotEmpty())
                    <section class="space-y-4">
                        <h2 class="font-serif text-xl sm:text-2xl text-maroon-800 font-semibold border-b border-light-gold/60 pb-2">
                            {{ $grp->name }}
                        </h2>

                        <div class="space-y-3">
                            @foreach($grp->faqEntries as $entry)
                                <div class="rounded-xl border border-light-gold bg-ivory overflow-hidden transition-all shadow-2xs"
                                     x-data="{ open: false }">
                                    <button type="button"
                                            @click="open = !open"
                                            class="w-full p-4 sm:p-5 text-left flex items-center justify-between gap-4 font-medium text-xs sm:text-sm text-rich-black hover:text-maroon-800 transition-colors cursor-pointer">
                                        <span class="font-semibold">{{ $entry->question }}</span>
                                        <span class="size-6 rounded-full bg-warm-beige flex items-center justify-center shrink-0 transition-transform text-xs text-maroon-800"
                                              :class="{ 'rotate-180': open }">
                                            ▼
                                        </span>
                                    </button>
                                    <div x-show="open"
                                         x-cloak
                                         class="px-4 pb-5 sm:px-5 sm:pb-6 text-xs sm:text-sm text-rich-black/75 leading-relaxed border-t border-light-gold/40 pt-4 bg-white/50 whitespace-pre-line">
                                        {{ $entry->answer }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @endif

    <!-- Ungrouped FAQ Entries (from admin panel) -->
    @if(!empty($ungroupedEntries) && $ungroupedEntries->isNotEmpty())
        <section class="space-y-4 mb-12">
            @if($faqGroups->isNotEmpty())
                <h2 class="font-serif text-xl sm:text-2xl text-maroon-800 font-semibold border-b border-light-gold/60 pb-2">
                    General Questions
                </h2>
            @endif

            <div class="space-y-3">
                @foreach($ungroupedEntries as $entry)
                    <div class="rounded-xl border border-light-gold bg-ivory overflow-hidden transition-all shadow-2xs"
                         x-data="{ open: false }">
                        <button type="button"
                                @click="open = !open"
                                class="w-full p-4 sm:p-5 text-left flex items-center justify-between gap-4 font-medium text-xs sm:text-sm text-rich-black hover:text-maroon-800 transition-colors cursor-pointer">
                            <span class="font-semibold">{{ $entry->question }}</span>
                            <span class="size-6 rounded-full bg-warm-beige flex items-center justify-center shrink-0 transition-transform text-xs text-maroon-800"
                                  :class="{ 'rotate-180': open }">
                                ▼
                            </span>
                        </button>
                        <div x-show="open"
                             x-cloak
                             class="px-4 pb-5 sm:px-5 sm:pb-6 text-xs sm:text-sm text-rich-black/75 leading-relaxed border-t border-light-gold/40 pt-4 bg-white/50 whitespace-pre-line">
                            {{ $entry->answer }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Content Page Sections (if any) -->
    @if($page && $page->contentPageSections->isNotEmpty())
        <div class="space-y-8 mb-12">
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
    @endif

    <!-- Still Have Questions Support Card -->
    <div class="p-6 sm:p-8 rounded-2xl bg-warm-beige/40 border border-light-gold flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xs">
        <div class="space-y-1 text-center sm:text-left">
            <h3 class="font-serif text-lg font-semibold text-rich-black">Still have questions?</h3>
            <p class="text-xs text-rich-black/60">Our friendly customer support team is just an email away.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ url('/track-order') }}" class="px-4 py-2.5 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-semibold hover:bg-maroon-50 transition-colors">
                Track Your Order
            </a>
            <a href="mailto:care@dhansree.com" class="px-4 py-2.5 rounded-lg bg-maroon-800 text-white text-xs font-semibold hover:bg-maroon-900 transition-colors shadow-xs">
                Email Support
            </a>
        </div>
    </div>

</div>
@endsection
