@extends('layouts.admin')

@section('title', 'Reel ' . $reel->reel_code)
@section('page_title', 'Reel: ' . $reel->title)

@section('header_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.social-commerce.reels.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs transition-colors">
        &larr; All Reels
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Top Status Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="font-mono text-xs font-bold text-slate-400">{{ $reel->reel_code }}</span>
                @php
                    $badgeClass = match($reel->status) {
                        'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'COMPLETED' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'RENDERING' => 'bg-purple-50 text-purple-700 border-purple-200',
                        'FAILED' => 'bg-rose-50 text-rose-700 border-rose-200',
                        default => 'bg-amber-50 text-amber-700 border-amber-200',
                    };
                @endphp
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border {{ $badgeClass }}">
                    {{ $reel->status }}
                </span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 mt-1">{{ $reel->title }}</h2>
            <p class="text-xs text-slate-500">
                Template: <span class="font-semibold text-slate-800">{{ str_replace('_', ' ', $reel->template_code) }}</span>
                &bull; Duration: {{ $reel->duration_seconds }}s
                &bull; Format: 9:16 (1080&times;1920)
                &bull; Created {{ $reel->created_at->format('d M Y, H:i') }}
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            @if($reel->status === 'RENDERING')
                <div class="px-4 py-2 rounded-xl bg-purple-100 text-purple-800 text-xs font-bold flex items-center gap-2">
                    <svg class="animate-spin size-4 text-purple-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Rendering Video ({{ $reel->progress }}%)...
                </div>
            @else
                <form method="POST" action="{{ route('admin.social-commerce.reels.render', $reel) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        {{ $reel->video_path ? 'Re-render Video (FFmpeg)' : 'Generate Video (FFmpeg)' }}
                    </button>
                </form>
            @endif

            @if($reel->status === 'DRAFT' || $reel->status === 'COMPLETED')
                <form method="POST" action="{{ route('admin.social-commerce.reels.approve', $reel) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Approve Reel for Publishing
                    </button>
                </form>
            @endif

            @if($reel->status === 'APPROVED' || $reel->status === 'COMPLETED')
                <button type="button" @click="$dispatch('open-modal', 'queue-modal')"
                        class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Send to Publishing Queue
                </button>
            @endif

            @if($reel->video_url)
                <a href="{{ $reel->video_url }}" download="{{ $reel->reel_code }}.mp4"
                   class="px-3 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download MP4
                </a>
            @endif
        </div>
    </div>

    @if($reel->error_message)
    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800">
        <p class="font-bold">Rendering Error:</p>
        <p class="mt-0.5">{{ $reel->error_message }}</p>
    </div>
    @endif

    <!-- Storyboard Slides & Captions Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Slides Storyboard -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2 mb-4">
                    Reel Storyboard Slides ({{ $reel->items->count() }})
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($reel->items as $idx => $item)
                    <div class="relative bg-slate-900 text-white rounded-2xl overflow-hidden border border-slate-800 aspect-[9/16] p-3 flex flex-col justify-between">
                        <!-- Top header on slide -->
                        <div class="flex items-center justify-between z-10">
                            <span class="size-6 rounded-md bg-amber-500 text-slate-950 font-bold text-xs flex items-center justify-center">
                                {{ $idx + 1 }}
                            </span>
                            <span class="text-[9px] font-mono bg-black/60 px-1.5 py-0.5 rounded text-white">
                                {{ $item->display_seconds }}s
                            </span>
                        </div>

                        <!-- Image -->
                        <img src="{{ $item->mediaAsset->url }}" alt="Slide {{ $idx + 1 }}" class="absolute inset-0 w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-black/30 pointer-events-none"></div>

                        <!-- Bottom Slide Overlay Text -->
                        <div class="relative z-10 space-y-1">
                            @if($item->headline)
                                <p class="text-xs font-bold text-white line-clamp-2 leading-tight drop-shadow-md">
                                    {{ $item->headline }}
                                </p>
                            @endif
                            @if($item->price_text)
                                <span class="inline-block px-2 py-0.5 rounded bg-amber-500 text-slate-950 font-bold text-[10px]">
                                    {{ $item->price_text }}
                                </span>
                            @endif
                            @if($item->socialProduct)
                                <span class="font-mono text-[9px] text-slate-300 block">
                                    {{ $item->socialProduct->product_code }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Captions, Audio & Meta Queue Modal -->
        <div class="space-y-6">

            @if($reel->video_url)
            <!-- Rendered Video Player -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-semibold text-slate-900">Generated Video Preview</h3>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                        1080 &times; 1920 MP4
                    </span>
                </div>
                <div class="relative bg-slate-950 rounded-xl overflow-hidden aspect-[9/16] max-w-[260px] mx-auto shadow-inner border border-slate-800">
                    <video src="{{ $reel->video_url }}" controls poster="{{ $reel->thumbnail_url }}" class="w-full h-full object-contain">
                        Your browser does not support HTML5 video.
                    </video>
                </div>
                <div class="text-center pt-1">
                    <a href="{{ $reel->video_url }}" download="{{ $reel->reel_code }}.mp4" class="text-xs font-bold text-amber-600 hover:text-amber-700 inline-flex items-center gap-1">
                        &darr; Download Final MP4
                    </a>
                </div>
            </div>
            @endif

            <!-- Caption Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    Captions &amp; Hashtags
                </h3>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 whitespace-pre-line leading-relaxed">
                    {{ $reel->caption ?: 'No caption specified.' }}
                </div>

                @if($reel->hashtags)
                <div class="pt-2 text-xs font-semibold text-amber-700">
                    {{ $reel->hashtags }}
                </div>
                @endif
            </div>

            <!-- Audio & Settings -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    Audio &amp; Specs
                </h3>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Audio Track:</span>
                        <span class="font-semibold text-slate-900">{{ str_replace('_', ' ', $reel->audio_track ?? 'Default') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Aspect Ratio:</span>
                        <span class="font-semibold text-slate-900">9:16 (1080 &times; 1920)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Codec:</span>
                        <span class="font-semibold text-slate-900">H.264 MP4</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Created By:</span>
                        <span class="font-semibold text-slate-900">{{ $reel->creator?->name ?? 'Admin' }}</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Push to Queue Modal -->
    <div x-data="{ open: false }"
         x-show="open"
         x-on:open-modal.window="if ($event.detail === 'queue-modal') open = true"
         x-on:keydown.escape.window="open = false"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="open = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-semibold text-slate-900">Push Reel to Publishing Queue</h3>
                    <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.social-commerce.reels.push-to-queue', $reel) }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Publish To Platforms *</label>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 text-xs font-semibold cursor-pointer">
                                <input type="checkbox" name="platforms[]" value="INSTAGRAM" checked class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <span>Instagram Reels</span>
                            </label>
                            <label class="flex items-center gap-2 text-xs font-semibold cursor-pointer">
                                <input type="checkbox" name="platforms[]" value="FACEBOOK" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span>Facebook Reels</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Schedule Publish Date &amp; Time (Optional)</label>
                        <input type="datetime-local" name="scheduled_at"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <p class="text-[10px] text-slate-400 mt-1">Leave blank to queue for immediate publishing after approval.</p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="open = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-xs font-semibold text-white hover:bg-blue-500 shadow-xs">
                            Queue for Publishing
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
