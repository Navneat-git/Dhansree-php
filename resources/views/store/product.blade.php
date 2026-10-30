@extends('layouts.store')

@push('head')
<!-- Product Schema.org JSON-LD -->
<script type="application/ld+json">
@php
    $schemaData = [
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $product->name,
        'image' => $product->images->pluck('url')->all(),
        'description' => strip_tags((string) ($product->short_description ?? $product->description)),
        'sku' => $product->variants->first()?->sku ?? $product->slug,
        'brand' => [
            '@type' => 'Brand',
            'name' => $product->brand ?? 'Dhansree',
        ],
        'offers' => [
            '@type' => 'Offer',
            'url' => url()->current(),
            'priceCurrency' => 'INR',
            'price' => $product->min_price_rupees ?? 0,
            'availability' => 'https://schema.org/InStock',
        ],
    ];

    if (!empty($reviewSummary)) {
        $schemaData['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $reviewSummary['average'],
            'reviewCount' => $reviewSummary['count'],
            'bestRating' => 5,
            'worstRating' => 1,
        ];
        if (!empty($initialReviews)) {
            $schemaData['review'] = collect($initialReviews)->take(5)->map(fn($r) => [
                '@type' => 'Review',
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $r['rating'],
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
                'author' => [
                    '@type' => 'Person',
                    'name' => $r['author_name'],
                ],
                'reviewBody' => $r['body'],
                'datePublished' => $r['published_at'],
            ])->all();
        }
    }
