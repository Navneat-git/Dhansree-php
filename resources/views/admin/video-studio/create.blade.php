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

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 font-medium">Engine:</span>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $ffmpegAvailable ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                FFmpeg {{ $ffmpegAvailable ? 'Ready' : 'Not Detected' }}
            </span>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Create Promotional Video</h1>
            <p class="text-xs text-slate-500 mt-1">Select an existing catalog product, pick images, and render a high-quality video for Reels, Feed Ads, and Product Pages.</p>
        </div>
    </div>

    <!-- Main Form Form Grid -->
    <form action="{{ route('admin.video-studio.store') }}"
          method="POST"
          id="create-video-form"
          @keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }"
          @submit="return handleFormSubmit($event)">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left 2 Cols: Product & Image Selection + Templates + Settings -->
            <div class="lg:col-span-2 space-y-6">

                <!-- 1. Video Style Selector (Slideshow vs AI Motion) -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        1. Select Video Style
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- FFmpeg Slideshow (Default & Fully Functional) -->
                        <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all"
                               :class="videoType === 'slideshow' ? 'border-amber-500 bg-amber-50/70 ring-1 ring-amber-500/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                            <input type="radio" name="video_type" value="slideshow" x-model="videoType" class="sr-only">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <svg style="width: 18px; height: 18px;" class="w-4.5 h-4.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    Product Slideshow
                                </span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">Free &amp; Built-in</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-2 leading-relaxed">Smooth transitions, luxury ambient backdrop, Ken Burns framing, music, and Dhansree branding.</p>
                        </label>

                        <!-- AI Fashion Motion (Extensible future provider placeholder) -->
                        <div class="relative flex flex-col p-4 rounded-xl border border-slate-200 bg-slate-50/70 opacity-80">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-slate-600 flex items-center gap-2">
                                    <svg style="width: 18px; height: 18px;" class="w-4.5 h-4.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                                    </svg>
                                    AI Fashion Motion
                                </span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-200 text-slate-600">Coming Soon</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Extensible image-to-video AI motion generator. Requires an active API provider key in settings.</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Product Search & Selection -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        2. Search &amp; Select Product
                    </label>
                    <p class="text-xs text-slate-500 mb-4">Choose an active product from your catalog. Its existing genuine images will load automatically below.</p>

                    <!-- Search Input with AJAX Auto-complete & Search Button -->
                    <div class="relative">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input type="text"
                                       x-model="searchQuery"
                                       @input.debounce.300ms="searchProducts()"
                                       @keydown.enter.prevent="searchProducts()"
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
                                            style="background-color: #d97706 !important; color: #ffffff !important;"
                                            class="px-3 py-1.5 text-xs font-bold rounded-lg shadow-xs hover:opacity-90 transition-opacity shrink-0 flex items-center gap-1">
                                        <span>Select Product</span>
                                        <svg style="width: 14px; height: 14px;" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Hidden Product ID Field -->
                    <input type="hidden" name="product_id" :value="selectedProductId">

                    <!-- Selected Product Card Display -->
                    <div x-show="selectedProduct" class="mt-4 p-4 rounded-xl border border-amber-200 bg-amber-50/70 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div style="width: 56px; height: 56px; min-width: 56px; max-width: 56px;"
                                 class="w-14 h-14 rounded-lg bg-white border border-amber-300 shrink-0 overflow-hidden shadow-xs flex items-center justify-center">
                                <img :src="selectedProduct?.primary_image"
                                     style="width: 56px; height: 56px; min-width: 56px; max-width: 56px; object-fit: cover;"
                                     class="w-14 h-14 object-cover block">
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-600 text-white">Active Product</span>
                                    <span class="text-[11px] font-mono text-slate-500" x-text="'SKU: ' + (selectedProduct?.sku || 'N/A')"></span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 truncate mt-1" x-text="selectedProduct?.name"></h3>
                                <p class="text-xs text-amber-800 font-bold mt-0.5" x-text="selectedProduct?.price"></p>
                            </div>
                        </div>

                        <button type="button" @click="clearProduct()" class="text-xs text-rose-600 hover:text-rose-800 font-bold underline shrink-0">
                            Change Product
                        </button>
                    </div>

                    <!-- Quick Pick From Recent Active Products -->
                    <div x-show="!selectedProduct" class="mt-4">
                        <span class="text-[11px] font-semibold text-slate-500 block mb-2">Or select from recent catalog items:</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($recentProducts as $rp)
                            <button type="button"
                                    @click="loadProductById({{ $rp->id }})"
                                    class="px-2.5 py-1.5 text-xs bg-slate-50 border border-slate-200 hover:border-amber-400 hover:bg-amber-50/40 rounded-lg text-slate-700 hover:text-slate-900 transition-colors flex items-center gap-2 shadow-2xs">
                                <span style="width: 24px; height: 24px; min-width: 24px; max-width: 24px;"
                                      class="w-6 h-6 rounded-full bg-slate-200 shrink-0 overflow-hidden inline-flex items-center justify-center">
                                    <img src="{{ $rp->primary_image_url }}"
                                         style="width: 24px; height: 24px; object-fit: cover;"
                                         class="w-6 h-6 object-cover block">
                                </span>
                                <span class="truncate max-w-[140px] font-medium">{{ $rp->name }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- 3. Image Selection & Reordering -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs" x-show="availableImages.length > 0">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                3. Select &amp; Order Images for Video (<span x-text="selectedImageIds.length"></span> of <span x-text="availableImages.length"></span> selected)
                            </label>
                            <p class="text-xs text-slate-500 mt-0.5">Click an image to toggle inclusion. Use arrows to change playback sequence.</p>
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
                                 :class="isSelected(img.id) ? 'border-amber-500 ring-2 ring-amber-500/40 shadow-xs' : 'border-slate-200 opacity-60 hover:opacity-100'">

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

                                <!-- Move Left / Right Controls for Order -->
                                <template x-if="isSelected(img.id)">
                                    <div class="absolute bottom-1.5 inset-x-1.5 flex items-center justify-between gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900/80 rounded-md px-1 py-0.5 backdrop-blur-xs">
                                        <button type="button" @click.stop="moveImageLeft(img.id)" class="p-1 text-slate-300 hover:text-white" title="Move earlier">
                                            ‹
                                        </button>
                                        <span class="text-[9px] text-amber-300 font-mono" x-text="'#' + getOrderIndex(img.id)"></span>
                                        <button type="button" @click.stop="moveImageRight(img.id)" class="p-1 text-slate-300 hover:text-white" title="Move later">
                                            ›
                                        </button>
                                    </div>
                                </template>

                                <!-- Hidden inputs for selected order -->
                                <template x-if="isSelected(img.id)">
                                    <input type="hidden" name="image_ids[]" :value="img.id">
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Minimum 1 Image Warning -->
                    <div x-show="selectedImageIds.length === 0" class="mt-3 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-2">
                        <svg class="size-4 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                        <span>Please select at least one image to include in the slideshow video.</span>
                    </div>
                </div>

                <!-- 4. Video Template Selector -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        4. Choose Video Template
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

                <!-- 5. Aspect Ratio & Placement Presets -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                        5. Choose Export Aspect Ratio &amp; Channel
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

            <!-- Right Col: Video Customization & Live Summary Settings -->
            <div class="space-y-6">

                <!-- 6. Video Duration & Music -->
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

                    <!-- Background Music Toggle & Selector -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-700">Background Music</label>
                                <p class="text-[11px] text-slate-500">Add elegant royalty-free audio</p>
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

                <!-- 7. Text Overlays & CTA Toggles -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4 shadow-xs">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Brand &amp; Text Overlays
                    </label>

                    <!-- Text Overlay Toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-900">Product Title Overlay</span>
                            <p class="text-[11px] text-slate-500">Glassmorphic title bar at bottom</p>
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
                            <p class="text-[11px] text-slate-500">Display current selling price</p>
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
                            <p class="text-[11px] text-slate-500">"Shop Now at dhansree.com" closing</p>
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

                <!-- 8. Generation Summary & Launch Button -->
                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-5 space-y-4 shadow-xs">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-amber-900">Settings Summary</h4>

                    <div class="space-y-2 text-xs divide-y divide-amber-100">
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Target Product:</span>
                            <span class="font-bold text-slate-900 truncate max-w-[150px]" x-text="selectedProduct ? selectedProduct.name : 'None selected'"></span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Images:</span>
                            <span class="font-bold text-slate-900" x-text="selectedImageIds.length + ' frames'"></span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Aspect Ratio:</span>
                            <span class="font-mono font-bold text-amber-800" x-text="selectedAspectRatio"></span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Duration:</span>
                            <span class="font-bold text-slate-900" x-text="duration + 's'"></span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Audio:</span>
                            <span class="font-bold" :class="enableMusic ? 'text-emerald-700' : 'text-slate-500'" x-text="enableMusic ? 'Enabled' : 'Muted'"></span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-500">Closing CTA:</span>
                            <span class="font-bold" :class="callToAction ? 'text-emerald-700' : 'text-slate-500'" x-text="callToAction ? 'Shop Now' : 'Disabled'"></span>
                        </div>
                    </div>

                    <button type="button"
                            @click="handleFormSubmit($event)"
                            :disabled="!canSubmit() || isSubmitting"
                            style="background-color: #d97706 !important; color: #ffffff !important;"
                            class="w-full py-3.5 px-4 rounded-xl text-sm font-bold shadow-md hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <template x-if="!isSubmitting">
                            <span class="flex items-center gap-2">
                                <svg style="width: 18px; height: 18px;" class="w-4.5 h-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                <span style="color: #ffffff !important;">Render Video Now</span>
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <span class="flex items-center gap-2 text-white">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Rendering Video with FFmpeg...</span>
                            </span>
                        </template>
                    </button>
                    <p class="text-[10px] text-center text-slate-500 font-medium">Processing runs safely in the background. Original product images are never altered.</p>
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
                <!-- Subtle Gold Accent Header Bar -->
                <div class="absolute top-0 inset-x-0 h-1.5" style="background: linear-gradient(90deg, #b45309, #f59e0b, #b45309);"></div>

                <!-- Spinning Video Reel Icon -->
                <div style="width: 64px; height: 64px; min-width: 64px; max-width: 64px; background: linear-gradient(135deg, #d97706 0%, #92400e 100%) !important;"
                     class="w-16 h-16 rounded-2xl text-white mx-auto flex items-center justify-center shadow-lg mb-4">
                    <svg style="width: 32px; height: 32px;" class="w-8 h-8 text-white animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>

                <h3 class="text-base font-bold text-slate-900">Rendering Video Studio Reel</h3>
                <p class="text-xs text-slate-600 mt-1 max-w-xs mx-auto">
                    FFmpeg is composing high-definition slides, Ken Burns motion, luxury branding, and audio.
                </p>

                <!-- Dynamic Animated Progress Bar -->
                <div class="mt-5 space-y-1.5 text-left">
                    <div class="flex justify-between items-center text-xs">
                        <span class="font-semibold text-slate-700" x-text="currentStepText">Processing...</span>
                        <span class="font-mono font-bold text-amber-700" x-text="progressPercent + '%'"></span>
                    </div>
                    <div class="w-full h-3 rounded-full bg-slate-100 border border-slate-200 overflow-hidden relative">
                        <div class="h-full rounded-full transition-all duration-300 ease-out"
                             :style="'width: ' + progressPercent + '%; background: linear-gradient(90deg, #d97706, #f59e0b, #d97706);'">
                        </div>
                    </div>
                </div>

                <!-- Step Indicators -->
                <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-left text-xs">
                    <div class="flex items-center gap-2" :class="progressPercent >= 20 ? 'text-emerald-700 font-semibold' : 'text-slate-400'">
                        <span class="w-4 text-center font-bold" x-text="progressPercent >= 20 ? '✓' : '○'"></span>
                        <span>Loading authentic product images</span>
                    </div>
                    <div class="flex items-center gap-2" :class="progressPercent >= 50 ? 'text-emerald-700 font-semibold' : (progressPercent >= 20 ? 'text-amber-700 font-medium' : 'text-slate-400')">
                        <span class="w-4 text-center font-bold" x-text="progressPercent >= 50 ? '✓' : (progressPercent >= 20 ? '➜' : '○')"></span>
                        <span>Applying cinematic transitions &amp; motion</span>
                    </div>
                    <div class="flex items-center gap-2" :class="progressPercent >= 75 ? 'text-emerald-700 font-semibold' : (progressPercent >= 50 ? 'text-amber-700 font-medium' : 'text-slate-400')">
                        <span class="w-4 text-center font-bold" x-text="progressPercent >= 75 ? '✓' : (progressPercent >= 50 ? '➜' : '○')"></span>
                        <span>Overlaying Dhansree logo &amp; price badge</span>
                    </div>
                    <div class="flex items-center gap-2" :class="progressPercent >= 100 ? 'text-emerald-700 font-semibold' : (progressPercent >= 75 ? 'text-amber-700 font-medium' : 'text-slate-400')">
                        <span class="w-4 text-center font-bold" x-text="progressPercent >= 100 ? '✓' : (progressPercent >= 75 ? '➜' : '○')"></span>
                        <span>Synthesizing audio &amp; saving H.264 MP4</span>
                    </div>
                </div>

                <!-- Warning note -->
                <p class="text-[11px] text-amber-800 font-medium bg-amber-50 rounded-lg p-2.5 mt-5 border border-amber-200">
                    ⏳ Processing takes just a few seconds. Please keep this window open while your video is rendered.
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

        selectedTemplate: 'fashion_showcase',
        selectedAspectRatio: '9:16',
        duration: 10,
        enableMusic: true,
        selectedAudioTrack: 'festive_royal',
        textOverlay: true,
        showPrice: true,
        callToAction: true,
        customTitle: '',

        init() {
            if (this.selectedProduct && this.availableImages.length && !this.selectedImageIds.length) {
                this.selectedImageIds = this.availableImages.map(img => img.id);
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
            if (!this.customTitle) {
                this.customTitle = prod.name;
            }
        },

        loadProductById(productId) {
            fetch(`/admin/video-studio/products/${productId}/images`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.product) {
                    this.selectProduct({
                        id: data.product.id,
                        name: data.product.name,
                        sku: data.product.sku,
                        price: data.product.price,
                        primary_image: data.images[0]?.url || '',
                        images: data.images || []
                    });
                }
            })
            .catch(() => {});
        },

        clearProduct() {
            this.selectedProduct = null;
            this.selectedProductId = null;
            this.availableImages = [];
            this.selectedImageIds = [];
        },

        isSelected(imgId) {
            return this.selectedImageIds.includes(imgId);
        },

        toggleImage(imgId) {
            if (this.isSelected(imgId)) {
                this.selectedImageIds = this.selectedImageIds.filter(id => id !== imgId);
            } else {
                this.selectedImageIds.push(imgId);
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
        },

        clearAllImages() {
            this.selectedImageIds = [];
        },

        handleFormSubmit(e) {
            if (e) e.preventDefault();
            if (this.isSubmitting) return;

            if (!this.selectedProductId) {
                alert('Please search and select a catalog product first.');
                return;
            }
            if (this.selectedImageIds.length === 0) {
                alert('Please select at least one genuine product image for the video.');
                return;
            }

            this.isSubmitting = true;
            this.showProcessingModal = true;
            this.progressPercent = 15;
            this.currentStepText = 'Preparing authentic product photos...';

            const form = document.getElementById('create-video-form');
            const formData = new FormData(form);

            // Animate progress smoothly while server renders
            this.progressTimer = setInterval(() => {
                if (this.progressPercent < 85) {
                    this.progressPercent += 5;
                    if (this.progressPercent >= 25 && this.progressPercent < 50) {
                        this.currentStepText = 'Composing video slides & transitions...';
                    } else if (this.progressPercent >= 50 && this.progressPercent < 75) {
                        this.currentStepText = 'Overlaying Dhansree logo & price badge...';
                    } else if (this.progressPercent >= 75) {
                        this.currentStepText = 'Encoding H.264 video with FFmpeg...';
                    }
                }
            }, 550);

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
                    throw new Error(data?.message || 'Server error occurred during video rendering.');
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
            this.currentStepText = '✓ Video generated successfully! Redirecting...';
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
                        alert('Video rendering failed: ' + (data.error_message || 'FFmpeg failed to encode video.'));
                    }
                })
                .catch(() => {});
            }, 1200);
        },

        canSubmit() {
            return this.selectedProductId && this.selectedImageIds.length > 0;
        }
    };
}
</script>
@endsection
