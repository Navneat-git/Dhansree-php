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
    $defaultVariant = $product->variants->whereNull('archived_at')->first();
    $firstImg = $product->primary_image_url ?? $product->images->first()?->url;
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12"
     x-data="{
        activeImage: '{{ $firstImg }}',
        zoomModalOpen: false,
        sizeChartOpen: false,
        selectedVariantId: {{ $defaultVariant?->id ?? 'null' }},
        selectedVariantPrice: '{{ $defaultVariant?->price_rupees ? '₹' . number_format($defaultVariant->price_rupees, 0) : $product->formatted_price }}',
        selectedVariantMrp: '{{ $defaultVariant?->mrp_rupees ? '₹' . number_format($defaultVariant->mrp_rupees, 0) : $product->formatted_mrp }}',
        selectedVariantDiscount: '{{ $defaultVariant?->discount_percentage ? $defaultVariant->discount_percentage . '% OFF' : ($product->discount_percentage ? $product->discount_percentage . '% OFF' : '') }}',
        quantity: 1,
        activeTab: 'description',
        selectVariant(id, price, mrp, discount) {
            this.selectedVariantId = id;
            this.selectedVariantPrice = price;
            this.selectedVariantMrp = mrp;
            this.selectedVariantDiscount = discount;
        }
     }">

    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-rich-black/50 mb-8 overflow-x-auto whitespace-nowrap">
        <a href="{{ $isSmartLivingHub ? url('/smart-living') : url('/') }}" class="hover:text-maroon-800 transition-colors">
            {{ $isSmartLivingHub ? 'Smart Living' : 'Home' }}
        </a>
        <span>/</span>
        @if($product->category)
            <a href="{{ url('/' . $product->category->slug) }}" class="hover:text-maroon-800 transition-colors">
                {{ $product->category->name }}
            </a>
            <span>/</span>
        @endif
        <span class="text-rich-black/80 font-medium truncate max-w-xs">{{ $product->name }}</span>
    </nav>

    <!-- Main Product Grid: Gallery (2-column desktop grid / mobile swipe) + Details -->
    <div class="grid grid-cols-1 {{ $isSmartLivingHub ? 'lg:grid-cols-12' : 'lg:grid-cols-2' }} gap-8 lg:gap-12 items-start">
        
        <!-- Left: Image Gallery (All images displayed together in 2-column grid on desktop, swipeable on mobile) -->
        <div class="min-w-0 {{ $isSmartLivingHub ? 'lg:col-span-7' : '' }}"
             x-data="{
                activeIdx: 0,
                expanded: false,
                imageCount: {{ $product->images->count() }},
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
                }
             }">
            
            <ul x-ref="galleryTrack"
                @scroll.passive="updateScroll($el)"
                aria-label="{{ $product->name }} pictures"
                class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden lg:grid lg:gap-3 lg:overflow-x-visible lg:scroll-auto {{ $product->images->count() === 1 ? 'lg:grid-cols-1' : 'lg:grid-cols-2' }}">
                
                @php
                    $imgCount = $product->images->count();
                    $leadIsWide = ($imgCount > 1 && $imgCount % 2 === 1);
                @endphp

                @forelse($product->images as $position => $img)
                    <li class="w-full shrink-0 snap-center {{ $leadIsWide && $position === 0 ? 'lg:col-span-2' : '' }}"
                        @if($position >= 6)
                            :class="expanded ? '' : 'lg:hidden'"
                        @endif
                    >
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
                             @click="activeImage = '{{ $img->url }}'; zoomModalOpen = true;">
                            
                            <div class="absolute inset-0 transition-transform duration-200 ease-out"
                                 :style="isHovered ? 'transform: scale(2.2); transform-origin: ' + origin + ';' : ''">
                                <img src="{{ $img->url }}"
                                     alt="{{ $img->alt_text ?? $product->name }}"
                                     loading="{{ $position === 0 ? 'eager' : 'lazy' }}"
                                     class="w-full h-full object-contain">
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="w-full lg:col-span-2 {{ $isSmartLivingHub ? 'aspect-square' : 'aspect-[4/5]' }} rounded-2xl border border-light-gold/60 bg-warm-beige/30 grid place-items-center text-rich-black/40">
                        <svg class="w-12 h-12 stroke-current" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                    </li>
                @endforelse
            </ul>

            <!-- View all photos button (Desktop, if more than 6 images) -->
            @if($imgCount > 6)
                <button type="button"
                        x-show="!expanded"
                        @click="expanded = true"
                        class="mt-3 w-full hidden lg:inline-flex items-center justify-center py-2.5 px-4 rounded-xl border border-light-gold bg-warm-beige/40 text-xs font-bold uppercase tracking-wider text-rich-black hover:bg-warm-beige transition-colors">
                    View all {{ $imgCount }} photos
                </button>
            @endif

            <!-- Mobile Dot Indicators -->
            @if($imgCount > 1)
                <ul aria-label="Choose a picture of {{ $product->name }}"
                    class="mt-3 flex items-center justify-center gap-2 lg:hidden">
                    @foreach($product->images as $position => $img)
                        <li>
                            <button type="button"
                                    @click="scrollToImage({{ $position }})"
                                    aria-label="Show picture {{ $position + 1 }} of {{ $imgCount }}"
                                    class="grid h-11 w-6 place-items-center">
                                <span aria-hidden
                                      :class="activeIdx === {{ $position }} ? 'w-6 bg-maroon-800' : 'w-2 bg-stone-300'"
                                      class="block h-2 rounded-full transition-all duration-300"></span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <!-- Right: Details, Sizing, CTA -->
        <div class="min-w-0 lg:self-start lg:sticky lg:top-24 space-y-6 {{ $isSmartLivingHub ? 'lg:col-span-5' : '' }}">
            
            <div class="space-y-2">
                <p class="text-xs uppercase tracking-widest text-gold-600 font-bold">
                    {{ $product->brand ?? 'DHANSREE' }}
                </p>
                <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-rich-black leading-snug">
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

            @if($product->short_description)
                <p class="text-xs sm:text-sm text-rich-black/75 leading-relaxed">
                    {{ $product->short_description }}
                </p>
            @endif

            <!-- Variant / Size Selector (Only for Women's Fashion products) -->
            @if(!$isSmartLivingHub && $product->variants->count() > 0)
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold uppercase tracking-wider text-rich-black">Select Size / Option:</span>
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
                        @foreach($product->variants->whereNull('archived_at') as $variant)
                            @php
                                $valName = $variant->optionValues->first()?->value ?? 'Standard';
                                $pRupees = '₹' . number_format($variant->price_rupees, 0);
                                $mRupees = $variant->mrp_rupees ? '₹' . number_format($variant->mrp_rupees, 0) : '';
                                $disc = $variant->discount_percentage ? $variant->discount_percentage . '% OFF' : '';
                            @endphp
                            <button type="button"
                                    @click="selectVariant({{ $variant->id }}, '{{ $pRupees }}', '{{ $mRupees }}', '{{ $disc }}')"
                                    :class="selectedVariantId === {{ $variant->id }} ? 'border-maroon-800 bg-maroon-50 text-maroon-800 ring-1 ring-maroon-800 font-bold' : 'border-light-gold text-rich-black hover:border-maroon-800/50'"
                                    class="min-w-12 h-11 px-3.5 rounded-lg border text-xs flex items-center justify-center transition-all bg-white shadow-xs">
                                {{ $valName }}
                            </button>
                        @endforeach
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
                            class="flex-1 py-3.5 px-6 rounded-lg bg-maroon-800 text-white text-xs font-bold uppercase tracking-wider hover:bg-maroon-900 transition-all shadow-md flex items-center justify-center gap-2">
                        <span>🛍️</span>
                        <span>Add to Bag</span>
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
                        <span>Fabric, Specifications &amp; Care</span>
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

    <!-- Related Products -->
    @if($relatedProducts->isNotEmpty())
        <section class="mt-16 md:mt-24 pt-12 border-t border-light-gold">
            <div class="text-center space-y-2 mb-10">
                <p class="text-xs uppercase tracking-widest text-gold-600 font-semibold">You May Also Love</p>
                <h2 class="font-serif text-3xl text-maroon-800">Related Products</h2>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($relatedProducts as $rel)
                    <div class="bg-white rounded-xl p-3 border border-light-gold/80 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                        <div>
                            <div class="relative aspect-[3/4] rounded-lg overflow-hidden bg-warm-beige mb-3">
                                <a href="{{ url('/product/' . $rel->slug) }}">
                                    <img src="{{ $rel->primary_image_url }}" alt="{{ $rel->name }}" class="w-full h-full object-cover">
                                </a>
                            </div>
                            <h4 class="text-xs font-medium text-rich-black line-clamp-2 h-9">
                                <a href="{{ url('/product/' . $rel->slug) }}" class="hover:text-maroon-800">
                                    {{ $rel->name }}
                                </a>
                            </h4>
                            <p class="text-sm font-bold text-maroon-800 mt-1">{{ $rel->formatted_price }}</p>
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
             @keydown.escape.window="sizeChartOpen = false"
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog"
             aria-modal="true"
             aria-labelledby="size-chart-title">
            
            <!-- Backdrop -->
            <div x-show="sizeChartOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:exit="ease-in duration-200"
                 x-transition:exit-start="opacity-100"
                 x-transition:exit-end="opacity-0"
                 @click="sizeChartOpen = false"
                 class="fixed inset-0 bg-rich-black/60 backdrop-blur-xs transition-opacity"></div>

            <!-- Dialog Positioning Wrapper -->
            <div class="min-h-full flex items-center justify-center p-4 sm:p-6 text-center">
                <div x-show="sizeChartOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:exit="ease-in duration-200"
                     x-transition:exit-start="opacity-100 scale-100"
                     x-transition:exit-end="opacity-0 scale-95"
                     @click.stop
                     class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-2xl border border-light-gold/60 max-h-[90vh] flex flex-col">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-light-gold/40 flex items-start justify-between bg-warm-beige/30">
                        <div>
                            <h3 id="size-chart-title" class="font-serif text-xl sm:text-2xl font-bold text-maroon-900">
                                {{ $sizeChart['title'] ?? 'Size guide' }}
                            </h3>
                            @if(!empty($sizeChart['note']))
                                <p class="mt-1 text-xs text-rich-black/70">
                                    {{ $sizeChart['note'] }}
                                </p>
                            @endif
                        </div>
                        <button type="button"
                                @click="sizeChartOpen = false"
                                class="p-1.5 rounded-lg text-rich-black/60 hover:text-maroon-800 hover:bg-warm-beige/50 transition-colors"
                                aria-label="Close size chart">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6 overflow-y-auto space-y-5 flex-1">
                        @if(!empty($sizeChart['image']))
                            @if($sizeChart['picture_is_the_chart'] ?? false)
                                <!-- Image is the entire size chart -->
                                <div class="max-h-[70vh] overflow-y-auto rounded-xl border border-light-gold bg-warm-beige/10">
                                    <img src="{{ $sizeChart['image']['url'] }}"
                                         alt="{{ $sizeChart['image']['alt_text'] ?? 'Size Chart' }}"
                                         class="mx-auto h-auto w-full object-contain">
                                </div>
                            @else
                                <!-- How to measure diagram image -->
                                <div class="shrink-0 overflow-hidden rounded-xl border border-light-gold bg-warm-beige/10">
                                    <img src="{{ $sizeChart['image']['url'] }}"
                                         alt="{{ $sizeChart['image']['alt_text'] ?? 'How to measure' }}"
                                         class="mx-auto h-auto max-h-[40vh] w-full object-contain">
                                </div>
                            @endif
                        @endif

                        @if(!($sizeChart['picture_is_the_chart'] ?? false) && !empty($sizeChart['rows']))
                            <!-- Measurement Table with Sticky Headers -->
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
                    <div class="px-6 py-3 bg-warm-beige/20 border-t border-light-gold/40 flex justify-end">
                        <button type="button"
                                @click="sizeChartOpen = false"
                                class="px-4 py-2 rounded-lg bg-maroon-800 text-white text-xs font-bold uppercase tracking-wider hover:bg-maroon-900 transition-colors shadow-xs">
                            Got It
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
