@if(!empty($quickLinksData) && !empty($quickLinksData['links']) && count($quickLinksData['links']) > 0)
    <section class="w-full max-w-full overflow-hidden py-3 px-3 sm:px-6 lg:px-8 border-y border-amber-100/70 bg-gradient-to-r from-amber-50/50 via-white to-amber-50/50">
        <div class="max-w-7xl mx-auto flex items-center gap-3 overflow-x-auto scrollbar-none py-0.5 w-full min-w-0">
            @if(!empty($quickLinksData['title']))
                <div class="text-[11px] font-bold text-slate-800 tracking-wider uppercase whitespace-nowrap shrink-0 flex items-center gap-1.5 pl-1">
                    <svg class="size-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                    </svg>
                    <span>{{ $quickLinksData['title'] }}</span>
                </div>
                <div class="h-4 w-px bg-slate-200 shrink-0 hidden sm:block"></div>
            @endif
            <div class="flex items-center gap-2 shrink-0">
                @foreach($quickLinksData['links'] as $link)
                    <a href="{{ url($link->path ?? '#') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:text-amber-800 hover:border-amber-400 hover:bg-amber-50/70 shadow-2xs transition-all whitespace-nowrap group">
                        <span>{{ $link->label }}</span>
                        <svg class="size-3 text-slate-400 group-hover:text-amber-600 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
