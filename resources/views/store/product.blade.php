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

    <!-- Main Product Grid: Gallery + Details -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-14 items-start">
        
        <!-- Left: Image Gallery -->
        <div class="lg:col-span-7 flex flex-col-reverse sm:flex-row gap-4 items-start">
            
            <!-- Thumbnail strip -->
            <div class="flex sm:flex-col gap-3 overflow-x-auto sm:overflow-y-auto w-full sm:w-20 shrink-0 pb-2 sm:pb-0">
                @foreach($product->images as $img)
                    <button type="button"
                            @click="activeImage = '{{ $img->url }}'"
                            :class="activeImage === '{{ $img->url }}' ? 'border-maroon-800 ring-2 ring-maroon-800/20' : 'border-light-gold hover:border-maroon-800/60'"
                            class="relative w-16 sm:w-20 aspect-[3/4] rounded-lg overflow-hidden border bg-warm-beige shrink-0 transition-all">
                        <img src="{{ $img->url }}" alt="{{ $img->alt_text ?? $product->name }}" class="w-full h-full object-cover">
                    </button>
                @endforeach
            </div>

            <!-- Main Big Photo with Enlarge / Zoom -->
            <div class="relative flex-1 aspect-[3/4] rounded-2xl overflow-hidden bg-warm-beige border border-light-gold shadow-md group">
                <img :src="activeImage"
                     alt="{{ $product->name }}"
                     class="w-full h-full object-cover cursor-zoom-in transition-transform duration-500 group-hover:scale-102"
                     @click="zoomModalOpen = true">

                <!-- Zoom hint icon -->
                <button type="button"
                        @click="zoomModalOpen = true"
                        class="absolute bottom-4 right-4 p-2.5 rounded-full bg-white/90 backdrop-blur-xs text-rich-black/80 hover:text-maroon-800 shadow-md transition-colors"
                        aria-label="Enlarge image">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Right: Details, Sizing, CTA -->
        <div class="lg:col-span-5 space-y-6">
            
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

            <!-- Variant / Size Selector -->
            @if($product->variants->count() > 0)
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold uppercase tracking-wider text-rich-black">Select Size / Option:</span>
                        <span class="text-maroon-800 font-medium">Standard Indian Fit</span>
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

                <!-- Material & Care -->
                <div class="border border-light-gold rounded-xl overflow-hidden bg-white shadow-xs">
                    <button type="button"
                            @click="activeTab = (activeTab === 'specs' ? '' : 'specs')"
                            class="w-full p-4 text-left font-bold text-rich-black flex items-center justify-between hover:bg-warm-beige/20 transition-colors">
                        <span>Fabric, Specifications &amp; Care</span>
                        <span x-text="activeTab === 'specs' ? '−' : '+'" class="text-base text-gold-600 font-bold"></span>
                    </button>
                    <div x-show="activeTab === 'specs'" class="p-4 pt-0 text-rich-black/75 leading-relaxed space-y-2">
                        @if($product->productAttributes->isNotEmpty())
                            <ul class="space-y-1.5">
                                @foreach($product->productAttributes as $attr)
                                    <li><strong>{{ $attr->name }}:</strong> {{ $attr->value }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p><strong>Fabric:</strong> 100% Breathable Pure Cotton</p>
                            <p><strong>Fit:</strong> Regular Straight Fit</p>
                            <p><strong>Care:</strong> Gentle hand wash or machine wash with mild detergent. Dry in shade.</p>
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

</div>
@endsection
