@extends('layouts.admin')

@section('page_title', 'Create Video')
@section('title', 'Create Promotional Video - Video Studio | Dhansree Admin')

@section('content')
<div class="space-y-6" x-data="videoStudioCreator()">
    <!-- Breadcrumb / Back Link -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.video-studio.index') }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            <span>Back to Video Studio</span>
        </a>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5 text-xs">
                <span class="text-slate-500 font-medium">FFmpeg:</span>
                <span class="font-semibold px-2 py-0.5 rounded-full {{ $ffmpegAvailable ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    {{ $ffmpegAvailable ? 'Ready' : 'Not Detected' }}
                </span>
            </div>
            <div class="flex items-center gap-1.5 text-xs">
                <span class="text-slate-500 font-medium">Google Veo AI:</span>
                <span class="font-semibold px-2 py-0.5 rounded-full {{ $googleVideoConfigured ? 'bg-purple-50 text-purple-800 border border-purple-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                    {{ $googleVideoConfigured ? 'Connected' : 'Setup Required' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Create Video Studio Production</h1>
            <p class="text-xs text-slate-500 mt-1">Select an existing catalog product to generate slideshows or realistic Google Veo AI fashion shoot videos.</p>
        </div>
    </div>

    <!-- AI Configuration Advisory if unconfigured -->
    @if(!$googleVideoConfigured)
    <div x-show="isAiMode" x-cloak class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 text-xs shadow-xs space-y-1">
        <div class="flex items-center gap-2 font-black text-amber-900">
            <svg class="size-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.008v.008H12v-.008Z" />
            </svg>
            <span>Google Veo Video API Notice</span>
        </div>
        <p class="text-amber-900 text-[11px] leading-relaxed">
            Google AI Video generation is currently in mock/disabled state. To enable live generation of moving models wearing your suits, add <code class="bg-amber-100 font-mono px-1 py-0.5 rounded font-bold">GOOGLE_VIDEO_API_KEY</code> and <code class="bg-amber-100 font-mono px-1 py-0.5 rounded font-bold">GOOGLE_VIDEO_ENABLED=true</code> to your <code class="font-mono">.env</code> file. Slideshow generation remains 100% free and functional.
        </p>
    </div>
    @endif

    <!-- Main Form Grid -->
    <form action="{{ route('admin.video-studio.store') }}"
          method="POST"
          enctype="multipart/form-data"
          id="create-video-form"
          @keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }"
          @submit="return handleFormSubmit($event)">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left 2 Cols: Mode Selection + Product Search + Images + Prompts / Templates -->
            <div class="lg:col-span-2 space-y-6">

                <!-- 1. Video Generation Mode Selector (4 Modes) -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        1. Select Video Production Mode
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                        <!-- Mode 1: FFmpeg Slideshow (Default & Free) -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                               :class="videoType === 'slideshow' ? 'border-amber-500 bg-amber-50/70 ring-1 ring-amber-500/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="video_type" value="slideshow" x-model="videoType" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <svg class="size-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    FFmpeg Slideshow
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800">100% Free</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-2 leading-relaxed">Smooth crossfades, zoom effects, audio, and branding directly from catalog photos.</p>
                        </label>

                        <!-- Mode 2: Import AI Video + Brand (from Google Flow) -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                               :class="videoType === 'imported_branded' ? 'border-amber-600 bg-amber-50/80 ring-2 ring-amber-500/40' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="video_type" value="imported_branded" x-model="videoType" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <svg class="size-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                    </svg>
                                    Import &amp; Brand
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 font-semibold">Flow / Reel</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-2 leading-relaxed">Upload video from Google Flow or camera; FFmpeg burns logo, price, royal music &amp; CTA.</p>
                        </label>

                        <!-- Mode 3: AI Fashion Shoot (Raw Google Veo Footage) -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                               :class="videoType === 'ai_fashion' ? 'border-purple-500 bg-purple-50/70 ring-1 ring-purple-500/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="video_type" value="ai_fashion" x-model="videoType" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <svg class="size-4 text-purple-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                                    </svg>
                                    AI Fashion Shoot
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-100 text-purple-800">Veo AI (~$0.15)</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-2 leading-relaxed">Photorealistic model wearing and moving in your catalog suit. Pure raw AI footage.</p>
                        </label>

                        <!-- Mode 4: AI Fashion Shoot + Branded Export (Veo + FFmpeg Post-Processing) -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                               :class="videoType === 'ai_fashion_branded' ? 'border-indigo-500 bg-indigo-50/70 ring-1 ring-indigo-500/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="video_type" value="ai_fashion_branded" x-model="videoType" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <svg class="size-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                    </svg>
                                    AI Shoot + Branded
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-800">AI + Branding</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-2 leading-relaxed">AI model footage finished with Dhansree logo, verified price, music, and closing CTA.</p>
                        </label>
                    </div>
                </div>

                <!-- 2. Product Search & Selection -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        2. Search &amp; Select Product
                    </label>
                    <p class="text-xs text-slate-500 mb-4">Choose an active product from your catalog to link with this video.</p>

                    <!-- Search Input with AJAX Auto-complete & Search Button -->
                    <div class="relative">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input type="text"
                                       id="product-search-input"
                                       x-model="searchQuery"
                                       @input.debounce.300ms="searchProducts()"
                                       @keydown.enter.prevent="if (searchResults.length > 0) { selectProduct(searchResults[0]); } else { searchProducts(); }"
                                       @focus="searchDropdownOpen = true"
                                       placeholder="Type product name, SKU or keyword..."
                                       class="w-full pl-10 pr-4 py-2.5 text-xs bg-white border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                                <svg style="width: 18px; height: 18px;" class="w-4.5 h-4.5 text-slate-400 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                            </div>
                            <button type="button"
                                    @click="searchProducts()"
                                    style="background-color: #d97706 !important; color: #ffffff !important;"
                                    class="px-4 py-2.5 text-xs font-bold rounded-xl shadow-xs hover:opacity-90 transition-opacity shrink-0">
                                Search
                            </button>
                        </div>

                        <!-- Search Dropdown Results -->
                        <div x-show="searchDropdownOpen && searchResults.length > 0"
                             @click.outside="searchDropdownOpen = false"
                             class="absolute left-0 right-0 top-full mt-1.5 z-30 max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl divide-y divide-slate-100">
                            <template x-for="prod in searchResults" :key="prod.id">
                                <div class="w-full p-3 hover:bg-amber-50/40 transition-colors flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div style="width: 48px; height: 48px; min-width: 48px; max-width: 48px;"
                                             class="w-12 h-12 rounded-lg bg-slate-100 border border-slate-200 shrink-0 overflow-hidden flex items-center justify-center">
                                            <template x-if="prod.primary_image">
                                                <img :src="prod.primary_image" style="width: 48px; height: 48px; min-width: 48px; max-width: 48px; object-fit: cover;" class="w-12 h-12 object-cover block">
                                            </template>
                                            <template x-if="!prod.primary_image">
                                                <svg style="width: 20px; height: 20px;" class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-slate-900 truncate" x-text="prod.name"></p>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                SKU: <span class="font-mono font-medium text-slate-700" x-text="prod.sku || 'N/A'"></span> •
                                                <span class="text-amber-700 font-bold" x-text="prod.price"></span> •
                                                <span class="text-slate-500" x-text="(prod.image_count || 0) + ' images'"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="selectProduct(prod)"
                                            style="background-color: #0f172a !important; color: #ffffff !important;"
                                            class="shrink-0 px-3 py-1.5 text-xs font-bold rounded-lg shadow-2xs hover:opacity-90 transition-opacity cursor-pointer">
                                        Choose Product
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Quick-Select from Recent Catalog Products -->
                    @if(!empty($recentProducts) && count($recentProducts) > 0)
                    <div x-show="!selectedProduct" class="mt-4 pt-3 border-t border-slate-100">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                            ⚡ Or 1-Click Pick from Catalog Products:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto pr-1">
                            @foreach($recentProducts->take(8) as $rp)
                                <div @click="selectProduct(@js([
                                        'id' => $rp->id,
                                        'name' => $rp->name,
                                        'sku' => $rp->sku,
                                        'price' => $rp->formatted_price,
                                        'primary_image' => $rp->primary_image_url,
                                        'images' => $rp->images->map(fn($img) => ['id' => $img->id, 'url' => $img->url])->values()->all(),
                                     ]))"
                                     class="flex items-center gap-2.5 p-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-amber-50 hover:border-amber-400 transition-all cursor-pointer group">
                                    <img src="{{ $rp->primary_image_url }}" class="size-10 rounded-lg object-cover bg-white border border-slate-200 shrink-0">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-800 truncate group-hover:text-amber-900">{{ $rp->name }}</p>
                                        <p class="text-[10px] text-slate-500 mt-0.5">
                                            <span class="font-mono text-slate-600">{{ $rp->sku ?: 'SKU' }}</span> • <span class="text-amber-700 font-bold">{{ $rp->formatted_price }}</span>
                                        </p>
                                    </div>
                                    <span class="text-[11px] font-bold text-amber-700 opacity-0 group-hover:opacity-100 transition-opacity">Select ➔</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Selected Product Banner -->
                    <div x-show="selectedProduct" class="mt-4 p-4 rounded-xl border border-amber-300 bg-amber-50/60 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div style="width: 56px; height: 56px; min-width: 56px; max-width: 56px;"
                                 class="w-14 h-14 rounded-lg bg-white border border-amber-200 shrink-0 overflow-hidden flex items-center justify-center shadow-xs">
                                <template x-if="selectedProduct && selectedProduct.primary_image">
                                    <img :src="selectedProduct.primary_image" style="width: 56px; height: 56px; min-width: 56px; max-width: 56px; object-fit: cover;" class="w-14 h-14 object-cover">
                                </template>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Selected Product</span>
                                <h3 class="text-xs font-black text-slate-900 truncate" x-text="selectedProduct ? selectedProduct.name : ''"></h3>
                                <p class="text-[11px] text-slate-600 mt-0.5 font-medium">
                                    SKU: <span class="font-mono font-semibold" x-text="selectedProduct ? (selectedProduct.sku || 'N/A') : ''"></span> •
                                    Price: <span class="font-bold text-amber-800" x-text="selectedProduct ? selectedProduct.price : ''"></span>
                                </p>
                            </div>
                        </div>

                        <button type="button" @click="clearProduct()" class="text-xs text-slate-400 hover:text-rose-600 font-bold p-1 cursor-pointer">
                            ✕ Change
                        </button>
                    </div>

                    <input type="hidden" name="product_id" :value="selectedProductId">
                </div>

                <!-- 2b. Upload Raw Video File (Only for imported_branded mode) -->
                <div class="rounded-xl border border-amber-300 bg-white p-5 shadow-xs space-y-4" x-show="videoType === 'imported_branded'" x-cloak>
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-amber-950">
                                2b. Upload Raw Video File (Google Flow / Camera / Studio)
                            </label>
                            <p class="text-xs text-slate-500 mt-0.5">Select a raw MP4, MOV, or WebM video file (max 60MB). FFmpeg will automatically brand it with audio, logo, and price.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300">
                                MP4 / MOV / WebM (Max 60MB)
                            </span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200" title="PHP upload_max_filesize limit on this server">
                                Server limit: <span class="font-bold text-slate-900" x-text="phpUploadLimitText"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Dropzone Area -->
                    <div class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors"
                         :class="uploadedVideoName ? 'border-emerald-400 bg-emerald-50/30' : 'border-slate-300 bg-slate-50 hover:bg-slate-100/70'">
                        <input type="file"
                               id="uploaded_video_input"
                               name="uploaded_video"
                               accept="video/mp4,video/quicktime,video/webm"
                               @change="handleVideoFileChange($event)"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                               :class="uploadedVideoName ? 'pointer-events-none' : ''">

                        <template x-if="!uploadedVideoName">
                            <div class="space-y-2 pointer-events-none">
                                <div class="size-12 rounded-full bg-amber-100 text-amber-700 mx-auto flex items-center justify-center">
                                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <p class="text-xs font-bold text-slate-800">
                                    Click to choose video file, or drag and drop
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    Google Flow generated video, phone camera recording, or downloaded reel
                                </p>
                            </div>
                        </template>

                        <template x-if="uploadedVideoName">
                            <div class="space-y-3">
                                <div class="flex items-center justify-center gap-2 text-emerald-800 font-bold text-xs">
                                    <svg class="size-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    <span x-text="uploadedVideoName"></span>
                                    <span class="text-[11px] text-slate-500 font-normal" x-text="'(' + uploadedVideoSize + ')'"></span>
                                </div>

                                <div class="max-w-xs mx-auto aspect-[9/16] max-h-64 rounded-xl overflow-hidden bg-black shadow-md border border-slate-700">
                                    <video :src="uploadedVideoPreviewUrl" controls playsinline class="w-full h-full object-contain"></video>
                                </div>

                                <button type="button" @click="clearUploadedVideo()" class="text-xs font-bold text-rose-600 hover:text-rose-800 relative z-20">
                                    ✕ Remove &amp; choose different video
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 3. AI Fashion Shoot Styles & Direct Prompt Controls (Active for AI modes) -->
                <div class="rounded-xl border border-purple-200 bg-white p-5 shadow-xs" x-show="isAiMode" x-cloak>
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-purple-900">
                                3. Fashion Shoot Style &amp; Creative Direction
                            </label>
                            <p class="text-xs text-slate-500 mt-0.5">Select a camera motion style optimized for Indian ethnic suits and edit the prompt if desired.</p>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                            {{ $googleVideoModel }}
                        </span>
                    </div>

                    <!-- Predefined Style Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($fashionStyles as $sKey => $s)
                        <label class="relative flex flex-col p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="selectedFashionStyle === '{{ $sKey }}' ? 'border-purple-600 bg-purple-50/70 ring-1 ring-purple-500/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="prompt_template" value="{{ $sKey }}" x-model="selectedFashionStyle" @change="fetchPrompt()" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">{{ $s['name'] }}</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-purple-100 text-purple-800">
                                    {{ $s['badge'] }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">{{ $s['description'] }}</p>
                        </label>
                        @endforeach
                    </div>

                    <!-- Editable AI Prompt Textarea -->
                    <div class="mt-4 pt-4 border-t border-purple-100 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>Google Veo Scene &amp; Garment Prompt</span>
                                    <span class="text-[10px] font-normal text-slate-500">(Auto-generated with strict garment fidelity rules)</span>
                                </label>
                            </div>
                            <button type="button"
                                    @click="fetchPrompt(true)"
                                    class="text-xs text-purple-700 hover:text-purple-900 font-bold flex items-center gap-1 hover:underline">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span>Reset / Rebuild Prompt</span>
                            </button>
                        </div>

                        <textarea name="prompt"
                                  rows="4"
                                  x-model="aiPrompt"
                                  placeholder="Scene prompt will automatically populate when you select a product..."
                                  class="w-full p-3 text-xs font-mono bg-slate-900 text-slate-100 border border-slate-700 rounded-xl focus:border-purple-500 focus:ring-1 focus:ring-purple-500 leading-relaxed"></textarea>

                        <!-- Negative Prompt Collapsible -->
                        <div x-data="{ showNeg: false }">
                            <button type="button" @click="showNeg = !showNeg" class="text-[11px] font-bold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                                <span x-text="showNeg ? '▼ Hide Negative Prompt' : '▶ Advanced: View / Edit Negative Prompt'"></span>
                            </button>
                            <div x-show="showNeg" x-cloak class="mt-2">
                                <textarea name="negative_prompt"
                                          rows="2"
                                          x-model="aiNegativePrompt"
                                          class="w-full p-2.5 text-[11px] font-mono bg-slate-50 border border-slate-300 rounded-lg text-slate-700"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Image Selection & Reference Picker -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs" x-show="availableImages.length > 0">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                4. Select Images &amp; Primary Reference (<span x-text="selectedImageIds.length"></span> selected)
                            </label>
                            <p class="text-xs text-slate-500 mt-0.5" x-show="!isAiMode && videoType !== 'imported_branded'">Click an image to toggle inclusion. Use arrows to change playback sequence.</p>
                            <p class="text-xs text-amber-700 mt-0.5 font-medium" x-show="videoType === 'imported_branded'">
                                ★ In Imported Video mode, image selection is optional (used as poster / thumbnail).
                            </p>
                            <p class="text-xs text-purple-700 mt-0.5 font-medium" x-show="isAiMode">
                                ★ In AI Fashion mode, select the <strong>Primary Reference</strong> (full front view) the AI model will wear.
                            </p>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <button type="button" @click="selectAllImages()" class="text-amber-700 hover:underline font-semibold">Select All</button>
                            <span class="text-slate-300">•</span>
                            <button type="button" @click="clearAllImages()" class="text-slate-500 hover:underline">Clear</button>
                        </div>
                    </div>

                    <!-- Image Gallery Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                        <template x-for="(img, idx) in availableImages" :key="img.id">
                            <div class="relative group rounded-xl border overflow-hidden transition-all bg-slate-100 aspect-[3/4] flex flex-col"
                                 :class="isSelected(img.id) ? (primaryImageId === img.id ? 'border-amber-500 ring-3 ring-amber-400 shadow-md' : 'border-amber-500 ring-1 ring-amber-500/40 shadow-xs') : 'border-slate-200 opacity-60 hover:opacity-100'">

                                <!-- Image Thumbnail -->
                                <img :src="img.url" class="w-full h-full object-cover cursor-pointer" @click="toggleImage(img.id)">

                                <!-- Selection Number Badge -->
                                <div style="width: 24px; height: 24px; min-width: 24px; max-width: 24px;"
                                     class="absolute top-1.5 left-1.5 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shadow-md cursor-pointer"
                                     @click="toggleImage(img.id)"
                                     :class="isSelected(img.id) ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-900/60 text-white border border-white/20'">
                                    <template x-if="isSelected(img.id)">
                                        <span x-text="getOrderIndex(img.id)"></span>
                                    </template>
                                    <template x-if="!isSelected(img.id)">
                                        <span>+</span>
                                    </template>
                                </div>

                                <!-- Set as Primary Reference Button for AI Mode -->
                                <template x-if="isAiMode && isSelected(img.id)">
                                    <button type="button"
                                            @click.stop="setPrimaryImage(img.id)"
                                            class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[9px] font-black shadow-md transition-all cursor-pointer"
                                            :class="primaryImageId === img.id ? 'bg-amber-400 text-slate-950 ring-2 ring-white' : 'bg-black/75 text-amber-300 hover:bg-black'">
                                        <span x-text="primaryImageId === img.id ? '★ PRIMARY' : 'SET PRIMARY'"></span>
                                    </button>
                                </template>

                                <!-- Move Left / Right Controls for Order -->
                                <template x-if="isSelected(img.id) && !isAiMode">
                                    <div class="absolute bottom-1.5 inset-x-1.5 flex items-center justify-between gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/80 rounded-md px-1 py-0.5 backdrop-blur-xs">
                                        <button type="button" @click.stop="moveImageLeft(img.id)" class="p-1 text-slate-300 hover:text-white" title="Move earlier">‹</button>
                                        <span class="text-[9px] text-amber-300 font-mono" x-text="'#' + getOrderIndex(img.id)"></span>
                                        <button type="button" @click.stop="moveImageRight(img.id)" class="p-1 text-slate-300 hover:text-white" title="Move later">›</button>
                                    </div>
                                </template>

                                <!-- Hidden inputs for selected order -->
                                <template x-if="isSelected(img.id)">
                                    <input type="hidden" name="image_ids[]" :value="img.id">
                                </template>
                            </div>
                        </template>
                    </div>

                    <input type="hidden" name="primary_image_id" :value="primaryImageId">

                    <!-- Minimum 1 Image Warning -->
                    <div x-show="selectedImageIds.length === 0 && videoType !== 'imported_branded'" class="mt-3 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2">
                        <svg class="size-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                        <span>Please select at least one catalog image to generate the video.</span>
                    </div>
                </div>

                <!-- 5. Video Template Selector (for Slideshow and AI Branded) -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs" x-show="videoType !== 'ai_fashion'">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        5. Choose Visual Template &amp; Styling
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($templates as $key => $tpl)
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                               :class="selectedTemplate === '{{ $key }}' ? 'border-amber-500 bg-amber-50/70 ring-1 ring-amber-500/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="template" value="{{ $key }}" x-model="selectedTemplate" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">{{ $tpl['name'] }}</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $tpl['tag'] }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">{{ $tpl['description'] }}</p>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- 6. Aspect Ratio Presets -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        6. Choose Export Aspect Ratio &amp; Channel
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($aspectRatios as $ratioKey => $ratio)
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                               :class="selectedAspectRatio === '{{ $ratioKey }}' ? 'border-amber-500 bg-amber-50/70 ring-1 ring-amber-500/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="aspect_ratio" value="{{ $ratioKey }}" x-model="selectedAspectRatio" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">{{ $ratio['label'] }}</span>
                                <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200">
                                    {{ $ratioKey }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">{{ $ratio['description'] }}</p>
                            <span class="text-[10px] text-slate-400 mt-2 font-mono">{{ $ratio['width'] }} × {{ $ratio['height'] }} px</span>
                        </label>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Right Col: Duration, Audio, Overlays & Launch -->
            <div class="space-y-6">

                <!-- Duration & Music -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-5 shadow-xs">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Duration (Seconds)
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach($durations as $d)
                            <label class="text-center py-2 rounded-lg border text-xs font-bold cursor-pointer transition-all"
                                   :class="duration === {{ $d }} ? 'border-slate-900 bg-slate-900 text-white shadow-xs' : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'">
                                <input type="radio" name="duration_seconds" value="{{ $d }}" x-model.number="duration" class="sr-only">
                                {{ $d }}s
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Background Music (for Slideshow and AI Branded) -->
                    <div class="pt-4 border-t border-slate-100" x-show="videoType !== 'ai_fashion'">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-700">Background Music</label>
                                <p class="text-[11px] text-slate-500">Royalty-free ethnic &amp; runway tracks</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_music" value="1" x-model="enableMusic" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-600"></div>
                            </label>
                        </div>

                        <div x-show="enableMusic" class="space-y-2">
                            @foreach($audioTracks as $aKey => $audio)
                            <label class="flex items-center gap-2 p-2.5 rounded-lg border text-xs cursor-pointer transition-all"
                                   :class="selectedAudioTrack === '{{ $aKey }}' ? 'border-amber-500 bg-amber-50/70 text-slate-900' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                                <input type="radio" name="audio_track" value="{{ $aKey }}" x-model="selectedAudioTrack" class="sr-only">
                                <div class="size-2 rounded-full" :class="selectedAudioTrack === '{{ $aKey }}' ? 'bg-amber-600' : 'bg-slate-300'"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-slate-900 truncate">{{ $audio['name'] }}</p>
                                    <p class="text-[10px] text-slate-500 truncate">{{ $audio['description'] }}</p>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Video Purpose & Branding Style Preset -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4 shadow-xs" x-show="videoType !== 'ai_fashion'">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                            Video Purpose &amp; Branding Style
                        </label>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300">
                            Website WebM / Social MP4
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">
                        Choose whether this video is for your website catalog (clean &amp; fast WebM) or social media ads (branded MP4).
                    </p>

                    <div class="space-y-2.5">
                        <!-- Option 1: Clean Storefront WebM (Recommended for Website) -->
                        <label class="relative flex flex-col p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="exportPreset === 'clean_storefront' ? 'border-emerald-600 bg-emerald-50/80 ring-2 ring-emerald-500/40' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="export_preset" value="clean_storefront" x-model="exportPreset" @change="setExportPreset('clean_storefront')" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>🌐</span>
                                    <span>Clean Storefront (Optimized WebM)</span>
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-200 text-emerald-950">Recommended for PDP</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1.5 leading-relaxed">
                                Pure model &amp; fabric movement. <strong>No logo plate, no burned price, no CTA, muted loop</strong>. Up to 70% smaller file size for instant loading.
                            </p>
                        </label>

                        <!-- Option 2: Social Media & Ad Reel (Branded MP4) -->
                        <label class="relative flex flex-col p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="exportPreset === 'social_branded' ? 'border-amber-600 bg-amber-50/80 ring-2 ring-amber-500/40' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="export_preset" value="social_branded" x-model="exportPreset" @change="setExportPreset('social_branded')" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>📱</span>
                                    <span>Social Media Reel (Branded MP4)</span>
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-900">Instagram / Ads</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1.5 leading-relaxed">
                                Burns Dhansree royal logo, verified selling price, royal music, and closing "Shop Now" card for Instagram &amp; WhatsApp status.
                            </p>
                        </label>

                        <!-- Option 3: Dual Export (Best of Both Worlds) -->
                        <label class="relative flex flex-col p-3.5 rounded-xl border cursor-pointer transition-all"
                               :class="exportPreset === 'dual' ? 'border-indigo-600 bg-indigo-50/80 ring-2 ring-indigo-500/40' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="export_preset" value="dual" x-model="exportPreset" @change="setExportPreset('dual')" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>⚡</span>
                                    <span>Dual Export (WebM for Website + Branded MP4 for Social)</span>
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-900">All-in-One</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1.5 leading-relaxed">
                                Generates both in 1 click! Clean WebM attaches to your website product page; Branded MP4 is ready to download for Instagram.
                            </p>
                        </label>
                    </div>
                </div>

                <!-- Text Overlays & CTA (for Slideshow and AI Branded) -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4 shadow-xs" x-show="videoType !== 'ai_fashion'">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Brand &amp; Text Overlays
                    </label>

                    <!-- Text Overlay Toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-900">Product Title Overlay</span>
                            <p class="text-[11px] text-slate-500">Gold/maroon title card at bottom</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="text_overlay" value="1" x-model="textOverlay" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-600"></div>
                        </label>
                    </div>

                    <!-- Verified Price Toggle -->
                    <div class="flex items-center justify-between" x-show="textOverlay">
                        <div>
                            <span class="text-xs font-semibold text-slate-900">Verified Price Tag</span>
                            <p class="text-[11px] text-slate-500">Display verified selling price</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="show_price" value="1" x-model="showPrice" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-600"></div>
                        </label>
                    </div>

                    <!-- Final Call To Action Screen -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                        <div>
                            <span class="text-xs font-semibold text-slate-900">Final Call to Action</span>
                            <p class="text-[11px] text-slate-500">"Shop Now at dhansree.com" closing card</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="call_to_action" value="1" x-model="callToAction" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-600"></div>
                        </label>
                    </div>

                    <!-- Custom Title / Headline (Optional) -->
                    <div class="pt-2 border-t border-slate-100">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Custom Video Headline (Optional)</label>
                        <input type="text"
                               name="title"
                               x-model="customTitle"
                               :placeholder="selectedProduct ? selectedProduct.name : 'Dhansree Promotional Reel'"
                               class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500">
                    </div>
                </div>

                <!-- Launch & Summary Panel -->
                <div class="rounded-xl border p-5 space-y-4 shadow-xs"
                     :class="isAiMode ? 'border-purple-200 bg-purple-50/50' : 'border-amber-200 bg-amber-50/50'">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-900">Production Summary</h4>

                    <div class="space-y-2 text-xs divide-y divide-slate-200/60">
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Mode:</span>
                            <span class="font-bold" :class="isAiMode ? 'text-purple-700' : 'text-amber-800'"
                                  x-text="videoType === 'slideshow' ? 'FFmpeg Slideshow' : (videoType === 'imported_branded' ? 'Import AI Video + Brand' : (videoType === 'ai_fashion' ? 'AI Fashion Shoot' : 'AI Shoot + Branded'))"></span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Target Product:</span>
                            <span class="font-bold text-slate-900 truncate max-w-[150px]" x-text="selectedProduct ? selectedProduct.name : 'None selected'"></span>
                        </div>
                        <div class="flex justify-between pt-1" x-show="isAiMode">
                            <span class="text-slate-500">Est. API Cost:</span>
                            <span class="font-mono font-bold text-purple-700">~${{ number_format($estimatedCostPerClip, 2) }} USD</span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Aspect Ratio:</span>
                            <span class="font-mono font-bold text-slate-900" x-text="selectedAspectRatio"></span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Duration:</span>
                            <span class="font-bold text-slate-900" x-text="duration + 's'"></span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Style:</span>
                            <span class="font-bold text-slate-900 truncate max-w-[150px]"
                                  :class="exportPreset === 'clean_storefront' ? 'text-emerald-700' : 'text-amber-800'"
                                  x-text="exportPreset === 'clean_storefront' ? 'Clean Storefront (WebM)' : (exportPreset === 'dual' ? 'Dual (WebM + Social)' : 'Social Branded MP4')"></span>
                        </div>
                    </div>

                    <!-- Dynamic Production Readiness Checklist -->
                    <div class="p-3 rounded-xl border text-xs space-y-2 transition-all"
                         :class="canSubmit() ? 'bg-emerald-50/80 border-emerald-300 text-emerald-950' : 'bg-amber-50/90 border-amber-300 text-amber-950'">
                        <div class="font-bold flex items-center justify-between text-[11px] uppercase tracking-wider">
                            <span>Ready Checklist</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                  :class="canSubmit() ? 'bg-emerald-200 text-emerald-900' : 'bg-amber-200 text-amber-900'"
                                  x-text="canSubmit() ? '✓ Ready to Produce' : '⚠️ Action Needed'"></span>
                        </div>

                        <!-- Product Status -->
                        <div class="flex items-start gap-2 pt-1 border-t border-slate-200/50">
                            <span class="font-bold text-sm leading-none shrink-0" :class="selectedProductId ? 'text-emerald-600' : 'text-amber-600'" x-text="selectedProductId ? '✓' : '⚠️'"></span>
                            <div class="flex-1 min-w-0">
                                <span class="font-semibold block" x-text="selectedProductId ? 'Catalog Product Linked' : 'Step 2: Choose Product (Required)'"></span>
                                <p class="text-[10px] text-slate-500 truncate" x-text="selectedProduct ? (selectedProduct.name + ' • ' + selectedProduct.price) : 'Pick or search your suit in Step 2'"></p>
                            </div>
                        </div>

                        <!-- Video / Images Status -->
                        <template x-if="videoType === 'imported_branded'">
                            <div class="flex items-start gap-2 pt-1 border-t border-slate-200/50">
                                <span class="font-bold text-sm leading-none shrink-0" :class="uploadedVideoFile ? 'text-emerald-600' : 'text-amber-600'" x-text="uploadedVideoFile ? '✓' : '⚠️'"></span>
                                <div class="flex-1 min-w-0">
                                    <span class="font-semibold block" x-text="uploadedVideoFile ? 'Raw Video Loaded' : 'Step 2b: Choose Video File (Required)'"></span>
                                    <p class="text-[10px] text-slate-500 truncate" x-text="uploadedVideoFile ? (uploadedVideoName + ' (' + uploadedVideoSize + ')') : 'Browse raw MP4/MOV from Google Flow'"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="videoType !== 'imported_branded'">
                            <div class="flex items-start gap-2 pt-1 border-t border-slate-200/50">
                                <span class="font-bold text-sm leading-none shrink-0" :class="selectedImageIds.length > 0 ? 'text-emerald-600' : 'text-amber-600'" x-text="selectedImageIds.length > 0 ? '✓' : '⚠️'"></span>
                                <div class="flex-1 min-w-0">
                                    <span class="font-semibold block" x-text="selectedImageIds.length > 0 ? (selectedImageIds.length + ' Product Photos Selected') : 'Step 2: Select Photos (Required)'"></span>
                                    <p class="text-[10px] text-slate-500" x-text="selectedImageIds.length > 0 ? 'Ready for FFmpeg slideshow rendering' : 'Select at least 1 image'"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Main Submit Button -->
                    <button type="button"
                            @click="handleFormSubmit($event)"
                            :disabled="isSubmitting"
                            :style="isAiMode ? 'background-color: #7e22ce !important; color: #ffffff !important;' : (videoType === 'imported_branded' ? 'background-color: #b45309 !important; color: #ffffff !important;' : 'background-color: #d97706 !important; color: #ffffff !important;')"
                            class="w-full py-3.5 px-4 rounded-xl text-sm font-bold shadow-md hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <template x-if="!isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg style="width: 18px; height: 18px;" class="w-4.5 h-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <span style="color: #ffffff !important;" x-text="videoType === 'imported_branded' ? (exportPreset === 'clean_storefront' ? 'Optimize & Upload Clean WebM' : (exportPreset === 'dual' ? 'Upload Dual (WebM + Branded MP4)' : 'Upload & Brand Video for Social')) : (isAiMode ? 'Generate AI Fashion Video' : 'Render Video Now')"></span>
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <span class="flex items-center gap-2 text-white">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="videoType === 'imported_branded' ? (exportPreset === 'clean_storefront' ? 'Encoding Clean WebM with FFmpeg...' : 'Encoding Video with FFmpeg...') : (isAiMode ? 'Directing Google Veo AI...' : 'Rendering with FFmpeg...')"></span>
                            </span>
                        </template>
                    </button>
                    <p class="text-[10px] text-center text-slate-500 font-medium">
                        Runs safely in the background. Original product photos are strictly preserved.
                    </p>
                </div>

            </div>

        </div>

        <!-- Fullscreen Processing & Progress Modal -->
        <div x-show="showProcessingModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm"
             style="display: none;"
             :style="showProcessingModal ? 'display: flex !important;' : 'display: none !important;'">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-amber-300 p-6 text-center overflow-hidden">
                <div class="absolute top-0 inset-x-0 h-1.5"
                     :style="isAiMode ? 'background: linear-gradient(90deg, #7e22ce, #a855f7, #7e22ce);' : 'background: linear-gradient(90deg, #b45309, #f59e0b, #b45309);'"></div>

                <div style="width: 64px; height: 64px; min-width: 64px; max-width: 64px;"
                     class="w-16 h-16 rounded-2xl text-white mx-auto flex items-center justify-center shadow-lg mb-4"
                     :style="isAiMode ? 'background: linear-gradient(135deg, #7e22ce 0%, #581c87 100%) !important;' : 'background: linear-gradient(135deg, #d97706 0%, #92400e 100%) !important;'">
                    <svg style="width: 32px; height: 32px;" class="w-8 h-8 text-white animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>

                <h3 class="text-base font-bold text-slate-900" x-text="videoType === 'imported_branded' ? 'Importing & Branding Video with FFmpeg' : (isAiMode ? 'Generating AI Fashion Shoot Video' : 'Rendering Video Studio Reel')"></h3>
                <p class="text-xs text-slate-600 mt-1 max-w-xs mx-auto"
                   x-text="videoType === 'imported_branded' ? 'FFmpeg is applying Dhansree branding, verified selling price, royal audio soundtrack, and end CTA.' : (isAiMode ? 'Google Veo AI is directing a moving model wearing the exact catalog garment.' : 'FFmpeg is composing high-definition slides, Ken Burns motion, luxury branding, and audio.')">
                </p>

                <!-- Dynamic Animated Progress Bar -->
                <div class="mt-5 space-y-1.5 text-left">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-semibold text-slate-700" x-text="currentStepText">Processing...</span>
                        <span class="font-mono font-bold text-amber-700" x-text="progressPercent + '%'"></span>
                    </div>
                    <div class="w-full h-3 rounded-full bg-slate-100 border border-slate-200 overflow-hidden relative">
                        <div class="h-full rounded-full transition-all duration-300 ease-out"
                             :style="'width: ' + progressPercent + '%; background: ' + (isAiMode ? 'linear-gradient(90deg, #7e22ce, #a855f7, #7e22ce)' : 'linear-gradient(90deg, #d97706, #f59e0b, #d97706)') + ';'">
                        </div>
                    </div>
                </div>

                <!-- Step Indicators -->
                <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-left text-xs">
                    <div class="flex items-center gap-2" :class="progressPercent >= 20 ? 'text-emerald-700 font-semibold' : 'text-slate-400'">
                        <span class="w-4 text-center font-bold" x-text="progressPercent >= 20 ? '✓' : '○'"></span>
                        <span x-text="isAiMode ? 'Uploading reference image to Google Veo' : 'Loading authentic product images'"></span>
                    </div>
                    <div class="flex items-center gap-2" :class="progressPercent >= 50 ? 'text-emerald-700 font-semibold' : (progressPercent >= 20 ? 'text-amber-700 font-medium' : 'text-slate-400')">
                        <span class="w-4 text-center font-bold" x-text="progressPercent >= 50 ? '✓' : (progressPercent >= 20 ? '➜' : '○')"></span>
                        <span x-text="isAiMode ? 'Synthesizing 4K model motion & garment physics' : 'Applying cinematic transitions & motion'"></span>
                    </div>
                    <div class="flex items-center gap-2" :class="progressPercent >= 75 ? 'text-emerald-700 font-semibold' : (progressPercent >= 50 ? 'text-amber-700 font-medium' : 'text-slate-400')">
                        <span class="w-4 text-center font-bold" x-text="progressPercent >= 75 ? '✓' : (progressPercent >= 50 ? '➜' : '○')"></span>
                        <span x-text="isAiMode ? 'Post-processing & preparing QC review card' : 'Overlaying Dhansree logo & price badge'"></span>
                    </div>
                    <div class="flex items-center gap-2" :class="progressPercent >= 100 ? 'text-emerald-700 font-semibold' : (progressPercent >= 75 ? 'text-amber-700 font-medium' : 'text-slate-400')">
                        <span class="w-4 text-center font-bold" x-text="progressPercent >= 100 ? '✓' : (progressPercent >= 75 ? '➜' : '○')"></span>
                        <span>Saving final MP4 output</span>
                    </div>
                </div>

                <p class="text-[11px] text-amber-800 font-medium bg-amber-50 rounded-lg p-2.5 mt-5 border border-amber-200">
                    ⏳ Processing runs in the background. You can keep this open or view progress on the dashboard.
                </p>
            </div>
        </div>
    </form>
</div>

<script>
function videoStudioCreator() {
    return {
        showProcessingModal: false,
        isSubmitting: false,
        progressPercent: 0,
        currentStepText: 'Initializing video generator...',
        progressTimer: null,
        statusPollTimer: null,
        videoType: 'slideshow',
        searchQuery: '',
        searchDropdownOpen: false,
        searchResults: [],
        selectedProductId: @json($selectedProduct?->id),
        selectedProduct: @json($selectedProductData),
        availableImages: @json($availableImagesData ?? []),
        selectedImageIds: @json(array_column($availableImagesData ?? [], 'id')),
        primaryImageId: @json($availableImagesData[0]['id'] ?? null),
        phpUploadLimitBytes: @json($phpUploadLimitBytes ?? 104857600),
        phpUploadLimitText: @json($phpUploadLimitText ?? '100M'),

        exportPreset: 'clean_storefront',
        selectedTemplate: 'fashion_showcase',
        selectedFashionStyle: 'runway_walk',
        selectedAspectRatio: '9:16',
        duration: 10,
        enableMusic: false,
        selectedAudioTrack: 'festive_royal',
        textOverlay: false,
        showPrice: false,
        callToAction: false,
        customTitle: '',

        setExportPreset(preset) {
            this.exportPreset = preset;
            if (preset === 'clean_storefront') {
                this.textOverlay = false;
                this.showPrice = false;
                this.enableMusic = false;
                this.callToAction = false;
            } else if (preset === 'social_branded' || preset === 'dual') {
                this.textOverlay = true;
                this.showPrice = true;
                this.enableMusic = true;
                this.callToAction = true;
            }
        },

        uploadedVideoFile: null,
        uploadedVideoName: '',
        uploadedVideoSize: '',
        uploadedVideoPreviewUrl: '',

        aiPrompt: '',
        aiNegativePrompt: @json($defaultNegativePrompt ?? ''),

        get isAiMode() {
            return this.videoType === 'ai_fashion' || this.videoType === 'ai_fashion_branded';
        },

        init() {
            if (this.selectedProduct && this.availableImages.length && !this.selectedImageIds.length) {
                this.selectedImageIds = this.availableImages.map(img => img.id);
                this.primaryImageId = this.availableImages[0]?.id || null;
            }
            if (this.selectedProductId) {
                this.fetchPrompt();
            }
        },

        searchProducts() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            fetch(`/admin/video-studio/products/search?q=${encodeURIComponent(this.searchQuery)}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                this.searchResults = data.products || [];
                this.searchDropdownOpen = true;
            })
            .catch(() => {
                this.searchResults = [];
            });
        },

        selectProduct(prod) {
            this.selectedProduct = prod;
            this.selectedProductId = prod.id;
            this.searchDropdownOpen = false;
            this.searchQuery = '';
            this.availableImages = prod.images || [];
            this.selectedImageIds = this.availableImages.map(img => img.id);
            this.primaryImageId = this.availableImages[0]?.id || null;
            if (!this.customTitle) {
                this.customTitle = prod.name;
            }
            this.fetchPrompt();
        },

        setPrimaryImage(id) {
            this.primaryImageId = id;
            if (!this.isSelected(id)) {
                this.selectedImageIds.unshift(id);
            }
        },

        fetchPrompt(forceReset = false) {
            if (!this.selectedProductId) return;
            if (!forceReset && this.aiPrompt && this.aiPrompt.length > 20) return;

            fetch('/admin/video-studio/preview-prompt', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
                },
                body: JSON.stringify({
                    product_id: this.selectedProductId,
                    style: this.selectedFashionStyle
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.prompt) {
                    this.aiPrompt = data.prompt;
                    if (data.negative_prompt && !this.aiNegativePrompt) {
                        this.aiNegativePrompt = data.negative_prompt;
                    }
                }
            })
            .catch(() => {});
        },

        clearProduct() {
            this.selectedProduct = null;
            this.selectedProductId = null;
            this.availableImages = [];
            this.selectedImageIds = [];
            this.primaryImageId = null;
            this.aiPrompt = '';
        },

        isSelected(imgId) {
            return this.selectedImageIds.includes(imgId);
        },

        toggleImage(imgId) {
            if (this.isSelected(imgId)) {
                this.selectedImageIds = this.selectedImageIds.filter(id => id !== imgId);
                if (this.primaryImageId === imgId) {
                    this.primaryImageId = this.selectedImageIds[0] || null;
                }
            } else {
                this.selectedImageIds.push(imgId);
                if (!this.primaryImageId) {
                    this.primaryImageId = imgId;
                }
            }
        },

        getOrderIndex(imgId) {
            const idx = this.selectedImageIds.indexOf(imgId);
            return idx !== -1 ? (idx + 1) : '';
        },

        moveImageLeft(imgId) {
            const idx = this.selectedImageIds.indexOf(imgId);
            if (idx > 0) {
                const temp = this.selectedImageIds[idx - 1];
                this.selectedImageIds[idx - 1] = imgId;
                this.selectedImageIds[idx] = temp;
                this.selectedImageIds = [...this.selectedImageIds];
            }
        },

        moveImageRight(imgId) {
            const idx = this.selectedImageIds.indexOf(imgId);
            if (idx !== -1 && idx < this.selectedImageIds.length - 1) {
                const temp = this.selectedImageIds[idx + 1];
                this.selectedImageIds[idx + 1] = imgId;
                this.selectedImageIds[idx] = temp;
                this.selectedImageIds = [...this.selectedImageIds];
            }
        },

        selectAllImages() {
            this.selectedImageIds = this.availableImages.map(i => i.id);
            if (!this.primaryImageId && this.availableImages.length) {
                this.primaryImageId = this.availableImages[0].id;
            }
        },

        handleVideoFileChange(e) {
            const file = e.target.files?.[0];
            if (!file) return;

            // Check hard max limit (60MB)
            if (file.size > 61440 * 1024) {
                alert('Video file is larger than 60MB. Please choose a smaller video.');
                e.target.value = '';
                return;
            }

            // Check if file exceeds the current server PHP upload_max_filesize limit
            if (this.phpUploadLimitBytes && file.size > this.phpUploadLimitBytes) {
                const fileSizeMb = (file.size / (1024 * 1024)).toFixed(1);
                alert(`⚠️ Server Upload Limit Warning:\n\nThis video is ${fileSizeMb} MB, which exceeds your server's current PHP upload_max_filesize limit (${this.phpUploadLimitText}).\n\nIf the upload fails, please run the fix command on your Lightsail server:\nsudo sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.3/fpm/php.ini\nsudo sed -i 's/^post_max_size = .*/post_max_size = 100M/' /etc/php/8.3/fpm/php.ini\nsudo systemctl restart php8.3-fpm`);
            }

            this.uploadedVideoFile = file;
            this.uploadedVideoName = file.name;
            this.uploadedVideoSize = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
            if (this.uploadedVideoPreviewUrl) {
                URL.revokeObjectURL(this.uploadedVideoPreviewUrl);
            }
            this.uploadedVideoPreviewUrl = URL.createObjectURL(file);
        },

        clearUploadedVideo() {
            this.uploadedVideoFile = null;
            this.uploadedVideoName = '';
            this.uploadedVideoSize = '';
            if (this.uploadedVideoPreviewUrl) {
                URL.revokeObjectURL(this.uploadedVideoPreviewUrl);
                this.uploadedVideoPreviewUrl = '';
            }
            const input = document.getElementById('uploaded_video_input');
            if (input) input.value = '';
        },

        handleFormSubmit(e) {
            if (e) e.preventDefault();
            if (this.isSubmitting) return;

            if (!this.selectedProductId) {
                alert("⚠️ Please select a catalog product first in Step 2.\n\nDhansree Video Studio needs a catalog product to brand its verified selling price & title onto the video, and to feature it on your storefront product & listing pages.");
                const searchEl = document.getElementById('product-search-input');
                if (searchEl) {
                    searchEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    searchEl.focus();
                }
                return;
            }

            if (this.videoType === 'imported_branded') {
                if (!this.uploadedVideoFile) {
                    alert("⚠️ Please select or browse your video file in Step 2b before clicking upload.\n\n(Choose the MP4 or MOV video you generated with Google Flow or recorded with your camera).");
                    const dropzoneEl = document.getElementById('uploaded_video_input');
                    if (dropzoneEl) {
                        dropzoneEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }
            } else if (this.selectedImageIds.length === 0) {
                alert("⚠️ Please select at least one genuine product image for the video in Step 2.");
                return;
            }

            this.isSubmitting = true;
            this.showProcessingModal = true;
            this.progressPercent = 15;
            this.currentStepText = this.videoType === 'imported_branded'
                ? 'Uploading video footage & preparing FFmpeg...'
                : (this.isAiMode ? 'Connecting to Google Veo API...' : 'Preparing authentic product photos...');

            const form = document.getElementById('create-video-form');
            const formData = new FormData(form);

            // Animate progress smoothly while server renders
            this.progressTimer = setInterval(() => {
                if (this.progressPercent < 85) {
                    this.progressPercent += 5;
                    if (this.videoType === 'imported_branded') {
                        if (this.progressPercent >= 25 && this.progressPercent < 50) {
                            this.currentStepText = 'Uploading footage & analyzing aspect ratio...';
                        } else if (this.progressPercent >= 50 && this.progressPercent < 75) {
                            this.currentStepText = 'Overlaying luxury Dhansree logo & verified price...';
                        } else if (this.progressPercent >= 75) {
                            this.currentStepText = 'Encoding H.264 branded reel with royal soundtrack...';
                        }
                    } else if (this.isAiMode) {
                        if (this.progressPercent >= 25 && this.progressPercent < 50) {
                            this.currentStepText = 'Synthesizing model motion & fabric physics...';
                        } else if (this.progressPercent >= 50 && this.progressPercent < 75) {
                            this.currentStepText = 'Rendering 4K photorealistic frames...';
                        } else if (this.progressPercent >= 75) {
                            this.currentStepText = 'Finalizing video & generating QC card...';
                        }
                    } else {
                        if (this.progressPercent >= 25 && this.progressPercent < 50) {
                            this.currentStepText = 'Composing video slides & transitions...';
                        } else if (this.progressPercent >= 50 && this.progressPercent < 75) {
                            this.currentStepText = 'Overlaying Dhansree logo & price badge...';
                        } else if (this.progressPercent >= 75) {
                            this.currentStepText = 'Encoding H.264 video with FFmpeg...';
                        }
                    }
                }
            }, 600);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
                },
                body: formData
            })
            .then(async res => {
                const data = await res.json().catch(() => null);
                if (!res.ok) {
                    throw new Error(data?.message || 'Server error occurred during video generation.');
                }
                return data;
            })
            .then(data => {
                if (data.success) {
                    if (data.video && data.video.is_completed) {
                        this.finishRendering(data.redirect);
                    } else if (data.status_url) {
                        this.pollStatus(data.status_url, data.redirect);
                    } else {
                        this.finishRendering(data.redirect);
                    }
                } else {
                    throw new Error(data.message || 'Video generation failed.');
                }
            })
            .catch(err => {
                clearInterval(this.progressTimer);
                if (this.statusPollTimer) clearInterval(this.statusPollTimer);
                this.isSubmitting = false;
                this.showProcessingModal = false;
                alert('Failed to generate video: ' + (err.message || 'Unknown error occurred.'));
            });
        },

        finishRendering(redirectUrl) {
            clearInterval(this.progressTimer);
            if (this.statusPollTimer) clearInterval(this.statusPollTimer);
            this.progressPercent = 100;
            this.currentStepText = '✓ Video generation ready! Redirecting...';
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 600);
        },

        pollStatus(statusUrl, redirectUrl) {
            this.statusPollTimer = setInterval(() => {
                fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.progress) {
                        this.progressPercent = Math.max(this.progressPercent, data.progress);
                    }
                    if (data.is_completed) {
                        this.finishRendering(redirectUrl);
                    } else if (data.is_failed) {
                        clearInterval(this.statusPollTimer);
                        clearInterval(this.progressTimer);
                        this.isSubmitting = false;
                        this.showProcessingModal = false;
                        alert('Video generation failed: ' + (data.error_message || 'Video failed to render.'));
                    }
                })
                .catch(() => {});
            }, 1500);
        },

        canSubmit() {
            if (!this.selectedProductId) return false;
            if (this.videoType === 'imported_branded') {
                return !!this.uploadedVideoFile;
            }
            return this.selectedImageIds.length > 0;
        }
    };
}
</script>
@endsection
