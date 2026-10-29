@extends('layouts.admin')

@section('page_title', 'Video Studio')
@section('title', 'Video Studio - Marketing | Dhansree Admin')

@section('content')
<div class="space-y-6" x-data="videoStudioDashboard()">

    <!-- Page Header Block -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center size-9 rounded-xl bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 text-white shadow-xs">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
                <h1 class="text-xl font-bold text-slate-900">Dhansree Video Studio</h1>
                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800 border border-amber-200">
                    Reels &amp; Ads
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Turn your existing catalog product photos into promotional videos for Instagram Reels, Facebook Ads, and Website listings.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.video-studio.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-lg bg-gradient-to-r from-rose-600 to-amber-600 text-white hover:from-rose-700 hover:to-amber-700 transition-all shadow-sm">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Create New Video</span>
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 text-xs flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2.5">
            <svg class="size-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 text-xs flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2.5">
            <svg class="size-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    </div>
    @endif

    <!-- Database Migration Notice -->
    @if($tableMissing ?? false)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-3">
                <svg class="size-5 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" />
                </svg>
                <div class="text-xs leading-relaxed">
                    <p class="font-bold text-amber-900">Database Table Pending Initialization</p>
                    <p class="mt-0.5 text-amber-800">The <code class="bg-amber-100 px-1.5 py-0.5 rounded font-mono text-amber-900">product_videos</code> table needs to be set up before saving videos.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.video-studio.migrate-db') }}">
                @csrf
                <button type="submit" class="shrink-0 px-4 py-2 text-xs font-semibold bg-amber-600 hover:bg-amber-700 text-white rounded-lg shadow-xs transition-colors">
                    Initialize Database Table
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- FFmpeg Engine Notice for AWS Lightsail -->
    @if(!$ffmpegStatus['available'])
    <div class="rounded-xl border border-rose-200 bg-rose-50/80 p-5 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div class="flex items-start gap-3.5">
                <div class="p-2 rounded-lg bg-rose-100 text-rose-700 shrink-0">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <div class="text-xs space-y-1.5 text-slate-700">
                    <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        FFmpeg Engine Not Detected
                        <span class="text-[11px] font-normal text-rose-700 bg-rose-100 px-2 py-0.5 rounded-full">Required for Video Rendering</span>
                    </h3>
                    <p class="text-slate-600 leading-relaxed">
                        To render videos directly on your <strong>AWS Lightsail</strong> server, connect to your instance via SSH and run this single command:
                    </p>
                    <div class="bg-slate-900 text-slate-100 font-mono text-[11px] px-3 py-2 rounded-lg flex items-center justify-between gap-3 my-2">
                        <code>sudo apt update &amp;&amp; sudo apt install -y ffmpeg</code>
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Once installed, FFmpeg is automatically detected at <code class="bg-slate-100 text-slate-700 px-1 py-0.5 rounded font-mono">/usr/bin/ffmpeg</code>. No server reboot is required.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-3.5 shadow-xs flex items-center justify-between text-xs">
        <div class="flex items-center gap-2 text-emerald-900 font-medium">
            <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>FFmpeg Rendering Engine Active:</span>
            <code class="font-mono text-emerald-800 text-[11px] bg-emerald-100 px-1.5 py-0.5 rounded">{{ $ffmpegStatus['version'] ?? 'Ready' }}</code>
        </div>
        <span class="text-[11px] text-emerald-700">Ready to render slideshow videos</span>
    </div>
    @endif

    <!-- Metric Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between text-xs font-medium text-slate-500 uppercase tracking-wider">
                <span>Total Videos</span>
                <span class="p-1 rounded bg-slate-100 text-slate-600 text-xs font-semibold">All</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($stats['total']) }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Generated marketing videos</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between text-xs font-medium text-emerald-600 uppercase tracking-wider">
                <span>Completed</span>
                <span class="size-2 rounded-full bg-emerald-500"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($stats['completed']) }}</p>
            <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Ready to download &amp; publish</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between text-xs font-medium text-blue-600 uppercase tracking-wider">
                <span>In Progress</span>
                <span class="size-2 rounded-full bg-blue-500 animate-pulse"></span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($stats['processing']) }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Queued or rendering</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between text-xs font-medium text-amber-700 uppercase tracking-wider">
                <span>Templates</span>
                <span class="rounded bg-amber-50 text-amber-800 border border-amber-200 px-1.5 py-0.5 text-[10px] font-bold">5 Active</span>
            </div>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ count($templates) }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Festive, Showcase, Offers &amp; More</p>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.video-studio.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="lg:col-span-2 relative">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search by title, product name or SKU..."
                       class="w-full pl-9 pr-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                <svg class="size-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-800 focus:outline-none focus:border-amber-500">
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
                        class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-800 focus:outline-none focus:border-amber-500">
                    <option value="all">All Formats</option>
                    @foreach($aspectRatios as $key => $preset)
                        <option value="{{ $key }}" {{ request('aspect_ratio') === $key ? 'selected' : '' }}>
                            {{ $preset['badge'] }} ({{ $preset['label'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Actions / Reset -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-3 py-2 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'aspect_ratio', 'template', 'video_type']))
                <a href="{{ route('admin.video-studio.index') }}" class="px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Video Grid -->
    @if($videos->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-xs">
        <div class="size-16 rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 mx-auto flex items-center justify-center">
            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </div>
        <h3 class="text-base font-bold text-slate-900 mt-4">No Videos Generated Yet</h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 leading-relaxed">
            Select any existing product from your catalog to automatically generate promotional videos for Instagram Reels, Facebook Ads, or your website gallery.
        </p>
        <div class="mt-6">
            <a href="{{ route('admin.video-studio.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold rounded-lg bg-gradient-to-r from-rose-600 to-amber-600 text-white hover:from-rose-700 hover:to-amber-700 transition-all shadow-sm">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Create Your First Video</span>
            </a>
        </div>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @foreach($videos as $video)
        <div class="group rounded-xl border border-slate-200 bg-white overflow-hidden flex flex-col hover:shadow-md transition-all shadow-xs"
             id="video-card-{{ $video->id }}"
             data-video-id="{{ $video->id }}"
             data-status="{{ $video->status }}">

            <!-- Thumbnail Poster Container (Dark video preview frame) -->
            <div class="relative bg-slate-950 aspect-[4/5] overflow-hidden flex items-center justify-center">
                @if($video->thumbnail_url)
                    <img src="{{ $video->thumbnail_url }}"
                         alt="{{ $video->title }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="text-slate-500 flex flex-col items-center">
                        <svg class="size-10 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span class="text-[10px] mt-1 text-slate-400">Rendering frame...</span>
                    </div>
                @endif

                <!-- Overlay Gradient -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-black/40"></div>

                <!-- Top Badges -->
                <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between gap-1 z-10">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-black/70 text-amber-300 border border-amber-500/30 backdrop-blur-sm">
                        {{ $video->aspect_ratio_label }}
                    </span>

                    <span class="video-status-badge text-[10px] font-semibold px-2 py-0.5 rounded backdrop-blur-sm flex items-center gap-1.5 {{ $video->status_badge['class'] }}">
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
                    <span class="text-[10px] text-slate-400 mt-0.5">FFmpeg Engine</span>
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

            <!-- Card Body / Product Info (Clean light layout) -->
            <div class="p-3.5 flex-1 flex flex-col justify-between">
                <div>
                    <h4 class="text-xs font-bold text-slate-900 line-clamp-1 hover:text-amber-700 transition-colors" title="{{ $video->title }}">
                        {{ $video->title }}
                    </h4>

                    @if($video->product)
                    <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-500">
                        <span class="font-mono text-slate-500">SKU: {{ $video->product->sku ?: 'N/A' }}</span>
                        <span>•</span>
                        <span class="text-amber-800 font-semibold">{{ $video->product->formatted_price }}</span>
                    </div>
                    @else
                    <div class="text-[11px] text-slate-500 mt-1">Standalone Promotional Video</div>
                    @endif

                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-[10px] px-2 py-0.5 rounded font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $video->template_label }}
                        </span>
                        <span class="text-[10px] text-slate-400">
                            {{ $video->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        @if($video->isCompleted() && $video->video_url)
                        <button type="button"
                                @click="openPreview('{{ $video->video_url }}', '{{ addslashes($video->title) }}', '{{ $video->aspect_ratio }}')"
                                class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-slate-900 hover:bg-slate-800 text-white transition-colors flex items-center gap-1 shadow-2xs">
                            <svg class="size-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            Preview
                        </button>

                        <a href="{{ route('admin.video-studio.download', $video) }}"
                           class="p-1 text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-md transition-colors"
                           title="Download Video">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </a>
                        @endif

                        @if($video->isFailed())
                        <form method="POST" action="{{ route('admin.video-studio.retry', $video) }}">
                            @csrf
                            <button type="submit"
                                    class="px-2.5 py-1 text-[11px] font-semibold rounded-md bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200 transition-colors flex items-center gap-1">
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
                                class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors"
                                title="Delete Video">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
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
