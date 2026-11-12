@extends('layouts.admin')

@section('title', 'Reel Studio')
@section('page_title', 'Reel Studio (Instagram & Facebook)')

@section('header_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.social-commerce.reels.create') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 text-slate-950 hover:bg-amber-400 shadow-xs transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Compose New Reel
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Template Showcase Ribbon -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">7 Fashion Reel Templates</h3>
                <p class="text-xs text-slate-500">Optimized for 9:16 Instagram &amp; Facebook Reels (1080&times;1920)</p>
            </div>
            <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200">
                Women's Fashion Edition
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
            @foreach($templates as $tpl)
            <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-amber-400 transition-all text-center flex flex-col justify-between">
                <div>
                    <span class="inline-block size-7 rounded-lg bg-slate-900 text-amber-400 font-bold text-xs leading-7 mx-auto mb-1.5">
                        {{ substr($tpl->name, 0, 1) }}
                    </span>
                    <h4 class="text-xs font-bold text-slate-900 leading-tight">{{ $tpl->name }}</h4>
                    <p class="text-[10px] text-slate-500 mt-1 line-clamp-2">{{ $tpl->tagline }}</p>
                </div>
                <span class="text-[9px] font-mono text-slate-400 mt-2 block">{{ $tpl->default_duration_seconds }}s &bull; {{ $tpl->default_transition }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Reels List Card -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        @if($reels->isEmpty())
        <div class="p-12 text-center text-slate-500">
            <div class="size-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-3">
                <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-900">No promotional reels created yet</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                Create engaging 9:16 reels for Instagram &amp; Facebook using supplier images, templates, and background audio.
            </p>
            <div class="mt-4">
                <a href="{{ route('admin.social-commerce.reels.create') }}"
                   class="px-4 py-2 rounded-xl bg-amber-500 text-slate-950 text-xs font-bold hover:bg-amber-400 shadow-xs inline-flex items-center gap-1.5">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Compose Your First Reel
                </a>
            </div>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Reel</th>
                        <th class="py-3 px-4">Template</th>
                        <th class="py-3 px-4">Slides / Duration</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Created Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($reels as $reel)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-lg bg-slate-900 text-amber-400 flex items-center justify-center shrink-0">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <div>
                                    <a href="{{ route('admin.social-commerce.reels.show', $reel) }}" class="font-bold text-slate-900 hover:text-amber-600 block">
                                        {{ $reel->title }}
                                    </a>
                                    <span class="font-mono text-[10px] text-slate-400">{{ $reel->reel_code }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-semibold text-slate-900">{{ str_replace('_', ' ', $reel->template_code) }}</span>
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-medium text-slate-800">{{ $reel->items->count() }} media slides</span>
                            <span class="text-[10px] text-slate-400 block">{{ $reel->duration_seconds }}s total &bull; 9:16</span>
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap">
                            @php
                                $badgeClass = match($reel->status) {
                                    'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'COMPLETED' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'RENDERING' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'FAILED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                {{ $reel->status }}
                            </span>
                            @if($reel->status === 'APPROVED')
                                <span class="text-[9px] text-emerald-600 block mt-0.5">Ready to Queue</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 whitespace-nowrap text-slate-500">
                            {{ $reel->created_at->format('d M Y, H:i') }}
                        </td>

                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.social-commerce.reels.show', $reel) }}"
                               class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition-colors">
                                Review &amp; Approve
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $reels->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
