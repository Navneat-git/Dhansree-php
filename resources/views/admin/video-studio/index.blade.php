@extends('layouts.admin')

@section('title', 'Video Studio - Marketing')

@section('content')
<div class="space-y-6" x-data="videoStudioDashboard()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Dhansree Video Studio</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Generate high-converting promotional videos for Reels, Feed Ads, and Product Pages using existing catalog imagery.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.video-studio.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 rounded-lg shadow-sm transition-colors">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create New Video
            </a>
        </div>
    </div>

    <!-- System Status Banner -->
    @if(!$ffmpegStatus['available'])
    <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-rose-200">
        <div class="flex items-start gap-3">
            <svg class="size-5 shrink-0 text-rose-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div class="text-xs leading-relaxed">
                <p class="font-semibold text-rose-300">FFmpeg engine not detected</p>
                <p class="mt-1 text-slate-300">To render videos locally, please ensure FFmpeg is installed at <code class="bg-slate-900 px-1 py-0.5 rounded text-amber-300">/home/administrator/.local/bin/ffmpeg</code> or configure <code class="bg-slate-900 px-1 py-0.5 rounded text-amber-300">FFMPEG_PATH</code> in your <code class="text-amber-300">.env</code> file.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Metric Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span>Total Videos</span>
                <span class="p-1 rounded bg-slate-800 text-slate-300">All</span>
            </div>
            <p class="text-2xl font-bold text-white mt-2">{{ number_format($stats['total']) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Generated promo assets</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <div class="flex items-center justify-between text-xs text-emerald-400">
                <span>Completed</span>
                <span class="size-2 rounded-full bg-emerald-400"></span>
            </div>
            <p class="text-2xl font-bold text-emerald-400 mt-2">{{ number_format($stats['completed']) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Ready to download &amp; publish</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <div class="flex items-center justify-between text-xs text-blue-400">
                <span>In Progress</span>
                <span class="size-2 rounded-full bg-blue-400 animate-pulse"></span>
            </div>
            <p class="text-2xl font-bold text-blue-400 mt-2">{{ number_format($stats['processing']) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Queued or rendering</p>
        </div>

        <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <div class="flex items-center justify-between text-xs text-amber-400">
                <span>Templates</span>
                <span class="p-1 rounded bg-amber-500/10 text-amber-400 text-[10px] font-semibold">5 Active</span>
            </div>
            <p class="text-2xl font-bold text-white mt-2">{{ count($templates) }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Women's Fashion, Festive &amp; Offers</p>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
        <form method="GET" action="{{ route('admin.video-studio.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="lg:col-span-2 relative">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search by title, product name or SKU..."
                       class="w-full pl-9 pr-3 py-2 text-xs bg-slate-950 border border-slate-800 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-amber-500">
                <svg class="size-4 text-slate-500 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs bg-slate-950 border border-slate-800 rounded-lg text-slate-300 focus:outline-none focus:border-amber-500">
                    <option value="all">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <!-- Aspect Ratio Filter -->
            <div>
                <select name="aspect_ratio" onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs bg-slate-950 border border-slate-800 rounded-lg text-slate-300 focus:outline-none focus:border-amber-500">
                    <option value="all">All Aspect Ratios</option>
                    @foreach($aspectRatios as $key => $preset)
                        <option value="{{ $key }}" {{ request('aspect_ratio') === $key ? 'selected' : '' }}>
                            {{ $preset['badge'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Actions / Reset -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-3 py-2 text-xs font-medium text-slate-200 bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'aspect_ratio', 'template', 'video_type']))
                <a href="{{ route('admin.video-studio.index') }}" class="px-3 py-2 text-xs text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Video Grid -->
    @if($videos->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-800 bg-slate-900/30 p-12 text-center">
        <div class="size-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 mx-auto flex items-center justify-center">
            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </div>
        <h3 class="text-base font-semibold text-white mt-4">No Videos Generated Yet</h3>
        <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">Select an existing catalog product to generate an elegant video slideshow for Instagram Reels, Facebook Ads, or website listing.</p>
        <div class="mt-6">
            <a href="{{ route('admin.video-studio.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-slate-950 bg-amber-500 hover:bg-amber-400 rounded-lg shadow-sm transition-colors">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Your First Video
            </a>
        </div>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @foreach($videos as $video)
        <div class="group rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden flex flex-col hover:border-slate-700 transition-all shadow-sm"
             id="video-card-{{ $video->id }}"
             data-video-id="{{ $video->id }}"
             data-status="{{ $video->status }}">

            <!-- Thumbnail Poster Container -->
            <div class="relative bg-slate-950 aspect-[4/5] overflow-hidden flex items-center justify-center">
                @if($video->thumbnail_url)
                    <img src="{{ $video->thumbnail_url }}"
                         alt="{{ $video->title }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="text-slate-600 flex flex-col items-center">
                        <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span class="text-[10px] mt-1">Generating frame...</span>
                    </div>
                @endif

                <!-- Overlay Gradient -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-black/40"></div>

                <!-- Top Badges -->
                <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between gap-1 z-10">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-black/60 text-amber-300 border border-amber-500/30 backdrop-blur-sm">
                        {{ $video->aspect_ratio_label }}
                    </span>

                    <span class="video-status-badge text-[10px] font-semibold px-2 py-0.5 rounded-md backdrop-blur-sm flex items-center gap-1.5 {{ $video->status_badge['class'] }}">
                        <span class="size-1.5 rounded-full {{ $video->status_badge['dot'] }}"></span>
                        <span>{{ $video->status_badge['text'] }}</span>
                    </span>
                </div>

                <!-- Play Button / Processing Spinner -->
                @if($video->isCompleted() && $video->video_url)
                <button type="button"
                        @click="openPreview('{{ $video->video_url }}', '{{ addslashes($video->title) }}', '{{ $video->aspect_ratio }}')"
                        class="absolute inset-0 flex items-center justify-center group-hover:scale-110 transition-transform cursor-pointer">
                    <div class="size-12 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center shadow-lg shadow-amber-500/30 hover:bg-amber-400 transition-colors">
                        <svg class="size-6 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </div>
                </button>
                @elseif($video->isProcessing())
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/70 backdrop-blur-xs">
                    <div class="size-10 rounded-full border-2 border-amber-500/30 border-t-amber-500 animate-spin"></div>
                    <span class="text-xs text-amber-300 font-semibold mt-3">Rendering Video...</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">FFmpeg Slideshow Engine</span>
                </div>
                @elseif($video->isFailed())
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-slate-950/80 p-4 text-center">
                    <div class="size-10 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center mb-2">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-rose-400">Rendering Failed</span>
                    <p class="text-[10px] text-slate-400 mt-1 line-clamp-2">{{ $video->error_message ?: 'Render timed out or encountered an error.' }}</p>
                </div>
                @endif

                <!-- Duration Bottom Badge -->
                <div class="absolute bottom-2.5 left-2.5 z-10 flex items-center gap-1.5 text-[11px] text-slate-200">
                    <span class="px-1.5 py-0.5 rounded bg-black/70 backdrop-blur-sm text-slate-300 font-mono text-[10px]">
                        {{ $video->formatted_duration }}
                    </span>
                    <span class="px-1.5 py-0.5 rounded bg-black/70 backdrop-blur-sm text-slate-400 text-[10px]">
                        {{ $video->formatted_file_size }}
                    </span>
                </div>
            </div>

            <!-- Card Body / Product Info -->
            <div class="p-3.5 flex-1 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <h4 class="text-xs font-semibold text-white line-clamp-1 hover:text-amber-400 transition-colors" title="{{ $video->title }}">
                            {{ $video->title }}
                        </h4>
                    </div>

                    @if($video->product)
                    <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-400">
                        <span class="font-mono text-slate-500">SKU: {{ $video->product->sku ?: 'N/A' }}</span>
                        <span>•</span>
                        <span class="text-amber-400 font-medium">{{ $video->product->formatted_price }}</span>
                    </div>
                    @else
                    <div class="text-[11px] text-slate-500 mt-1">Standalone Brand Video</div>
                    @endif

                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-[10px] px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                            {{ $video->template_label }}
                        </span>
                        <span class="text-[10px] text-slate-500">
                            {{ $video->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        @if($video->isCompleted() && $video->video_url)
                        <button type="button"
                                @click="openPreview('{{ $video->video_url }}', '{{ addslashes($video->title) }}', '{{ $video->aspect_ratio }}')"
                                class="px-2.5 py-1 text-[11px] font-medium rounded-md bg-slate-800 hover:bg-slate-700 text-white transition-colors flex items-center gap-1">
                            <svg class="size-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            Preview
                        </button>

                        <a href="{{ route('admin.video-studio.download', $video) }}"
                           class="p-1 text-slate-400 hover:text-emerald-400 hover:bg-slate-800 rounded-md transition-colors"
                           title="Download Video">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </a>
                        @endif

                        @if($video->isFailed())
                        <form method="POST" action="{{ route('admin.video-studio.retry', $video) }}">
                            @csrf
                            <button type="submit"
                                    class="px-2.5 py-1 text-[11px] font-medium rounded-md bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 border border-amber-500/30 transition-colors flex items-center gap-1">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Retry
                            </button>
                        </form>
                        @endif
                    </div>

                    <!-- Delete Button -->
                    <form method="POST" action="{{ route('admin.video-studio.destroy', $video) }}"
                          onsubmit="return confirm('Delete this video? Original product images will remain intact.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="p-1 text-slate-500 hover:text-rose-400 hover:bg-slate-800 rounded-md transition-colors"
                                title="Delete Video">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $videos->links() }}
    </div>
    @endif

    <!-- Video Preview Modal -->
    <div x-show="previewModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md"
         @keydown.escape.window="closePreview()">
        <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl"
             @click.outside="closePreview()">

            <!-- Modal Header -->
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-800 bg-slate-900/90">
                <div class="flex items-center gap-2">
                    <span class="p-1 rounded bg-amber-500/10 text-amber-400">
                        <svg class="size-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    <h3 class="text-sm font-semibold text-white truncate max-w-xs" x-text="previewTitle"></h3>
                </div>
                <button type="button" @click="closePreview()" class="p-1 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Video Player -->
            <div class="bg-black flex items-center justify-center min-h-[360px] max-h-[75vh]">
                <video x-ref="videoPlayer"
                       controls
                       autoplay
                       playsinline
                       class="max-h-[75vh] w-auto max-w-full rounded-b-xl"
                       :src="previewUrl">
                </video>
            </div>
        </div>
    </div>
</div>

<script>
function videoStudioDashboard() {
    return {
        previewModalOpen: false,
        previewUrl: '',
        previewTitle: '',
        previewAspect: '9:16',

        init() {
            // Live poll processing cards every 4 seconds
            this.startStatusPolling();
        },

        openPreview(url, title, aspect) {
            this.previewUrl = url;
            this.previewTitle = title;
            this.previewAspect = aspect;
            this.previewModalOpen = true;
            this.$nextTick(() => {
                if (this.$refs.videoPlayer) {
                    this.$refs.videoPlayer.play().catch(() => {});
                }
            });
        },

        closePreview() {
            if (this.$refs.videoPlayer) {
                this.$refs.videoPlayer.pause();
            }
            this.previewModalOpen = false;
            this.previewUrl = '';
        },

        startStatusPolling() {
            const cards = document.querySelectorAll('[data-status="queued"], [data-status="processing"]');
            if (!cards.length) return;

            const interval = setInterval(() => {
                const activeCards = document.querySelectorAll('[data-status="queued"], [data-status="processing"]');
                if (!activeCards.length) {
                    clearInterval(interval);
                    return;
                }

                activeCards.forEach(card => {
                    const videoId = card.getAttribute('data-video-id');
                    if (!videoId) return;

                    fetch(`/admin/video-studio/${videoId}/status`, {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.is_completed || data.is_failed) {
                            // Reload to update full preview, buttons & thumbnails
                            window.location.reload();
                        }
                    })
                    .catch(() => {});
                });
            }, 4000);
        }
    };
}
</script>
@endsection
