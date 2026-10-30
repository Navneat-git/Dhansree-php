@extends('layouts.admin')

@section('page_title', 'Video Studio')
@section('title', 'Video Studio - Marketing | Dhansree Admin')

@section('content')
<div class="space-y-6" x-data="videoStudioDashboard()">

    <!-- Page Header Block with Contrast & Depth -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center size-12 rounded-xl text-white shadow-sm"
                      style="background: linear-gradient(135deg, #580000 0%, #8b0000 50%, #b45309 100%) !important;">
                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-black text-slate-900 tracking-tight">Dhansree Video Studio</h1>
                        <span class="rounded-full bg-amber-100 text-amber-900 border border-amber-300 px-2.5 py-0.5 text-xs font-bold shadow-2xs">
                            Reels &amp; Ads
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Transform uploaded product photos into marketing videos for Instagram Reels, Facebook Ads, and Website listings.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('admin.video-studio.create') }}"
                   style="background-color: #d97706 !important; color: #ffffff !important;"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider shadow-sm hover:opacity-90 transition-opacity">
                    <svg class="size-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span style="color: #ffffff !important;">Create New Video</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-900 text-xs flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2.5">
            <svg class="size-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
            <span class="font-bold">{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 text-rose-900 text-xs flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-2.5">
            <svg class="size-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <span class="font-bold">{{ session('error') }}</span>
        </div>
    </div>
    @endif

    <!-- Database Migration Notice -->
    @if($tableMissing ?? false)
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-3">
                <svg class="size-5 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" />
                </svg>
                <div class="text-xs leading-relaxed">
                    <p class="font-black text-amber-950">Database Table Pending Initialization</p>
                    <p class="mt-0.5 text-amber-900">The <code class="bg-amber-100 px-1.5 py-0.5 rounded font-mono text-amber-900 font-bold">product_videos</code> table needs to be created before saving videos.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.video-studio.migrate-db') }}">
                @csrf
                <button type="submit"
                        style="background-color: #d97706 !important; color: #ffffff !important;"
                        class="shrink-0 px-4 py-2 text-xs font-bold rounded-lg shadow-xs hover:opacity-90 transition-opacity">
                    Initialize Database Table
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- FFmpeg Engine Notice for AWS Lightsail -->
    @if(!$ffmpegStatus['available'])
    <div class="rounded-xl border border-rose-300 bg-rose-50 p-5 shadow-xs">
        <div class="flex items-start gap-3.5">
            <div class="p-2 rounded-lg bg-rose-100 text-rose-700 shrink-0">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
            </div>
            <div class="text-xs space-y-1.5 text-slate-800">
                <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                    FFmpeg Engine Not Detected
                    <span class="text-[11px] font-bold text-rose-800 bg-rose-100 px-2 py-0.5 rounded-full border border-rose-200">Required for Video Rendering</span>
                </h3>
                <p class="text-slate-700 leading-relaxed">
                    To render videos on your <strong>AWS Lightsail</strong> instance, connect via SSH and run:
                </p>
                <div class="bg-slate-900 text-amber-300 font-mono text-xs px-3.5 py-2.5 rounded-lg flex items-center justify-between gap-3 my-2 border border-slate-800 shadow-inner">
                    <code>sudo apt update &amp;&amp; sudo apt install -y ffmpeg</code>
                </div>
                <p class="text-[11px] text-slate-600">
                    Once installed, FFmpeg is automatically recognized at <code class="bg-slate-200 text-slate-900 px-1.5 py-0.5 rounded font-mono font-bold">/usr/bin/ffmpeg</code>.
                </p>
            </div>
        </div>
    </div>
    @else
    <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-3.5 shadow-xs flex items-center justify-between text-xs">
        <div class="flex items-center gap-2.5 text-emerald-950 font-bold">
            <span style="width: 10px; height: 10px;" class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-4 ring-emerald-200 animate-pulse"></span>
            <span>FFmpeg Rendering Engine Active:</span>
            <span class="font-mono text-emerald-950 text-xs bg-emerald-200 border border-emerald-300 px-2 py-0.5 rounded font-bold">
                {{ $ffmpegStatus['version'] ?? 'Ready' }}
            </span>
        </div>
        <span class="text-xs text-emerald-800 font-semibold hidden sm:inline">Engine ready to render HD slideshow videos</span>
    </div>
    @endif

    <!-- Metric Stats Cards with Color-Coded Top Borders -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Videos -->
        <div class="rounded-xl border-t-4 border-t-slate-800 border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between text-xs font-bold text-slate-600 uppercase tracking-wider">
                <span>Total Videos</span>
                <span class="p-1 px-2 rounded bg-slate-100 text-slate-700 text-[11px] font-bold">All</span>
            </div>
            <p class="text-3xl font-black text-slate-900 mt-2">{{ number_format($stats['total']) }}</p>
            <p class="text-[11px] text-slate-500 font-medium mt-1">Generated promo assets</p>
        </div>

        <!-- Completed & Ready for Review -->
        <div class="rounded-xl border-t-4 border-t-emerald-500 border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between text-xs font-bold text-emerald-700 uppercase tracking-wider">
                <span>Completed</span>
                <span class="size-2 rounded-full bg-emerald-500"></span>
            </div>
            <p class="text-3xl font-black text-slate-900 mt-2">{{ number_format($stats['completed']) }}</p>
            <p class="text-[11px] text-emerald-700 font-semibold mt-1">Ready to download &amp; publish</p>
        </div>

        <!-- Ready for QC Review -->
        <div class="rounded-xl border-t-4 border-t-amber-500 border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between text-xs font-bold text-amber-800 uppercase tracking-wider">
                <span>QC Review</span>
                <span class="rounded bg-amber-100 text-amber-900 border border-amber-300 px-1.5 py-0.5 text-[10px] font-black">AI Footage</span>
            </div>
            <p class="text-3xl font-black text-slate-900 mt-2">{{ number_format($stats['pending_review'] ?? 0) }}</p>
            <p class="text-[11px] text-amber-800 font-semibold mt-1">Pending admin sign-off</p>
        </div>

        <!-- In Progress -->
        <div class="rounded-xl border-t-4 border-t-blue-500 border border-slate-200 bg-white p-4 shadow-xs">
            <div class="flex items-center justify-between text-xs font-bold text-blue-700 uppercase tracking-wider">
                <span>In Progress</span>
                <span class="size-2 rounded-full bg-blue-500 animate-pulse"></span>
            </div>
            <p class="text-3xl font-black text-slate-900 mt-2">{{ number_format($stats['processing']) }}</p>
            <p class="text-[11px] text-slate-500 font-medium mt-1">Queued or rendering</p>
        </div>
    </div>

    <!-- Filters & Search Bar with Clear Slate Contrast -->
    <div class="rounded-xl border border-slate-300 bg-slate-50 p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.video-studio.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <!-- Search -->
            <div class="lg:col-span-2 relative">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search by title, product or SKU..."
                       class="w-full pl-9 pr-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 font-medium placeholder-slate-400 focus:outline-none focus:border-amber-600 focus:ring-1 focus:ring-amber-600 shadow-2xs">
                <svg class="size-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>

            <!-- Video Type Filter -->
            <div>
                <select name="video_type" onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 font-medium focus:outline-none focus:border-amber-600 shadow-2xs">
                    <option value="all">All Modes</option>
                    <option value="slideshow" {{ request('video_type') === 'slideshow' ? 'selected' : '' }}>FFmpeg Slideshow</option>
                    <option value="ai_fashion" {{ request('video_type') === 'ai_fashion' ? 'selected' : '' }}>AI Fashion Shoot</option>
                    <option value="ai_fashion_branded" {{ request('video_type') === 'ai_fashion_branded' ? 'selected' : '' }}>AI Shoot + Branded</option>
                </select>
            </div>

            <!-- Approval Status Filter -->
            <div>
                <select name="approval_status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 font-medium focus:outline-none focus:border-amber-600 shadow-2xs">
                    <option value="all">All Approvals</option>
                    <option value="pending_review" {{ request('approval_status') === 'pending_review' ? 'selected' : '' }}>Ready for Review</option>
                    <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="not_required" {{ request('approval_status') === 'not_required' ? 'selected' : '' }}>Not Required</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 font-medium focus:outline-none focus:border-amber-600 shadow-2xs">
                    <option value="all">All Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="queued" {{ request('status') === 'queued' ? 'selected' : '' }}>Queued</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>

            <!-- Actions / Reset -->
            <div class="flex items-center gap-2">
                <button type="submit"
                        style="background-color: #0f172a !important; color: #ffffff !important;"
                        class="flex-1 px-4 py-2 text-xs font-bold rounded-lg shadow-xs hover:opacity-90 transition-opacity">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'approval_status', 'aspect_ratio', 'template', 'video_type']))
                <a href="{{ route('admin.video-studio.index') }}"
                   class="px-3 py-2 text-xs font-bold text-slate-700 hover:text-slate-950 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg transition-colors">
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Video Grid or High-Contrast Empty State -->
    @if($videos->isEmpty())
    <div class="rounded-2xl border-2 border-dashed border-amber-300 bg-gradient-to-b from-amber-50/60 via-white to-amber-50/30 p-12 text-center shadow-xs">
        <div style="width: 64px; height: 64px; min-width: 64px; max-width: 64px;"
             class="w-16 h-16 rounded-2xl text-white mx-auto flex items-center justify-center shadow-md"
             style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;">
            <svg class="size-8 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </div>
        <h3 class="text-lg font-black text-slate-900 mt-4">No Videos Generated Yet</h3>
        <p class="text-xs text-slate-600 max-w-md mx-auto mt-1.5 leading-relaxed font-medium">
            Select an existing catalog product to automatically generate promotional videos for Instagram Reels, Facebook Ads, or your website gallery.
        </p>
        <div class="mt-6">
            <a href="{{ route('admin.video-studio.create') }}"
               style="background-color: #d97706 !important; color: #ffffff !important;"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-wider shadow-md hover:opacity-90 transition-opacity">
                <svg class="size-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span style="color: #ffffff !important;">Create Your First Video</span>
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
                <div class="absolute top-2.5 left-2.5 right-2.5 flex items-center justify-between gap-1 z-10 flex-wrap">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded backdrop-blur-sm {{ $video->video_type_badge['class'] }}">
                        {{ $video->video_type_badge['text'] }}
                    </span>

                    <span class="video-status-badge text-[10px] font-semibold px-2 py-0.5 rounded backdrop-blur-sm flex items-center gap-1.5 {{ $video->status_badge['class'] }}">
                        <span style="width: 6px; height: 6px;" class="w-1.5 h-1.5 rounded-full {{ $video->status_badge['dot'] }}"></span>
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
                    <span class="text-[10px] text-slate-400 mt-0.5">{{ $video->isAi() ? 'Google Veo AI Engine' : 'FFmpeg Engine' }}</span>
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
                <div class="absolute bottom-2.5 left-2.5 right-2.5 z-10 flex items-center justify-between text-[11px] text-slate-200">
                    <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.5 rounded bg-black/70 backdrop-blur-sm text-slate-300 font-mono text-[10px]">
                            {{ $video->formatted_duration }}
                        </span>
                        <span class="px-1.5 py-0.5 rounded bg-black/70 backdrop-blur-sm text-slate-400 text-[10px]">
                            {{ $video->formatted_file_size }}
                        </span>
                    </div>
                    @if($video->isAi())
                    <span class="px-1.5 py-0.5 rounded bg-purple-950/80 text-purple-300 border border-purple-500/30 text-[10px] font-mono">
                        ~${{ number_format($video->actual_cost ?: $video->estimated_cost ?: 0.15, 2) }}
                    </span>
                    @endif
                </div>
            </div>

            <!-- Card Body / Product Info -->
            <div class="p-3.5 flex-1 flex flex-col justify-between">
                <div>
                    <h4 class="text-xs font-bold text-slate-900 line-clamp-1 hover:text-amber-700 transition-colors" title="{{ $video->title }}">
                        {{ $video->title }}
                    </h4>

                    @if($video->product)
                    <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-600">
                        <span class="font-mono text-slate-500">SKU: {{ $video->product->sku ?: 'N/A' }}</span>
                        <span>•</span>
                        <span class="text-amber-800 font-bold">{{ $video->product->formatted_price }}</span>
                    </div>
                    @else
                    <div class="text-[11px] text-slate-500 mt-1">Standalone Promotional Video</div>
                    @endif

                    <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                        <span class="text-[10px] px-2 py-0.5 rounded font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $video->template_label }}
                        </span>
                        @if($video->webm_url)
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-emerald-50 text-emerald-800 border border-emerald-200" title="Optimized WebM active on storefront">
                            🌐 WebM
                        </span>
                        @endif
                        @if($video->isAi())
                        <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ $video->approval_badge['class'] }}">
                            {{ $video->approval_badge['text'] }}
                        </span>
                        @endif
                        <span class="text-[10px] text-slate-500 font-medium">
                            {{ $video->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        @if($video->isCompleted() && $video->isAi())
                        <a href="{{ route('admin.video-studio.review', $video) }}"
                           style="background-color: #580000 !important; color: #ffffff !important;"
                           class="px-2.5 py-1 text-[11px] font-bold rounded-md shadow-2xs hover:opacity-90 transition-opacity flex items-center gap-1">
                            <svg class="size-3.5 text-amber-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                            </svg>
                            <span>QC Review</span>
                        </a>
                        @endif

                        @if($video->isCompleted() && $video->video_url)
                        <button type="button"
                                @click="openPreview('{{ $video->video_url }}', '{{ addslashes($video->title) }}', '{{ $video->aspect_ratio }}')"
                                style="background-color: #0f172a !important; color: #ffffff !important;"
                                class="px-2.5 py-1 text-[11px] font-bold rounded-md shadow-2xs hover:opacity-90 transition-opacity flex items-center gap-1 cursor-pointer">
                            <svg class="size-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            Preview
                        </button>

                        <div class="flex items-center gap-1 pl-1">
                            <a href="{{ route('admin.video-studio.download', $video) }}"
                               class="p-1 text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-md transition-colors"
                               title="Download MP4">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </a>
                            @if($video->webm_url)
                            <a href="{{ route('admin.video-studio.download', [$video, 'format' => 'webm']) }}"
                               class="px-1.5 py-0.5 text-[9px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-300 hover:bg-emerald-100 rounded transition-colors"
                               title="Download WebM format">
                                WebM
                            </a>
                            @endif
                            @if(!empty($video->render_settings['branded_video_path']))
                            <a href="{{ route('admin.video-studio.download', [$video, 'format' => 'branded']) }}"
                               class="px-1.5 py-0.5 text-[9px] font-bold text-amber-900 bg-amber-50 border border-amber-300 hover:bg-amber-100 rounded transition-colors"
                               title="Download Social Media Reel (Branded MP4 with audio & logo)">
                                Reel
                            </a>
                            @endif
                        </div>
                        @endif

                        @if($video->isFailed())
                        <form method="POST" action="{{ route('admin.video-studio.retry', $video) }}">
                            @csrf
                            <button type="submit"
                                    class="px-2.5 py-1 text-[11px] font-bold rounded-md bg-amber-100 text-amber-900 hover:bg-amber-200 border border-amber-300 transition-colors flex items-center gap-1">
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
