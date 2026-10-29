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
    <form action="{{ route('admin.video-studio.store') }}" method="POST" id="create-video-form">
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
                                    <svg class="size-4.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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
                                    <svg class="size-4.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
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

                    <!-- Search Input with AJAX Auto-complete -->
                    <div class="relative">
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.300ms="searchProducts()"
                               @focus="searchDropdownOpen = true"
                               placeholder="Type product name, SKU or keyword..."
                               class="w-full pl-10 pr-4 py-2.5 text-xs bg-white border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                        <svg class="size-4.5 text-slate-400 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>

                        <!-- Search Dropdown Results -->
                        <div x-show="searchDropdownOpen && searchResults.length > 0"
                             @click.outside="searchDropdownOpen = false"
                             class="absolute left-0 right-0 top-full mt-1.5 z-30 max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl divide-y divide-slate-100">
                            <template x-for="prod in searchResults" :key="prod.id">
                                <button type="button"
                                        @click="selectProduct(prod)"
                                        class="w-full text-left p-3 hover:bg-slate-50 transition-colors flex items-center gap-3">
                                    <div class="size-11 rounded-lg bg-slate-100 border border-slate-200 shrink-0 overflow-hidden flex items-center justify-center">
                                        <template x-if="prod.primary_image">
                                            <img :src="prod.primary_image" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!prod.primary_image">
                                            <svg class="size-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-slate-900 truncate" x-text="prod.name"></p>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            SKU: <span class="font-mono text-slate-700" x-text="prod.sku || 'N/A'"></span> •
                                            <span class="text-amber-700 font-semibold" x-text="prod.price"></span>
                                        </p>
                                    </div>
                                    <span class="text-[10px] font-medium px-2 py-0.5 rounded bg-slate-100 text-slate-600">
                                        <span x-text="prod.image_count"></span> images
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Hidden Product ID Field -->
                    <input type="hidden" name="product_id" :value="selectedProductId">

                    <!-- Selected Product Card Display -->
                    <div x-show="selectedProduct" class="mt-4 p-4 rounded-xl border border-amber-200 bg-amber-50/60 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="size-14 rounded-lg bg-white border border-amber-200 shrink-0 overflow-hidden shadow-2xs">
                                <img :src="selectedProduct?.primary_image" class="w-full h-full object-cover">
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

                        <button type="button" @click="clearProduct()" class="text-xs text-slate-500 hover:text-rose-600 font-medium underline shrink-0">
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
                                <span class="size-4 rounded-full bg-slate-200 shrink-0 overflow-hidden">
                                    <img src="{{ $rp->primary_image_url }}" class="w-full h-full object-cover">
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
                                <div class="absolute top-1.5 left-1.5 size-6 rounded-full flex items-center justify-center text-xs font-bold shadow-md cursor-pointer"
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

                    <button type="submit"
                            :disabled="!canSubmit()"
                            class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-rose-600 to-amber-600 hover:from-rose-700 hover:to-amber-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-md transition-all flex items-center justify-center gap-2">
                        <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <span>Render Video Now</span>
                    </button>
                    <p class="text-[10px] text-center text-slate-400">Processing runs safely in the background. Original product images are never altered.</p>
                </div>

            </div>

        </div>
    </form>
</div>

<script>
function videoStudioCreator() {
    return {
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

        canSubmit() {
            return this.selectedProductId && this.selectedImageIds.length > 0;
        }
    };
}
</script>
@endsection