@endphp
{!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
@php
    $liveVariants = $product->variants->whereNull('archived_at');
    $colorOption = $product->options->first(fn($o) => in_array(strtolower($o->name), ['color', 'colour']));
    $sizeOption = $product->options->first(fn($o) => in_array(strtolower($o->name), ['size']));

    $variantsMatrix = [];
    $colorsList = [];

    // Map each image and determine its pinned color associations
    $preparedImages = [];
    foreach ($product->images as $pos => $img) {
        $assignedColors = [];
        if ($colorOption) {
            foreach ($colorOption->values as $cVal) {
                $cValName = trim($cVal->value);
                $colorCode = \App\Models\WholesaleColorCode::resolveCode($cValName);

                $isPinnedToOption = $img->productImageOptionValues->contains('option_value_id', $cVal->id);

                $varsForColor = $liveVariants->filter(fn($v) => $v->optionValues->contains('id', $cVal->id));
                if ($varsForColor->isEmpty()) {
                    $varsForColor = $liveVariants->filter(function ($v) use ($colorCode, $cValName) {
                        $sku = strtoupper($v->sku);
                        return ($colorCode !== '' && str_contains($sku, '-' . $colorCode)) || str_contains($sku, strtoupper($cValName));
                    });
                }
                $varIds = $varsForColor->pluck('id')->all();
                $isPinnedToVariant = !empty($varIds) && $img->productImageVariants->whereIn('variant_id', $varIds)->isNotEmpty();

                if ($isPinnedToOption || $isPinnedToVariant) {
                    $assignedColors[] = $cValName;
                }
            }
        }
        $preparedImages[] = [
            'id' => $img->id,
            'url' => $img->url,
            'alt' => $img->alt_text ?? $product->name,
            'position' => $img->position,
            'is_primary' => ($pos === 0 || $img->position === 0),
            'is_actual' => (bool) $img->is_actual_product_image,
            'colors' => array_values(array_unique($assignedColors)),
        ];
    }

    $colorHexMap = [
        'blue' => '#2563eb',
        'navy' => '#1e3a8a',
        'yellow' => '#eab308',
        'mustard' => '#ca8a04',
        'red' => '#dc2626',
        'maroon' => '#881337',
        'green' => '#16a34a',
        'white' => '#ffffff',
        'off-white' => '#fafaf9',
        'black' => '#0f172a',
        'pink' => '#ec4899',
        'purple' => '#9333ea',
        'orange' => '#ea580c',
        'grey' => '#64748b',
        'gray' => '#64748b',
        'beige' => '#d6c7b2',
        'peach' => '#ffcba4',
        'teal' => '#0d9488',
        'rust' => '#b45309',
        'gold' => '#d97706',
    ];

    if ($colorOption) {
        foreach ($colorOption->values as $cVal) {
            $cName = $cVal->value;
            $colorCode = \App\Models\WholesaleColorCode::resolveCode($cName);

            $varsForColor = $liveVariants->filter(function($v) use ($cVal) {
                return $v->optionValues->contains('id', $cVal->id);
            });
            if ($varsForColor->isEmpty()) {
                $varsForColor = $liveVariants->filter(function ($v) use ($colorCode, $cName) {
                    $sku = strtoupper($v->sku);
                    return ($colorCode !== '' && str_contains($sku, '-' . $colorCode)) || str_contains($sku, strtoupper($cName));
                });
            }
            if ($varsForColor->isEmpty()) {
                continue;
            }

            $varIds = $varsForColor->pluck('id')->all();
            $colorImage = $product->images->first(function($img) use ($cVal, $varIds) {
                return $img->productImageOptionValues->contains('option_value_id', $cVal->id)
                    || (!empty($varIds) && $img->productImageVariants->whereIn('variant_id', $varIds)->isNotEmpty());
            }) ?? $product->images->first();

            $hexKey = strtolower(trim($cName));

            $colorsList[] = [
                'id' => $cVal->id,
                'name' => $cName,
                'hex' => $colorHexMap[$hexKey] ?? (\App\Models\WholesaleColorCode::resolveHex($cName) ?: '#94a3b8'),
                'thumbnail' => $colorImage?->url,
                'imageUrl' => $colorImage?->url,
            ];
        }
    }

    foreach ($liveVariants as $variant) {
        $colorVal = $colorOption ? $variant->optionValues->firstWhere('option_id', $colorOption->id)?->value : null;
        $sizeVal = $sizeOption ? $variant->optionValues->firstWhere('option_id', $sizeOption->id)?->value : null;
        if (!$sizeVal) {
            $sizeVal = $variant->display_option_label;
        }

        $stock = $variant->inventoryLevels->sum('on_hand') - $variant->inventoryLevels->sum('reserved');
        $effectivePrice = $variant->price_rupees > 0 ? $variant->price_rupees : ($product->min_price_rupees ?: 0);
        $effectiveMrp = $variant->mrp_rupees > 0 ? $variant->mrp_rupees : ($product->mrp_rupees ?: 0);
        $store = \App\Models\StoreSettings::current();
        $calc = $store->calculateDiscountedPrice($effectivePrice, $effectiveMrp);

        $variantsMatrix[] = [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'color' => $colorVal ?? '',
            'size' => $sizeVal,
            'price' => $calc['formatted_price'],
            'mrp' => $calc['formatted_mrp'] ?? '',
            'discount' => $calc['discount_percentage'] ? $calc['discount_percentage'] . '% OFF' : '',
            'inStock' => $stock > 0,
            'stock' => $stock,
        ];
    }

    // Determine initial color and size, PREFERRING requested ?color=... parameter or in-stock variants!
    $requestedColorParam = trim((string) request()->query('color', ''));
    $firstColor = !empty($colorsList) ? $colorsList[0]['name'] : '';

    if ($requestedColorParam !== '' && !empty($colorsList)) {
        $matchedColorItem = collect($colorsList)->first(function($c) use ($requestedColorParam) {
            return strcasecmp($c['name'], $requestedColorParam) === 0
                || strcasecmp(\App\Models\WholesaleColorCode::resolveCode($c['name']), $requestedColorParam) === 0;
        });
        if ($matchedColorItem) {
            $firstColor = $matchedColorItem['name'];
        }
    }

    $firstSize = '';
    $defaultVariant = null;

    if ($firstColor) {
        $colorMatches = collect($variantsMatrix)->where('color', $firstColor);
        $firstMatch = $colorMatches->firstWhere('inStock', true) ?? $colorMatches->first();
        if ($firstMatch) {
            $firstSize = $firstMatch['size'];
            $defaultVariant = $liveVariants->firstWhere('id', $firstMatch['id']);
        }
    } else {
        $firstMatch = collect($variantsMatrix)->firstWhere('inStock', true) ?? ($variantsMatrix[0] ?? null);
        if ($firstMatch) {
            $firstSize = $firstMatch['size'];
            $defaultVariant = $liveVariants->firstWhere('id', $firstMatch['id']);
        }
    }

    if (!$defaultVariant) {
        $defaultVariant = $liveVariants->first();
    }

    // Prepare initial images array prioritized for firstColor
    $initialImages = $preparedImages;
    if (!empty($firstColor)) {
        $colorImages = collect($preparedImages)->filter(function($img) use ($firstColor) {
            return collect($img['colors'])->contains(function($c) use ($firstColor) {
                return strcasecmp($c, $firstColor) === 0
                    || strcasecmp(\App\Models\WholesaleColorCode::resolveCode($c), $firstColor) === 0;
            });
        })->values();

        if ($colorImages->isNotEmpty()) {
            $commonImages = collect($preparedImages)->filter(function($img) {
                return empty($img['colors']);
            })->values();

            $initialImages = $colorImages->concat($commonImages)->all();
        }
    }

    // Resolve initial image corresponding to the selected color
    $selectedColorItem = !empty($firstColor) ? collect($colorsList)->firstWhere('name', $firstColor) : null;
    $firstImg = $selectedColorItem['imageUrl'] ?? null;

    if (!$firstImg && !empty($initialImages)) {
        $firstImg = $initialImages[0]['url'];
    }

    if (!$firstImg && $defaultVariant) {
        $firstImg = $product->images->first(function($img) use ($defaultVariant) {
            return $img->productImageVariants->contains('variant_id', $defaultVariant->id);
        })?->url;
    }

    if (!$firstImg) {
        $firstImg = $product->primary_image_url ?? $product->images->first()?->url;
    }

    $approvedVideo = $product->getApprovedVideoForColor($firstColor) ?? $product->approved_video;
    $allProductVideos = $product->videos->filter(fn($v) => $v->status === 'completed' && $v->isApproved());
    $videosByColor = [];
    if ($colorOption) {
        foreach ($colorOption->values as $cVal) {
            $cVid = $allProductVideos->first(fn($v) => $v->isPinnedToOptionValue($cVal->id)) ?? $product->approved_video;
            if ($cVid) {
                $videosByColor[strtolower(trim($cVal->value))] = [
                    'id' => $cVid->id,
                    'video_url' => $cVid->video_url,
                    'webm_url' => $cVid->webm_url,
                    'thumbnail_url' => $cVid->thumbnail_url,
                    'duration' => $cVid->duration_seconds ?? 8,
                    'is_ai_generated' => (bool) $cVid->isAiGenerated(),
                ];
            }
        }
    }
@endphp

@push('head')
    @if(!empty($firstImg))
        <meta property="og:image" content="{{ $firstImg }}">
        <meta name="twitter:image" content="{{ $firstImg }}">
    @endif
@endpush

@include('store.partials.quick-links')

<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-8 md:py-12 w-full max-w-full overflow-hidden"
     x-data="{
        allImages: @js($preparedImages),
        videosByColor: @js($videosByColor),
        currentVideo: @js($approvedVideo ? [
            'id' => $approvedVideo->id,
            'video_url' => $approvedVideo->video_url,
            'webm_url' => $approvedVideo->webm_url,
            'thumbnail_url' => $approvedVideo->thumbnail_url,
            'duration' => $approvedVideo->duration_seconds ?? 8,
            'is_ai_generated' => (bool) $approvedVideo->isAiGenerated(),
        ] : null),
        activeIdx: 0,
        expanded: false,
        activeImage: '{{ $firstImg }}',
        zoomModalOpen: false,
        videoModalOpen: false,
        sizeChartOpen: false,
        sizeChartZoomOpen: false,
        chartZoomScale: 1,
        zoomInChart() {
            if (this.chartZoomScale < 3) {
                this.chartZoomScale = Math.min(3, +(this.chartZoomScale + 0.5).toFixed(1));
            }
        },
        zoomOutChart() {
            if (this.chartZoomScale > 1) {
                this.chartZoomScale = Math.max(1, +(this.chartZoomScale - 0.5).toFixed(1));
            }
        },
        resetChartZoom() {
            this.chartZoomScale = 1;
        },
        toggleChartZoom() {
            this.chartZoomScale = this.chartZoomScale > 1 ? 1 : 2;
        },
        variantsMatrix: @js($variantsMatrix),
        colors: @js($colorsList),
        hasColors: {{ !empty($colorsList) ? 'true' : 'false' }},
        selectedColor: '{{ $firstColor }}',
        selectedSize: '{{ $firstSize }}',
        selectedVariantId: {{ $defaultVariant?->id ?? 'null' }},
        selectedVariantPrice: '{{ $firstMatch['price'] ?? $product->formatted_price }}',
        selectedVariantMrp: '{{ $firstMatch['mrp'] ?? ($product->formatted_mrp ?? '') }}',
        selectedVariantDiscount: '{{ $firstMatch['discount'] ?? ($product->discount_percentage ? $product->discount_percentage . '% OFF' : '') }}',
        inStock: {{ ($defaultVariant?->inventoryLevels->sum('on_hand') ?? 1) > 0 ? 'true' : 'false' }},
        quantity: 1,
        activeTab: 'description',
        get visibleImages() {
            if (!this.selectedColor) return this.allImages;
            const sel = (this.selectedColor || '').trim().toLowerCase();
            const forColor = this.allImages.filter(img => img.colors && img.colors.some(c => c.trim().toLowerCase() === sel));
            if (forColor.length > 0) {
                const common = this.allImages.filter(img => !img.colors || img.colors.length === 0);
                return [...forColor, ...common];
            }
            return this.allImages;
        },
        updateScroll(el) {
            const width = el.clientWidth;
            if (width > 0) {
                this.activeIdx = Math.round(el.scrollLeft / width);
            }
        },
        scrollToImage(idx) {
            const track = this.$refs.galleryTrack;
            if (track && track.clientWidth > 0) {
                track.scrollTo({ left: track.clientWidth * idx, behavior: 'smooth' });
                this.activeIdx = idx;
            }
        },
        selectColor(colorName) {
            this.selectedColor = colorName;
            this.activeIdx = 0;
            this.expanded = false;

            if (window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.set('color', colorName);
                window.history.replaceState({}, '', url.toString());
            }

            if (this.videosByColor && colorName) {
                const key = colorName.toLowerCase().trim();
                if (this.videosByColor[key]) {
                    this.currentVideo = this.videosByColor[key];
                } else {
                    this.currentVideo = @js($product->approved_video ? [
                        'id' => $product->approved_video->id,
                        'video_url' => $product->approved_video->video_url,
                        'webm_url' => $product->approved_video->webm_url,
                        'thumbnail_url' => $product->approved_video->thumbnail_url,
                        'duration' => $product->approved_video->duration_seconds ?? 8,
                        'is_ai_generated' => (bool) $product->approved_video->isAiGenerated(),
                    ] : null);
                }
            }

            this.$nextTick(() => {
                if (this.visibleImages.length > 0) {
                    this.activeImage = this.visibleImages[0].url;
                }
                const track = this.$refs.galleryTrack;
                if (track) {
                    track.scrollTo({ left: 0, behavior: 'instant' });
                }
            });

            const sizes = this.getSizesForColor(colorName);
            if (!sizes.some(s => s.size === this.selectedSize)) {
                if (sizes.length > 0) {
                    const inStockSize = sizes.find(s => s.inStock);
                    this.selectSize(inStockSize ? inStockSize.size : sizes[0].size);
                }
            } else {
                this.updateSelectedVariant();
            }
        },
        selectSize(sizeName) {
            this.selectedSize = sizeName;
            this.updateSelectedVariant();
        },
        getSizesForColor(colorName) {
            if (!this.hasColors) return this.variantsMatrix;
            return this.variantsMatrix.filter(v => v.color === colorName);
        },
        updateSelectedVariant() {
            let found = null;
            if (this.hasColors) {
                found = this.variantsMatrix.find(v => v.color === this.selectedColor && v.size === this.selectedSize);
            } else {
                found = this.variantsMatrix.find(v => v.size === this.selectedSize);
            }
            if (found) {
                this.selectedVariantId = found.id;
                this.selectedVariantPrice = (found.price && found.price !== '₹0') ? found.price : '{{ $product->formatted_price }}';
                this.selectedVariantMrp = found.mrp;
                this.selectedVariantDiscount = found.discount;
                this.inStock = found.inStock;
            }
        },
        isBuying: false,
        async buyNowFromReel() {
            this.isBuying = true;
            this.videoModalOpen = false;
            if (this.$refs.modalVideo) this.$refs.modalVideo.pause();

            const vId = this.selectedVariantId || (this.variantsMatrix && this.variantsMatrix[0] ? this.variantsMatrix[0].id : null);
            if (!vId) {
                window.location.href = '{{ route('cart.index') }}';
                return;
            }

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                await fetch('{{ route('cart.add') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ variant_id: vId, quantity: 1 })
                });
                window.location.href = '{{ route('cart.index') }}';
            } catch (e) {
                window.location.href = '{{ route('cart.index') }}';
            }
        }
     }">

    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-rich-black/50 mb-8 overflow-x-auto whitespace-nowrap w-full max-w-full min-w-0 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <a href="{{ $isSmartLivingHub ? url('/smart-living') : url('/') }}" class="hover:text-maroon-800 transition-colors shrink-0">
            {{ $isSmartLivingHub ? 'Smart Living' : 'Home' }}
        </a>
        <span>/</span>
        @if($product->category)
            <a href="{{ url('/' . $product->category->slug) }}" class="hover:text-maroon-800 transition-colors shrink-0">
                {{ $product->category->name }}
            </a>
            <span>/</span>
        @endif
        <span class="text-rich-black/80 font-medium truncate max-w-xs shrink-0">{{ $product->name }}</span>
    </nav>

    <!-- Main Product Grid: Gallery (2-column desktop grid / mobile swipe) + Details -->
    <div class="grid grid-cols-1 {{ $isSmartLivingHub ? 'lg:grid-cols-12' : 'lg:grid-cols-2' }} gap-8 lg:gap-12 items-start w-full max-w-full">
        
        <!-- Left: Image Gallery (All images displayed together in 2-column grid on desktop, swipeable on mobile) -->
        <div class="min-w-0 w-full max-w-full overflow-hidden {{ $isSmartLivingHub ? 'lg:col-span-7' : '' }}">
            @php $hasApprovedVideo = (bool) $approvedVideo; @endphp
            
            <ul x-ref="galleryTrack"
                @scroll.passive="updateScroll($el)"
                aria-label="{{ $product->name }} pictures"
                class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth w-full max-w-full [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden lg:grid lg:grid-cols-2 lg:gap-3 lg:overflow-x-visible lg:scroll-auto">
                
                @if($approvedVideo)
                    <li class="w-full shrink-0 snap-center" x-show="currentVideo">
                        <div class="relative {{ $isSmartLivingHub ? 'aspect-square' : 'aspect-[4/5]' }} overflow-hidden rounded-2xl border border-light-gold/60 bg-black group shadow-xs"
                             x-data="{ isPlaying: false, isMuted: true, showDisclaimer: false }"
                             @mouseleave="showDisclaimer = false">
                            <video x-ref="galleryVideo"
                                   :poster="currentVideo?.thumbnail_url"
                                   :src="currentVideo?.webm_url || currentVideo?.video_url"
                                   poster="{{ $approvedVideo->thumbnail_url }}"
                                   playsinline
                                   loop
                                   muted
                                   preload="metadata"
                                   class="w-full h-full object-cover bg-black cursor-pointer"
                                   @play="isPlaying = true"
                                   @pause="isPlaying = false"
                                   @click="if ($refs.galleryVideo.paused) { $refs.galleryVideo.play(); } else { $refs.galleryVideo.pause(); }">
                                @if($approvedVideo->webm_url)
                                    <source src="{{ $approvedVideo->webm_url }}" type="video/webm">
                                @endif
                                <source src="{{ $approvedVideo->video_url }}" type="video/mp4">
                            </video>

                            <!-- Top-Right: AI Video Disclaimer (ⓘ) Button -->
                            <div x-show="Boolean(currentVideo ? currentVideo.is_ai_generated : {{ $approvedVideo?->isAiGenerated() ? 'true' : 'false' }})"
                                 class="absolute top-2.5 right-2.5 z-20 pointer-events-auto">
                                <button type="button"
                                        @mouseenter="showDisclaimer = true"
                                        @click.stop="showDisclaimer = !showDisclaimer"
                                        title="AI-generated video for illustration purposes only. Actual product details may vary; please refer to the actual product images for accurate details."
                                        aria-label="AI Video Disclaimer"
                                        class="size-7 rounded-full bg-slate-900/90 hover:bg-slate-900 text-amber-300 hover:text-amber-200 border border-amber-400/50 flex items-center justify-center shadow-md transition-all cursor-pointer backdrop-blur-md focus:outline-none">
                                    <span class="font-bold text-sm leading-none select-none">ⓘ</span>
                                </button>
                            </div>

                            <!-- AI Video Disclaimer Tooltip Banner (Safely centered within card, never clipped by overflow-hidden) -->
                            <div x-show="showDisclaimer"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 translate-y-1"
                                 @mouseenter="showDisclaimer = true"
                                 @click.outside="showDisclaimer = false"
                                 @click.stop
                                 class="absolute top-11 z-30 pointer-events-auto p-3 rounded-xl bg-slate-950/95 text-slate-100 backdrop-blur-md border border-amber-500/50 text-[11px] leading-relaxed shadow-2xl"
                                 style="left: 10px; right: 10px; width: calc(100% - 20px);">
                                <div class="flex items-start gap-2">
                                    <span class="size-4 rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/40 flex items-center justify-center text-[10px] font-serif italic shrink-0 mt-0.5">i</span>
                                    <p class="text-slate-100 font-normal">
                                        AI-generated video for illustration purposes only. Actual product details may vary; please refer to the actual product images for accurate details.
                                    </p>
                                </div>
                            </div>

                            <!-- Play/Pause Overlay Button -->
                            <div class="absolute inset-0 flex items-center justify-center transition-opacity pointer-events-none"
                                 :class="isPlaying ? 'opacity-0 group-hover:opacity-100' : 'opacity-100'">
                                <button type="button"
                                        @click.stop="if ($refs.galleryVideo.paused) { $refs.galleryVideo.play(); } else { $refs.galleryVideo.pause(); }"
                                        class="size-11 rounded-full bg-black/60 backdrop-blur-md text-white flex items-center justify-center shadow-lg hover:scale-110 transition-transform pointer-events-auto cursor-pointer border border-white/20">
                                    <svg class="size-5 fill-white translate-x-0.5" x-show="!isPlaying" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    <svg class="size-5 fill-white" x-show="isPlaying" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                                </button>
                            </div>

                            <!-- Bottom Controls: Unmute (Left) + Model Motion (Right) -->
                            <div class="absolute bottom-2.5 inset-x-2.5 flex items-center justify-between z-10 pointer-events-none">
                                <!-- Bottom-Left: Unmute Button -->
                                <button type="button"
                                        @click.stop="$refs.galleryVideo.muted = !$refs.galleryVideo.muted; isMuted = $refs.galleryVideo.muted;"
                                        class="px-2 py-0.5 rounded-md bg-black/70 hover:bg-black/90 backdrop-blur-xs text-white text-[10px] font-medium flex items-center gap-1 transition-colors shadow-xs pointer-events-auto cursor-pointer">
                                    <span x-text="isMuted ? '🔇 Unmute' : '🔊 Mute'"></span>
                                </button>

                                <!-- Bottom-Right: Model Motion Badge (Opens Reel Lightbox) -->
                                <button type="button"
                                        @click.stop="videoModalOpen = true; $refs.modalVideo?.play()"
                                        title="Watch full screen model reel"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-900/90 hover:bg-slate-900 backdrop-blur-md text-amber-300 border border-amber-400/40 text-[10px] font-bold shadow-md transition-all pointer-events-auto cursor-pointer">
                                    <span class="size-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                    <span>Model Motion</span>
                                    <span class="text-[9px] font-mono text-slate-300" x-text="'(' + (currentVideo?.duration || {{ $approvedVideo->duration_seconds ?? 8 }}) + 's)'">({{ $approvedVideo->duration_seconds ?? 8 }}s)</span>
                                    <span class="text-amber-400 text-[10px]">➔</span>
                                </button>
                            </div>
                        </div>
                    </li>
                @endif
                
                <template x-for="(img, idx) in visibleImages" :key="img.id + '-' + (selectedColor || 'all')">
                    <li class="w-full shrink-0 snap-center"
                        :class="{
                            'lg:hidden': (!expanded && (idx + {{ $hasApprovedVideo ? 1 : 0 }}) >= 6)
                        }">
                        <div class="relative {{ $isSmartLivingHub ? 'aspect-square' : 'aspect-[4/5]' }} overflow-hidden rounded-2xl border border-light-gold/60 bg-white group cursor-zoom-in"
                             x-data="{ isHovered: false, origin: '50% 50%' }"
                             @mousemove="
                                 if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                                     const box = $el.getBoundingClientRect();
                                     const x = (($event.clientX - box.left) / box.width) * 100;
                                     const y = (($event.clientY - box.top) / box.height) * 100;
                                     origin = x + '% ' + y + '%';
                                     isHovered = true;
                                 }
                             "
                             @mouseleave="isHovered = false"
                             @click="activeImage = img.url; zoomModalOpen = true;">
                            
                            <div class="absolute inset-0 transition-transform duration-200 ease-out"
                                 :style="isHovered ? 'transform: scale(2.2); transform-origin: ' + origin + ';' : ''">
                                <img :src="img.url"
                                     :alt="img.alt"
                                     :loading="idx === 0 ? 'eager' : 'lazy'"
                                     class="w-full h-full object-contain">
                            </div>

                            <!-- Actual Product Image Badge -->
                            <template x-if="img.is_actual">
                                <div class="absolute top-2.5 left-2.5 z-10 pointer-events-none">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-semibold tracking-wide shadow-xs border"
                                          style="background-color: #FBFAF8; color: #580000; border-color: #B08858;">
                                        <svg class="w-3 h-3 shrink-0" style="color: #B08858;" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Actual Product Image</span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </li>
                </template>

                <!-- Blank Card for Odd Counts (1, 3, 5, 7, 9 etc.) so all cards maintain identical small size -->
                <li x-show="((visibleImages.length + {{ $hasApprovedVideo ? 1 : 0 }}) % 2 === 1) && (expanded || (visibleImages.length + {{ $hasApprovedVideo ? 1 : 0 }}) < 6)"
                    class="hidden lg:block w-full shrink-0">
                    <div class="relative {{ $isSmartLivingHub ? 'aspect-square' : 'aspect-[4/5]' }} rounded-2xl border border-dashed border-light-gold/60 bg-warm-beige/20 flex flex-col items-center justify-center p-6 text-center select-none">
                        <div class="w-10 h-10 rounded-full border border-light-gold/60 bg-white/60 flex items-center justify-center text-maroon-800 font-serif font-bold text-sm mb-1.5 shadow-xs">
                            D
                        </div>
                        <span class="font-serif text-[11px] uppercase tracking-widest text-rich-black/35 font-semibold">Dhansree</span>
                        <span class="text-[9px] uppercase tracking-wider text-rich-black/25 mt-0.5">Jaipur Ethnic Elegance</span>
                    </div>
                </li>

                <template x-if="visibleImages.length === 0 && !{{ $hasApprovedVideo ? 'true' : 'false' }}">
                    <li class="w-full lg:col-span-2 {{ $isSmartLivingHub ? 'aspect-square' : 'aspect-[4/5]' }} rounded-2xl border border-light-gold/60 bg-warm-beige/30 grid place-items-center text-rich-black/40">
                        <svg class="w-12 h-12 stroke-current" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                    </li>
                </template>
            </ul>

            <!-- Fallback for non-JS / crawlers -->
            <noscript>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    @forelse($initialImages as $position => $img)
                        <div class="relative {{ $isSmartLivingHub ? 'aspect-square' : 'aspect-[4/5]' }} overflow-hidden rounded-2xl border border-light-gold/60 bg-white">
                            <img src="{{ $img['url'] }}" alt="{{ $img['alt'] }}" class="w-full h-full object-contain" loading="{{ $position === 0 ? 'eager' : 'lazy' }}">
                            @if(!empty($img['is_actual']))
                                <div class="absolute top-2.5 left-2.5 z-10 pointer-events-none">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-semibold tracking-wide shadow-xs border"
                                          style="background-color: #FBFAF8; color: #580000; border-color: #B08858;">
                                        <svg class="w-3 h-3 shrink-0" style="color: #B08858;" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Actual Product Image</span>
                                    </span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="w-full lg:col-span-2 {{ $isSmartLivingHub ? 'aspect-square' : 'aspect-[4/5]' }} rounded-2xl border border-light-gold/60 bg-warm-beige/30 grid place-items-center text-rich-black/40">
                            No image available
                        </div>
                    @endforelse
                    @if((count($initialImages) + ($approvedVideo ? 1 : 0)) % 2 === 1)
                        <div class="hidden lg:flex relative {{ $isSmartLivingHub ? 'aspect-square' : 'aspect-[4/5]' }} rounded-2xl border border-dashed border-light-gold/60 bg-warm-beige/20 flex-col items-center justify-center p-6 text-center">
                            <div class="w-10 h-10 rounded-full border border-light-gold/60 bg-white/60 flex items-center justify-center text-maroon-800 font-serif font-bold text-sm mb-1.5 shadow-xs">
                                D
                            </div>
                            <span class="font-serif text-[11px] uppercase tracking-widest text-rich-black/35 font-semibold">Dhansree</span>
                        </div>
                    @endif
                </div>
            </noscript>

            <!-- View all photos button (Desktop, if more than 6 media items) -->
            <div x-show="(visibleImages.length + {{ $hasApprovedVideo ? 1 : 0 }}) > 6 && !expanded" class="hidden lg:block">
                <button type="button"
                        @click="expanded = true"
                        class="mt-3 w-full inline-flex items-center justify-center py-2.5 px-4 rounded-xl border border-light-gold bg-warm-beige/40 text-xs font-bold uppercase tracking-wider text-rich-black hover:bg-warm-beige transition-colors">
                    <span x-text="'View all ' + (visibleImages.length + {{ $hasApprovedVideo ? 1 : 0 }}) + ' photos & video'"></span>
                </button>
            </div>

            <!-- Mobile Dot Indicators -->
            <ul x-show="(visibleImages.length + {{ $hasApprovedVideo ? 1 : 0 }}) > 1"
                aria-label="Choose a picture of {{ $product->name }}"
                class="mt-3 flex items-center justify-center gap-2 lg:hidden">
                @if($hasApprovedVideo)
                    <li>
                        <button type="button"
                                @click="scrollToImage(0)"
                                aria-label="Show model video"
                                class="grid h-11 w-6 place-items-center">
                            <span aria-hidden
                                  :class="activeIdx === 0 ? 'w-6 bg-amber-600' : 'w-2 bg-stone-300'"
                                  class="block h-2 rounded-full transition-all duration-300"></span>
                        </button>
                    </li>
                @endif
                <template x-for="(img, position) in visibleImages" :key="'dot-' + img.id">
                    <li>
                        <button type="button"
                                @click="scrollToImage(position + {{ $hasApprovedVideo ? 1 : 0 }})"
                                :aria-label="'Show picture ' + (position + 1) + ' of ' + visibleImages.length"
                                class="grid h-11 w-6 place-items-center">
                            <span aria-hidden
                                  :class="activeIdx === (position + {{ $hasApprovedVideo ? 1 : 0 }}) ? 'w-6 bg-maroon-800' : 'w-2 bg-stone-300'"
                                  class="block h-2 rounded-full transition-all duration-300"></span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>

        <!-- Right: Details, Sizing, CTA -->
        <div class="min-w-0 lg:self-start lg:sticky lg:top-24 space-y-6 {{ $isSmartLivingHub ? 'lg:col-span-5' : '' }}">
            
            <div class="space-y-2">
                <p class="text-xs uppercase tracking-widest text-gold-600 font-bold">
                    {{ $product->brand ?? 'DHANSREE' }}
                </p>
                <h1 class="font-sans text-xl sm:text-2xl lg:text-3xl font-semibold text-rich-black leading-snug tracking-tight">
                    {{ $product->name }}
                </h1>
                @if(!empty($reviewSummary))
                    <div class="flex items-center gap-2 pt-1 text-xs">
                        <div class="flex items-center text-amber-500 text-sm">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= round($reviewSummary['average']) ? 'fill-amber-400 text-amber-400' : 'text-zinc-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="font-bold text-rich-black">{{ number_format($reviewSummary['average'], 1) }}</span>
                        <span class="text-rich-black/30">•</span>
                        <a href="#customer-reviews" class="text-maroon-800 hover:underline font-medium">
                            {{ $reviewSummary['count'] }} {{ Str::plural('Review', $reviewSummary['count']) }}
                        </a>
                    </div>
                @endif
            </div>

            <!-- Price and Discount -->
            <div class="flex items-baseline gap-3 pb-4 border-b border-light-gold/60">
                <span class="text-2xl sm:text-3xl font-bold text-maroon-800" x-text="selectedVariantPrice"></span>
                <del class="text-sm text-rich-black/40 font-medium" x-show="selectedVariantMrp" x-text="selectedVariantMrp"></del>
                <span class="px-2 py-0.5 rounded bg-gold-200 text-maroon-900 text-xs font-bold uppercase tracking-wider"
                      x-show="selectedVariantDiscount"
                      x-text="selectedVariantDiscount"></span>
            </div>

            <p class="text-[0.6875rem] text-rich-black/50 -mt-2">
                Price inclusive of all taxes. Free express shipping on orders over ₹999.
            </p>

            @if($approvedVideo)
                <div class="pt-1">
                    <button type="button"
                            @click="videoModalOpen = true; $refs.modalVideo?.play()"
                            class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-amber-500/10 border border-amber-300 text-maroon-900 text-xs font-bold hover:border-amber-400 hover:shadow-xs transition-all cursor-pointer">
                        <span class="size-5 rounded-full bg-gradient-to-tr from-amber-500 to-rose-600 text-white flex items-center justify-center text-[10px] shadow-xs">▶</span>
                        <span class="tracking-wide">Watch in Motion (Model Reel)</span>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-white text-amber-900 border border-amber-200/60 font-semibold">{{ $approvedVideo->duration_seconds ?? 8 }}s</span>
                    </button>
                </div>
            @endif

            @php $defaultCoupon = \App\Models\StoreSettings::current()->getDefaultCoupon(); @endphp
            @if($defaultCoupon)
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    <span>Special Offer: Includes {{ $defaultCoupon->type === \App\Enums\CouponType::PERCENTAGE ? ($defaultCoupon->value / 100) . '% OFF' : '₹' . ($defaultCoupon->value / 100) . ' OFF' }} with coupon <strong class="font-bold text-emerald-900 tracking-wide">{{ $defaultCoupon->code }}</strong> (auto-applied at checkout)</span>
                </div>
            @endif

            @if($product->short_description)
                <p class="text-xs sm:text-sm text-rich-black/75 leading-relaxed">
                    {{ $product->short_description }}
                </p>
            @endif

            <!-- Variant, Color & Size Selector (Only for Women's Fashion products) -->
            @if(!$isSmartLivingHub && $product->variants->count() > 0)
                <div class="space-y-4 pt-2">
                    <!-- Color Selector (if product has multiple color variations) -->
                    <template x-if="hasColors">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold uppercase tracking-wider text-rich-black">
                                    Color: <span class="text-maroon-800 font-semibold" x-text="selectedColor"></span>
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <template x-for="c in colors" :key="c.name">
                                    <button type="button"
                                            @click="selectColor(c.name)"
                                            :class="selectedColor === c.name ? 'border-maroon-800 ring-2 ring-maroon-800/40 bg-maroon-50/70 font-bold text-maroon-900' : 'border-light-gold hover:border-maroon-800/60 text-rich-black bg-white'"
                                            class="px-3.5 py-2 rounded-lg border text-xs font-semibold flex items-center gap-2 transition-all shadow-2xs">
                                        <template x-if="c.thumbnail">
                                            <img :src="c.thumbnail" class="w-4 h-4 rounded-full object-cover border border-slate-200">
                                        </template>
                                        <template x-if="!c.thumbnail">
                                            <span class="w-3.5 h-3.5 rounded-full border border-slate-300"
                                                  :style="'background-color: ' + (c.hex || '#cbd5e1')"></span>
                                        </template>
                                        <span x-text="c.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Size Selector -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold uppercase tracking-wider text-rich-black">
                                Select Size: <span class="text-maroon-800 font-semibold" x-text="selectedSize"></span>
                            </span>
                            @if(!empty($sizeChart))
                                <button type="button"
                                        @click="sizeChartOpen = true"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-rich-black underline underline-offset-4 hover:text-maroon-800 transition-colors">
                                    <svg class="w-4 h-4 text-maroon-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Size chart
                                </button>
                            @else
                                <span class="text-maroon-800 font-medium">Standard Indian Fit</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2.5">
                            <template x-for="v in getSizesForColor(selectedColor)" :key="v.id">
                                <button type="button"
                                        @click="selectSize(v.size)"
                                        :class="{
                                            'border-maroon-800 bg-maroon-50 text-maroon-800 ring-1 ring-maroon-800 font-bold': selectedSize === v.size && v.inStock,
                                            'border-rose-400 bg-rose-50/50 text-rose-700 ring-1 ring-rose-400 font-bold': selectedSize === v.size && !v.inStock,
                                            'border-light-gold text-rich-black hover:border-maroon-800/50 bg-white': selectedSize !== v.size && v.inStock,
                                            'border-slate-200 text-slate-300 bg-slate-50 line-through': selectedSize !== v.size && !v.inStock
                                        }"
                                        class="min-w-11 px-3.5 h-11 rounded-lg border text-xs font-semibold flex items-center justify-center transition-all shadow-xs relative">
                                    <span x-text="v.size"></span>
                                </button>
                            </template>
                        </div>

                        <!-- Out of Stock Indicator -->
                        <div x-show="!inStock" class="text-xs text-rose-600 font-medium flex items-center gap-1.5 pt-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Selected size & color is currently out of stock</span>
                        </div>
                    </div>
                </div>
            @elseif(!$isSmartLivingHub && !empty($sizeChart))
                <div class="pt-2">
                    <button type="button"
                            @click="sizeChartOpen = true"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-rich-black underline underline-offset-4 hover:text-maroon-800 transition-colors">
                        <svg class="w-4 h-4 text-maroon-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Size chart
                    </button>
                </div>
            @endif

            <!-- Quantity Stepper & Add to Bag -->
            <div class="space-y-4 pt-4 border-t border-light-gold/60">
                <div class="flex items-center gap-4">
                    <div class="flex items-center border border-light-gold rounded-lg bg-white p-1">
                        <button type="button"
                                @click="quantity = Math.max(1, quantity - 1)"
                                class="w-8 h-8 rounded text-sm font-bold text-rich-black/70 hover:bg-slate-100 flex items-center justify-center">−</button>
                        <span class="w-10 text-center text-xs font-bold text-rich-black" x-text="quantity"></span>
                        <button type="button"
                                @click="quantity = quantity + 1"
                                class="w-8 h-8 rounded text-sm font-bold text-rich-black/70 hover:bg-slate-100 flex items-center justify-center">+</button>
                    </div>

                    <button type="button"
                            @click="addToCart(selectedVariantId, quantity)"
                            :disabled="!inStock"
                            :class="!inStock ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-maroon-800 hover:bg-maroon-900'"
                            class="flex-1 py-3.5 px-6 rounded-lg text-white text-xs font-bold uppercase tracking-wider transition-all shadow-md flex items-center justify-center gap-2">
                        <span>🛍️</span>
                        <span x-text="inStock ? 'Add to Bag' : 'Out of Stock'"></span>
                    </button>

                    <button type="button"
                            @click="toggleWishlist({{ $product->id }})"
                            class="py-3.5 px-4 rounded-lg border border-light-gold bg-white hover:border-rose-400 text-rich-black transition-all shadow-xs flex items-center justify-center gap-2 shrink-0"
                            :title="isInWishlist({{ $product->id }}) ? 'Remove from Wishlist' : 'Add to Wishlist'">
                        <svg class="w-5 h-5 transition-colors"
                             :class="isInWishlist({{ $product->id }}) ? 'fill-rose-500 text-rose-500' : 'text-slate-400 hover:text-rose-500'"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider hidden sm:inline"
                              x-text="isInWishlist({{ $product->id }}) ? 'Wishlisted' : 'Wishlist'">
                        </span>
                    </button>
                </div>

                <!-- Express Delivery info banner -->
                <div class="p-3.5 rounded-xl bg-warm-beige/40 border border-light-gold flex items-center gap-3 text-xs text-rich-black/80">
                    <span class="text-xl">🚚</span>
                    <div>
                        <p class="font-bold">Fast Dispatch in 24 Hours</p>
                        <p class="text-[0.6875rem] text-rich-black/60">Delivered within 3–5 business days across India.</p>
                    </div>
                </div>
            </div>

            <!-- Accordions: Description, Specs, Shipping -->
            <div class="pt-6 border-t border-light-gold/60 space-y-3 text-xs">
                
                <!-- Description -->
                <div class="border border-light-gold rounded-xl overflow-hidden bg-white shadow-xs">
                    <button type="button"
                            @click="activeTab = (activeTab === 'description' ? '' : 'description')"
                            class="w-full p-4 text-left font-bold text-rich-black flex items-center justify-between hover:bg-warm-beige/20 transition-colors">
                        <span>Product Description</span>
                        <span x-text="activeTab === 'description' ? '−' : '+'" class="text-base text-gold-600 font-bold"></span>
                    </button>
                    <div x-show="activeTab === 'description'" class="p-4 pt-0 text-rich-black/75 leading-relaxed prose prose-sm max-w-none">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                </div>

                <!-- Material & Care / Specifications -->
                <div class="border border-light-gold rounded-xl overflow-hidden bg-white shadow-xs">
                    <button type="button"
                            @click="activeTab = (activeTab === 'specs' ? '' : 'specs')"
                            class="w-full p-4 text-left font-bold text-rich-black flex items-center justify-between hover:bg-warm-beige/20 transition-colors">
                        <span>{{ $isSmartLivingHub ? 'Specifications' : 'Fabric, Specifications & Care' }}</span>
                        <span x-text="activeTab === 'specs' ? '−' : '+'" class="text-base text-gold-600 font-bold"></span>
                    </button>
                    <div x-show="activeTab === 'specs'" class="p-4 pt-0 text-rich-black/75 leading-relaxed">
                        @php
                            $specs = collect();
                            if (!empty($product->brand)) {
                                $specs->push(['label' => 'Brand', 'value' => $product->brand]);
                            }
                            foreach ($product->productAttributes as $attr) {
                                $specs->push(['label' => $attr->label, 'value' => $attr->value]);
                            }
                        @endphp
                        @if($specs->isNotEmpty())
                            <dl class="divide-y divide-light-gold/40 text-xs">
                                @foreach($specs as $spec)
                                    <div class="grid grid-cols-[minmax(0,10rem)_minmax(0,1fr)] gap-x-4 gap-y-0.5 py-2 first:pt-0">
                                        <dt class="font-semibold text-rich-black/70">{{ $spec['label'] }}:</dt>
                                        <dd class="font-medium text-rich-black">{{ $spec['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @elseif($isSmartLivingHub)
                            <div class="text-xs text-rich-black/60">
                                <p>Standard specifications apply. Please refer to product description for details.</p>
                            </div>
                        @else
                            <div class="space-y-1.5 text-xs">
                                <p><strong>Fabric:</strong> 100% Breathable Pure Cotton</p>
                                <p><strong>Fit:</strong> Regular Straight Fit</p>
                                <p><strong>Care:</strong> Gentle hand wash or machine wash with mild detergent. Dry in shade.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Shipping & Returns -->
                <div class="border border-light-gold rounded-xl overflow-hidden bg-white shadow-xs">
                    <button type="button"
                            @click="activeTab = (activeTab === 'shipping' ? '' : 'shipping')"
                            class="w-full p-4 text-left font-bold text-rich-black flex items-center justify-between hover:bg-warm-beige/20 transition-colors">
                        <span>Shipping &amp; Easy 7-Day Returns</span>
                        <span x-text="activeTab === 'shipping' ? '−' : '+'" class="text-base text-gold-600 font-bold"></span>
                    </button>
                    <div x-show="activeTab === 'shipping'" class="p-4 pt-0 text-rich-black/75 leading-relaxed space-y-1.5">
                        <p>• <strong>Free Shipping:</strong> On all orders above ₹999.</p>
                        <p>• <strong>COD Available:</strong> Pay in cash at the time of delivery.</p>
                        <p>• <strong>Hassle-Free Returns:</strong> Initiate returns or size exchanges within 7 days of delivery.</p>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- Image Zoom Lightbox Modal -->
    <div x-show="zoomModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-rich-black/90 backdrop-blur-md"
         @keydown.escape.window="zoomModalOpen = false">
        <div class="relative max-w-4xl w-full max-h-[90vh] flex items-center justify-center">
            <button type="button"
                    @click="zoomModalOpen = false"
                    class="absolute -top-12 right-0 p-2 text-white/80 hover:text-white"
                    aria-label="Close zoom">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <img :src="activeImage" alt="{{ $product->name }}" class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl">
        </div>
    </div>

    @if($approvedVideo)
    <!-- Fullscreen Fashion Reel Lightbox Modal -->
    <div x-show="videoModalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/90 backdrop-blur-md"
         @keydown.escape.window="videoModalOpen = false; $refs.modalVideo?.pause()">
        <div class="relative max-w-sm sm:max-w-md w-full max-h-[92vh] flex flex-col items-center justify-center bg-black rounded-3xl overflow-hidden shadow-2xl border border-amber-400/50">
            <!-- Close Button -->
            <button type="button"
                    @click="videoModalOpen = false; $refs.modalVideo?.pause()"
                    class="absolute top-4 right-4 z-30 size-9 rounded-full bg-black/70 text-white flex items-center justify-center hover:bg-black/90 transition-all border border-white/20 cursor-pointer shadow-lg"
                    aria-label="Close video reel">
                ✕
            </button>

            <!-- Top Left AI Disclaimer Indicator with Tooltip -->
            <div x-show="Boolean(currentVideo ? currentVideo.is_ai_generated : {{ $approvedVideo?->isAiGenerated() ? 'true' : 'false' }})"
                 class="absolute top-4 left-4 z-30 pointer-events-auto"
                 x-data="{ showModalDisclaimer: false }"
                 @click.outside="showModalDisclaimer = false">
                <div class="relative group/modal-disclaimer inline-flex"
                     @mouseenter="showModalDisclaimer = true"
                     @mouseleave="showModalDisclaimer = false">
                    <button type="button"
                            @click.stop="showModalDisclaimer = !showModalDisclaimer"
                            title="AI-generated video for illustration purposes only. Actual product details may vary; please refer to the actual product images for accurate details."
                            aria-label="AI Video Disclaimer"
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-black/85 hover:bg-black text-amber-300 hover:text-amber-200 border border-amber-400/70 text-xs font-bold shadow-xl cursor-pointer transition-all backdrop-blur-md">
                        <span class="size-4 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/60 flex items-center justify-center text-[10px] font-serif italic shrink-0">i</span>
                        <span class="tracking-wide">AI Video Disclaimer</span>
                    </button>

                    <!-- Hover/Tap Tooltip -->
                    <div :class="showModalDisclaimer ? 'opacity-100 visible pointer-events-auto' : 'opacity-0 invisible pointer-events-none group-hover/modal-disclaimer:opacity-100 group-hover/modal-disclaimer:visible group-hover/modal-disclaimer:pointer-events-auto'"
                         @click.stop
                         class="transition-all duration-200 ease-out absolute left-0 top-full pt-2 w-72 sm:w-80 z-40">
                        <div class="p-3.5 rounded-xl bg-slate-950/98 text-slate-100 backdrop-blur-md border border-amber-500/60 text-xs leading-relaxed shadow-2xl">
                            <div class="flex items-start gap-2.5">
                                <span class="size-4 rounded-full bg-amber-500/20 text-amber-300 border border-amber-400/50 flex items-center justify-center text-[10px] font-serif italic shrink-0 mt-0.5">i</span>
                                <p class="text-white font-normal text-xs leading-relaxed" style="color: #ffffff !important;">
                                    AI-generated video for illustration purposes only. Actual product details may vary; please refer to the actual product images for accurate details.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Player in 9:16 vertical ratio -->
            <div class="w-full h-full max-h-[92vh] aspect-[9/16] relative bg-black flex items-center justify-center overflow-hidden">
                <video x-ref="modalVideo"
                       :poster="currentVideo?.thumbnail_url"
                       :src="currentVideo?.webm_url || currentVideo?.video_url"
                       poster="{{ $approvedVideo->thumbnail_url }}"
                       playsinline
                       loop
                       controls
                       autoplay
                       class="w-full h-full object-contain">
                    @if($approvedVideo->webm_url)
                        <source src="{{ $approvedVideo->webm_url }}" type="video/webm">
                    @endif
                    <source src="{{ $approvedVideo->video_url }}" type="video/mp4">
                </video>
            </div>

            <!-- Bottom Floating Tagged Product Details (Direct child of modal, always visible above native controls) -->
            <div class="absolute bottom-3 sm:bottom-4 inset-x-3 sm:inset-x-4 p-3.5 sm:p-4 rounded-2xl shadow-2xl z-30 pointer-events-auto"
                 style="background-color: rgba(10, 10, 14, 0.96); backdrop-filter: blur(16px); border: 1px solid rgba(251, 191, 36, 0.35);">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <span class="inline-block text-[10px] uppercase tracking-wider font-bold mb-1"
                              style="color: #fbbf24 !important;">
                            Featured in Reel
                        </span>
                        <!-- Product Title in Wrap Mode with Pure White Color -->
                        <p class="text-xs sm:text-sm font-bold leading-snug line-clamp-2 break-words"
                           style="color: #ffffff !important;">
                            {{ $product->name }}
                        </p>
                        <!-- Complete Pricing Display -->
                        <div class="flex items-baseline gap-2 mt-1 flex-wrap">
                            <span class="text-sm sm:text-base font-black"
                                  style="color: #fbbf24 !important;"
                                  x-text="selectedVariantPrice">
                                {{ $firstMatch['price'] ?? $product->formatted_price }}
                            </span>
                            <span class="text-xs line-through"
                                  style="color: #cbd5e1 !important;"
                                  x-text="selectedVariantMrp"
                                  x-show="selectedVariantMrp">
                                {{ $firstMatch['mrp'] ?? ($product->formatted_mrp ?? '') }}
                            </span>
                            <span class="text-[11px] font-bold"
                                  style="color: #34d399 !important;"
                                  x-text="selectedVariantDiscount"
                                  x-show="selectedVariantDiscount">
                                {{ $firstMatch['discount'] ?? ($product->discount_percentage ? $product->discount_percentage . '% OFF' : '') }}
                            </span>
                        </div>
                    </div>

                    <!-- Buy Now CTA Button (Adds to cart & navigates directly to cart) -->
                    <button type="button"
                            @click="buyNowFromReel()"
                            :disabled="isBuying || !inStock"
                            class="shrink-0 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 active:bg-amber-600 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg transition-all cursor-pointer whitespace-nowrap flex items-center gap-1.5 self-center">
                        <span x-text="isBuying ? 'Adding...' : (inStock ? 'Buy Now' : 'Sold Out')">Buy Now</span>
                        <span class="text-xs" x-show="!isBuying">➔</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Customer Reviews Section -->
    <section id="customer-reviews" class="mt-16 md:mt-24 pt-12 border-t border-light-gold">
        <div class="space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-widest text-gold-600 font-semibold">Verified Feedback</p>
                    <h2 class="font-serif text-3xl text-maroon-800 font-normal">Customer Reviews</h2>
                </div>
                <div>
                    @if(auth('customer')->check())
                        <a href="{{ route('storefront.account.orders') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gold-200/50 hover:bg-gold-200 text-maroon-900 border border-gold-400 text-xs font-semibold transition-colors">
                            <span>★ Write a Review for Your Purchase</span>
                        </a>
                    @else
                        <a href="{{ route('storefront.account.login') }}?next={{ urlencode(request()->getRequestUri()) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gold-200/50 hover:bg-gold-200 text-maroon-900 border border-gold-400 text-xs font-semibold transition-colors">
                            <span>Sign in to Review Purchases</span>
                        </a>
                    @endif
                </div>
            </div>

            @if(!empty($reviewSummary))
                <!-- Rating Breakdown Grid -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 p-6 sm:p-8 rounded-3xl bg-white border border-light-gold shadow-xs items-center">
                    <div class="md:col-span-4 text-center md:border-r md:border-light-gold/80 md:pr-6 space-y-2">
                        <div class="font-serif text-5xl font-bold text-maroon-800">
                            {{ number_format($reviewSummary['average'], 1) }}
                        </div>
                        <div class="flex items-center justify-center text-amber-400 text-lg">
                            @for($i = 1; $i <= 5; $i++)
                                <span>{{ $i <= round($reviewSummary['average']) ? '★' : '☆' }}</span>
                            @endfor
                        </div>
                        <div class="text-xs text-rich-black/60 font-medium">
                            Based on {{ $reviewSummary['count'] }} verified {{ Str::plural('rating', $reviewSummary['count']) }}
                        </div>
                    </div>

                    <!-- Breakdown Bars -->
                    <div class="md:col-span-8 space-y-2">
                        @foreach([5, 4, 3, 2, 1] as $star)
                            @php
                                $starCount = $reviewSummary['breakdown'][$star] ?? 0;
                                $percentage = $reviewSummary['count'] > 0 ? round(($starCount / $reviewSummary['count']) * 100) : 0;
                            @endphp
                            <div class="flex items-center gap-3 text-xs">
                                <span class="w-12 text-rich-black/70 shrink-0 font-medium">{{ $star }} Stars</span>
                                <div class="flex-1 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-amber-400 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="w-10 text-right text-rich-black/50 tabular-nums shrink-0">{{ $starCount }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="space-y-4">
                    @foreach($initialReviews as $rev)
                        <div class="p-6 rounded-2xl bg-white border border-light-gold/60 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-maroon-50 border border-maroon-200 text-maroon-800 font-bold text-xs flex items-center justify-center">
                                        {{ mb_substr($rev['author_name'], 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-xs text-rich-black">
                                            {{ $rev['author_name'] }}
                                        </div>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Verified Purchase
                                        </span>
                                    </div>
                                </div>
                                <div class="text-[11px] text-rich-black/40">
                                    {{ \Carbon\Carbon::parse($rev['published_at'])->format('d M Y') }}
                                </div>
                            </div>

                            <div class="flex items-center gap-1 text-amber-400 text-sm">
                                @for($i = 1; $i <= 5; $i++)
                                    <span>{{ $i <= $rev['rating'] ? '★' : '☆' }}</span>
                                @endfor
                                @if(!empty($rev['variant_title']))
                                    <span class="text-xs text-rich-black/40 ml-2">Variant: {{ $rev['variant_title'] }}</span>
                                @endif
                            </div>

                            @if(!empty($rev['title']))
                                <h4 class="font-bold text-rich-black text-sm">{{ $rev['title'] }}</h4>
                            @endif

                            <p class="text-xs text-rich-black/75 leading-relaxed whitespace-pre-line">{{ $rev['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center rounded-2xl bg-white border border-light-gold/60 space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-gold-200/40 text-gold-600 flex items-center justify-center font-bold text-xl">
                        ★
                    </div>
                    <h3 class="font-serif text-base font-semibold text-rich-black">No Reviews Yet</h3>
                    <p class="text-xs text-rich-black/60 max-w-md mx-auto">
                        Be the first to review this product once your order is delivered to your doorstep.
                    </p>
                </div>
            @endif
        </div>
    </section>

    <!-- Product FAQs Section (Accordion) -->
    @if(isset($faqs) && count($faqs) > 0)
        <section class="mt-16 md:mt-24 pt-12 border-t border-light-gold">
            <div class="text-center space-y-2 mb-10">
                <p class="text-xs uppercase tracking-widest text-gold-600 font-semibold">Have Questions?</p>
                <h2 class="font-serif text-3xl text-maroon-800">Frequently Asked Questions</h2>
                <p class="text-xs text-rich-black/60 max-w-lg mx-auto">
                    Quick answers to common questions about orders, quality, sizing, and care.
                </p>
            </div>

            <div class="max-w-3xl mx-auto space-y-3" x-data="{ openFaq: null }">
                @foreach($faqs as $index => $faq)
                    <div class="bg-white rounded-xl border border-light-gold/70 shadow-xs overflow-hidden transition-all duration-200">
                        <button type="button"
                                @click="openFaq = (openFaq === {{ $index }} ? null : {{ $index }})"
                                class="w-full flex items-center justify-between p-4 sm:p-5 text-left text-rich-black hover:text-maroon-800 transition-colors focus:outline-none">
                            <span class="text-xs sm:text-sm font-semibold pr-4">
                                {{ $faq->question }}
                            </span>
                            <span class="shrink-0 size-6 rounded-full bg-warm-beige/60 text-maroon-800 flex items-center justify-center transition-transform duration-200"
                                  :class="openFaq === {{ $index }} ? 'rotate-180 bg-maroon-800 text-white' : ''">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="openFaq === {{ $index }}"
                             x-cloak
                             class="px-4 pb-4 sm:px-5 sm:pb-5 pt-0 text-xs sm:text-sm text-rich-black/75 leading-relaxed border-t border-light-gold/30">
                            <p class="pt-3">{!! nl2br(e($faq->answer)) !!}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Related Products -->
    @if($relatedProducts->isNotEmpty())
        <section class="mt-16 md:mt-24 pt-12 border-t border-light-gold">
            <div class="text-center space-y-2 mb-10">
                <p class="text-xs uppercase tracking-widest text-gold-600 font-semibold">You May Also Love</p>
                <h2 class="font-serif text-3xl text-maroon-800">Related Products</h2>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($relatedProducts as $rel)
                    @php
                        $relVideo = $rel->approved_video;
                    @endphp
                    <div class="group bg-white rounded-xl p-3 border border-light-gold/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                         x-data="{ isHovered: false }"
                         @mouseenter="isHovered = true; if ($refs.cardVideo) { $refs.cardVideo.play().catch(() => {}); }"
                         @mouseleave="isHovered = false; if ($refs.cardVideo) { $refs.cardVideo.pause(); $refs.cardVideo.currentTime = 0; }">
                        <div>
                            <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-warm-beige mb-3">
                                <a href="{{ url('/product/' . $rel->slug) }}" class="block w-full h-full relative">
                                    <img src="{{ $rel->primary_image_url }}" alt="{{ $rel->name }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">

                                    @if($relVideo)
                                        <video x-ref="cardVideo"
                                               x-show="isHovered"
                                               playsinline
                                               loop
                                               muted
                                               preload="none"
                                               class="absolute inset-0 w-full h-full object-cover">
                                            @if($relVideo->webm_url)
                                                <source src="{{ $relVideo->webm_url }}" type="video/webm">
                                            @endif
                                            <source src="{{ $relVideo->video_url }}" type="video/mp4">
                                        </video>
                                    @endif
                                </a>

                                @if($relVideo)
                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-slate-900/85 backdrop-blur-xs text-amber-300 text-[10px] font-bold tracking-wide flex items-center gap-1 border border-amber-400/40 shadow-xs z-10 pointer-events-none"
                                          title="Watch fashion reel in motion">
                                        <span class="size-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                        <span>▶ Reel</span>
                                    </span>
                                @endif

                                <button type="button"
                                        @click.stop.prevent="toggleWishlist({{ $rel->id }})"
                                        class="absolute top-2 right-2 w-7 h-7 rounded-full bg-white/90 backdrop-blur-xs flex items-center justify-center shadow-xs hover:scale-110 transition-transform z-10"
                                        :title="isInWishlist({{ $rel->id }}) ? 'Remove from wishlist' : 'Add to wishlist'">
                                    <svg class="w-3.5 h-3.5 transition-colors"
                                         :class="isInWishlist({{ $rel->id }}) ? 'fill-rose-500 text-rose-500' : 'text-slate-400 hover:text-rose-500'"
                                         viewBox="0 0 24 24"
                                         fill="none"
                                         stroke="currentColor"
                                         stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                                    </svg>
                                </button>
                            </div>
                            <h4 class="text-xs font-medium text-rich-black line-clamp-2 h-9">
                                <a href="{{ url('/product/' . $rel->slug) }}" class="hover:text-maroon-800">
                                    {{ $rel->name }}
                                </a>
                            </h4>
                            <div class="flex items-baseline gap-1.5 mt-1">
                                <span class="text-sm font-bold text-maroon-800">{{ $rel->formatted_price }}</span>
                                @if($rel->formatted_mrp)
                                    <del class="text-xs text-rich-black/40">{{ $rel->formatted_mrp }}</del>
                                @endif
                                @if($rel->discount_percentage)
                                    <span class="text-[10px] font-bold text-emerald-700">{{ $rel->discount_percentage }}% OFF</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ url('/product/' . $rel->slug) }}"
                           class="mt-3 block text-center py-2 rounded-lg border border-maroon-800 text-maroon-800 text-xs font-semibold hover:bg-maroon-800 hover:text-white transition-colors">
                            View Product
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if(!$isSmartLivingHub && !empty($sizeChart))
        <!-- Size Chart Modal Dialog -->
        <div x-cloak
             x-show="sizeChartOpen"
             @keydown.escape.window="if (sizeChartZoomOpen) { sizeChartZoomOpen = false; resetChartZoom(); } else { sizeChartOpen = false; }"
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog"
             aria-modal="true"
             aria-labelledby="size-chart-title">
            
            <!-- Backdrop -->
            <div x-show="sizeChartOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="sizeChartOpen = false"
                 class="fixed inset-0 bg-rich-black/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"></div>

            <!-- Dialog Positioning Wrapper -->
            <div class="min-h-full flex items-center justify-center p-4 sm:p-6 text-center"
                 @click="sizeChartOpen = false">
                <div x-show="sizeChartOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.stop
                     class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-3xl border border-light-gold/60 max-h-[90vh] flex flex-col">
                    
                    <!-- Modal Header -->
                    <div class="px-5 sm:px-6 py-3.5 sm:py-4 border-b border-light-gold/40 flex items-center justify-between gap-3 bg-warm-beige/30">
                        <div>
                            <h3 id="size-chart-title" class="font-serif text-xl sm:text-2xl font-bold text-maroon-900 leading-tight">
                                {{ $sizeChart['title'] ?? 'Size guide' }}
                            </h3>
                            @if(!empty($sizeChart['note']))
                                <p class="text-xs text-rich-black/70 mt-0.5">
                                    {{ $sizeChart['note'] }}
                                </p>
                            @endif
                        </div>
                        <button type="button"
                                @click="sizeChartOpen = false; resetChartZoom()"
                                class="p-1.5 rounded-lg text-rich-black/60 hover:text-maroon-800 hover:bg-warm-beige/50 transition-colors shrink-0"
                                aria-label="Close size chart">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-4 sm:p-6 overflow-y-auto space-y-3 flex-1">
                        @if(!empty($sizeChart['image']))
                            <!-- Interactive Zoom Toolbar -->
                            <div class="flex flex-wrap items-center justify-between gap-2 px-1">
                                <div class="text-xs text-rich-black/70 flex items-center gap-1.5 font-medium">
                                    <svg class="w-3.5 h-3.5 text-gold-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                    <span class="hidden sm:inline">Click image or use controls to zoom</span>
                                    <span class="sm:hidden">Tap image to zoom</span>
                                </div>
                                <div class="flex items-center gap-1 sm:gap-1.5">
                                    <!-- Zoom Out -->
                                    <button type="button"
                                            @click="zoomOutChart()"
                                            :disabled="chartZoomScale <= 1"
                                            class="px-2.5 py-1 rounded-lg border border-light-gold bg-white hover:bg-warm-beige/30 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-semibold text-rich-black transition-colors"
                                            title="Zoom Out">
                                        − Zoom
                                    </button>
                                    <!-- Zoom Level Indicator / Toggle -->
                                    <button type="button"
                                            @click="toggleChartZoom()"
                                            class="px-2.5 py-1 rounded-lg bg-warm-beige/70 hover:bg-warm-beige text-maroon-900 border border-light-gold/80 font-mono text-xs font-bold transition-colors"
                                            title="Click to toggle 100% / 180%">
                                        <span x-text="Math.round(chartZoomScale * 100) + '%'">100%</span>
                                    </button>
                                    <!-- Zoom In -->
                                    <button type="button"
                                            @click="zoomInChart()"
                                            :disabled="chartZoomScale >= 2.5"
                                            class="px-2.5 py-1 rounded-lg border border-light-gold bg-white hover:bg-warm-beige/30 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-semibold text-rich-black transition-colors"
                                            title="Zoom In">
                                        + Zoom
                                    </button>
                                    <!-- Reset -->
                                    <button type="button"
                                            x-show="chartZoomScale > 1"
                                            @click="resetChartZoom()"
                                            class="px-2 py-1 rounded-lg border border-light-gold text-xs text-rich-black/70 hover:text-maroon-800 transition-colors">
                                        Reset
                                    </button>
                                    <!-- Fullscreen Lightbox Button -->
                                    <button type="button"
                                            @click="sizeChartZoomOpen = true"
                                            class="ml-1 px-3 py-1 rounded-lg bg-maroon-800 hover:bg-maroon-900 text-white text-xs font-semibold transition-colors flex items-center gap-1 shadow-xs"
                                            title="Open Fullscreen Lightbox">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                        <span class="hidden sm:inline">Fullscreen</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Image Container with Smooth Inline Zoom & Pan -->
                            <div class="relative overflow-auto rounded-xl border border-light-gold bg-warm-beige/10 p-2 sm:p-3 max-h-[72vh] text-center select-none"
                                 :class="chartZoomScale > 1 ? 'cursor-grab active:cursor-grabbing' : 'cursor-zoom-in'"
                                 title="Click to zoom in / out">
                                <div class="inline-block transition-transform duration-200 ease-out max-w-full"
                                     :style="'transform: scale(' + chartZoomScale + '); transform-origin: top center;'">
                                    <img src="{{ $sizeChart['image']['url'] }}"
                                         alt="{{ $sizeChart['image']['alt_text'] ?? 'Size Chart' }}"
                                         @click="toggleChartZoom()"
                                         class="mx-auto h-auto w-full max-w-full sm:max-w-2xl object-contain rounded-lg shadow-2xs select-none">
                                </div>
                            </div>
                        @elseif(!empty($sizeChart['rows']))
                            <!-- Fallback Measurement Table only when NO image is available -->
                            <div class="overflow-x-auto rounded-xl border border-light-gold shadow-xs">
                                <table class="w-full min-w-max border-separate border-spacing-0 text-xs">
                                    <thead>
                                        <tr class="text-left bg-warm-beige/60">
                                            <th scope="col" class="sticky top-0 z-10 border-b border-light-gold py-3 px-4 font-bold text-maroon-900 uppercase tracking-wider">
                                                Size
                                            </th>
                                            @foreach($sizeChart['columns'] as $col)
                                                <th scope="col" class="sticky top-0 z-10 border-b border-light-gold py-3 px-4 font-bold text-maroon-900 uppercase tracking-wider">
                                                    {{ $col }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sizeChart['rows'] as $r)
                                            <tr class="hover:bg-warm-beige/20 transition-colors odd:bg-white even:bg-warm-beige/10">
                                                <th scope="row" class="border-b border-light-gold/40 py-2.5 px-4 font-bold text-rich-black text-left">
                                                    {{ $r['label'] }}
                                                </th>
                                                @foreach($r['values'] as $val)
                                                    <td class="border-b border-light-gold/40 py-2.5 px-4 font-medium text-rich-black/80 tabular-nums">
                                                        {{ $val !== '' ? $val : '—' }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-[0.6875rem] text-rich-black/50 italic">
                                * All measurements are in inches unless otherwise specified.
                            </p>
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-3 bg-warm-beige/20 border-t border-light-gold/40 flex items-center justify-between">
                        <span class="text-[11px] text-rich-black/50 italic">
                            * All garment measurements are in inches.
                        </span>
                        <button type="button"
                                @click="sizeChartOpen = false; resetChartZoom()"
                                class="px-5 py-2 rounded-lg bg-maroon-800 text-white text-xs font-bold uppercase tracking-wider hover:bg-maroon-900 transition-colors shadow-xs">
                            Got It
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($sizeChart['image']))
            <!-- High-Resolution Fullscreen Size Chart Zoom Lightbox Modal -->
            <div x-cloak
                 x-show="sizeChartZoomOpen"
                 @keydown.escape.window="if (sizeChartZoomOpen) { sizeChartZoomOpen = false; resetChartZoom(); }"
                 style="z-index: 9999;"
                 class="fixed inset-0 flex flex-col bg-rich-black/95 backdrop-blur-md"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="size-chart-zoom-title">

                <!-- Top Floating Header with Zoom Controls -->
                <header class="px-4 py-3 bg-rich-black/95 border-b border-white/15 flex flex-wrap items-center justify-between gap-3 text-white shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="size-8 rounded-full bg-gold-500/20 text-gold-300 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                            </svg>
                        </span>
                        <div>
                            <h4 id="size-chart-zoom-title" class="text-sm font-semibold text-white tracking-wide">
                                Size Guide – Fullscreen Enlarge View
                            </h4>
                            <p class="text-[11px] text-white/60 hidden sm:block">
                                Click image or use + / − buttons to inspect measurements closely
                            </p>
                        </div>
                    </div>

                    <!-- Control Buttons -->
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <!-- Zoom Out Button -->
                        <button type="button"
                                @click="zoomOutChart()"
                                :disabled="chartZoomScale <= 1"
                                class="p-2 sm:px-3 sm:py-1.5 rounded-lg bg-white/10 hover:bg-white/20 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-semibold text-white transition-colors flex items-center gap-1"
                                title="Zoom Out (-)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                            <span class="hidden sm:inline">Zoom Out</span>
                        </button>

                        <!-- Zoom Level Percentage Display / Toggle -->
                        <button type="button"
                                @click="toggleChartZoom()"
                                class="px-2.5 py-1.5 rounded-lg bg-gold-500/20 border border-gold-400/40 text-gold-300 font-mono text-xs font-bold hover:bg-gold-500/30 transition-colors"
                                title="Click to toggle 100% / 180%">
                            <span x-text="Math.round(chartZoomScale * 100) + '%'">100%</span>
                        </button>

                        <!-- Zoom In Button -->
                        <button type="button"
                                @click="zoomInChart()"
                                :disabled="chartZoomScale >= 2.5"
                                class="p-2 sm:px-3 sm:py-1.5 rounded-lg bg-white/10 hover:bg-white/20 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-semibold text-white transition-colors flex items-center gap-1"
                                title="Zoom In (+)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span class="hidden sm:inline">Zoom In</span>
                        </button>

                        <!-- Reset Button -->
                        <button type="button"
                                x-show="chartZoomScale > 1"
                                @click="resetChartZoom()"
                                class="hidden sm:inline-flex px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold text-white transition-colors"
                                title="Reset zoom to 100%">
                            Reset
                        </button>

                        <div class="h-5 w-px bg-white/20 mx-1"></div>

                        <!-- Close / Back Button -->
                        <button type="button"
                                @click="sizeChartZoomOpen = false; resetChartZoom()"
                                class="px-3.5 py-1.5 rounded-lg bg-maroon-800 hover:bg-maroon-700 text-white text-xs font-bold transition-colors flex items-center gap-1.5 shadow-sm"
                                aria-label="Back to size chart">
                            <span>Back</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </header>

                <!-- Zoom Canvas Area -->
                <div class="flex-1 overflow-auto p-4 sm:p-8 flex items-center justify-center select-none"
                     :class="chartZoomScale > 1 ? 'cursor-grab active:cursor-grabbing' : 'cursor-zoom-in'"
                     @click.self="sizeChartZoomOpen = false; resetChartZoom()">
                    <div class="transition-transform duration-200 ease-out inline-block max-w-none text-center"
                         :style="'transform: scale(' + chartZoomScale + '); transform-origin: center center;'">
                        <img src="{{ $sizeChart['image']['url'] }}"
                             alt="{{ $sizeChart['image']['alt_text'] ?? 'Size Chart Enlarge' }}"
                             @click="toggleChartZoom()"
                             class="rounded-xl shadow-2xl max-h-[85vh] w-auto max-w-full sm:max-w-4xl object-contain mx-auto bg-white p-2.5 sm:p-4 border border-white/20 select-none pointer-events-auto"
                             title="Click to zoom in/out">
                    </div>
                </div>

                <!-- Bottom Floating Instruction Pill -->
                <div class="px-4 py-2.5 bg-rich-black/95 border-t border-white/10 flex items-center justify-between text-xs text-white/70">
                    <span class="hidden sm:inline">Tip: Click image or use + / − to zoom up to 250%. Drag to pan. Click Back or press Esc to return.</span>
                    <span class="sm:hidden text-center w-full text-[11px]">Use the <strong class="text-gold-300">+</strong> button above to zoom into measurement details.</span>
                    <button type="button"
                            @click="sizeChartZoomOpen = false; resetChartZoom()"
                            class="hidden sm:inline-block text-xs text-gold-300 hover:text-white underline underline-offset-2">
                        Back to Size Guide
                    </button>
                </div>
            </div>
        @endif
    @endif

</div>

@php
    $initialSku = $defaultVariant?->sku ?: ($product->primaryVariant?->sku ?: ('DS-PROD-' . $product->id));
    $variantSkus = $product->variants->pluck('sku')->filter()->values()->all();
    if (empty($variantSkus)) {
        $variantSkus = [$initialSku];
    }
    $catalogMatchIds = array_values(array_unique(array_merge([$initialSku], $variantSkus)));
    $initialPrice = (float) ($defaultVariant?->price_rupees ?? ($product->primaryVariant?->price_rupees ?? 0));
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.dhansreeTrack === 'function') {
            window.dhansreeTrack('view_item', {
                content_name: '{{ addslashes($product->name) }}',
                content_ids: @js($catalogMatchIds),
                content_type: 'product',
                value: {{ $initialPrice }},
                currency: 'INR',
                contents: [{
                    id: '{{ $initialSku }}',
                    quantity: 1,
                    item_price: {{ $initialPrice }}
                }]
            });
        }
    });
</script>
@endsection
