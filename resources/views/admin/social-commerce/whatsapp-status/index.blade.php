@extends('layouts.admin')

@section('title', 'WhatsApp Status Studio (9:16)')
@section('page_title', 'WhatsApp Status Studio')

@section('header_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.social-commerce.products.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
        Product Library
    </a>
    <a href="{{ route('admin.social-commerce.inbox.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 shadow-xs transition-colors">
        Supplier Media Inbox
    </a>
</div>
@endsection

@section('content')
<div x-data="{
    assetId: {{ $selectedAsset ? $selectedAsset->id : ($standaloneAssets->first()?->id ?? 'null') }},
    productId: {{ $selectedProduct ? $selectedProduct->id : 'null' }},
    template: 'classic_maroon',
    brandName: '{{ addslashes($brandName) }}',
    tagline: '{{ addslashes($tagline) }}',
    whatsappNumber: '{{ addslashes($whatsappNumber) }}',
    ctaText: '{{ addslashes($whatsappCta) }}',
    priceText: '{{ $selectedProduct ? ($selectedProduct->special_offer_price_rupees ? 'Special: ₹' . number_format($selectedProduct->special_offer_price_rupees, 0) : '₹' . number_format($selectedProduct->suggested_selling_price_rupees ?? 999, 0)) : 'Special: ₹899' }}',
    customTitle: '{{ $selectedProduct ? addslashes($selectedProduct->name) : 'Exclusive Women Fashion' }}',
    previewUrl: '{{ $previewUrl }}',
    cardPath: '',
    whatsappWebUrl: '{{ $whatsappWebUrl }}',
    isRendering: false,
    errorMessage: '',

    selectProduct(prod) {
        this.productId = prod.id;
        this.customTitle = prod.name;
        this.priceText = prod.special_offer_price_rupees ? 'Special: ₹' + Math.round(prod.special_offer_price_rupees) : (prod.suggested_selling_price_rupees ? '₹' + Math.round(prod.suggested_selling_price_rupees) : 'Special Offer');
        if (prod.primary_media_asset_id) {
            this.assetId = prod.primary_media_asset_id;
        } else if (prod.media_assets && prod.media_assets.length > 0) {
            this.assetId = prod.media_assets[0].id;
        }
        this.generateStatus();
    },

    selectAsset(asset) {
        this.assetId = asset.id;
        this.generateStatus();
    },

    generateStatus() {
        if (!this.assetId) return;
        this.isRendering = true;
        this.errorMessage = '';

        fetch('{{ route('admin.social-commerce.whatsapp-status.generate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                asset_id: this.assetId,
                product_id: this.productId,
                template: this.template,
                brand_name: this.brandName,
                tagline: this.tagline,
                whatsapp_number: this.whatsappNumber,
                cta_text: this.ctaText,
                price_text: this.priceText,
                custom_title: this.customTitle
            })
        })
        .then(res => res.json())
        .then(data => {
            this.isRendering = false;
            if (data.success) {
                this.previewUrl = data.card_url;
                this.cardPath = data.card_path;
                this.whatsappWebUrl = data.whatsapp_web_url;
            } else {
                this.errorMessage = data.message || 'Failed to generate status card';
            }
        })
        .catch(err => {
            this.isRendering = false;
            this.errorMessage = 'Network error during rendering';
        });
    }
}" class="space-y-6">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left Column: Settings, Picker & Overlays (7 cols) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- Product / Image Picker Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <span class="size-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">1</span>
                        Select Product or Media
                    </h3>
                    <span class="text-xs text-slate-500">Pick from catalog or uploaded assets</span>
                </div>

                <!-- Products Carousel / List -->
                @if($products->isNotEmpty())
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">Available Products</label>
                    <div class="flex gap-2.5 overflow-x-auto pb-2 scrollbar-thin">
                        @foreach($products as $prod)
                        <button type="button" @click="selectProduct({{ json_encode($prod) }})"
                                class="flex-shrink-0 flex items-center gap-2.5 p-2 rounded-xl border text-left transition-all hover:bg-slate-50"
                                :class="productId === {{ $prod->id }} ? 'border-amber-500 bg-amber-50/40 ring-2 ring-amber-500/20' : 'border-slate-200 bg-white'">
                            <img src="{{ $prod->primary_image_url }}" alt="{{ $prod->name }}" class="size-10 rounded-lg object-cover bg-slate-100">
                            <div class="max-w-[130px]">
                                <p class="text-xs font-semibold text-slate-900 truncate">{{ $prod->name }}</p>
                                <p class="text-[10px] text-slate-500">
                                    {{ $prod->special_offer_price_rupees ? '₹' . number_format($prod->special_offer_price_rupees, 0) : $prod->product_code }}
                                </p>
                            </div>
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Standalone Assets Grid -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">Or Choose from Media Inbox</label>
                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2 max-h-48 overflow-y-auto p-1">
                        @foreach($standaloneAssets as $ast)
                        <div @click="selectAsset({{ json_encode($ast) }})"
                             class="aspect-square rounded-xl overflow-hidden cursor-pointer border-2 transition-all"
                             :class="assetId === {{ $ast->id }} ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-slate-200 hover:border-slate-300'">
                            <img src="{{ $ast->url }}" alt="{{ $ast->file_name }}" class="w-full h-full object-cover">
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Customization & Branding Form Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        <span class="size-6 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">2</span>
                        Branding, Price &amp; WhatsApp CTA
                    </h3>
                    <span class="text-xs text-slate-400 font-mono">1080 &times; 1920 (9:16)</span>
                </div>

                <!-- Template Selector -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Select Layout Style</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach($templates as $code => $label)
                        <button type="button" @click="template = '{{ $code }}'; generateStatus()"
                                class="p-2.5 rounded-xl border text-center transition-all"
                                :class="template === '{{ $code }}' ? 'border-amber-500 bg-amber-50 text-amber-900 font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50 font-medium'">
                            <span class="text-xs block">{{ $label }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Headline</label>
                        <input type="text" x-model="customTitle" @change="generateStatus()"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Price Badge Text</label>
                        <input type="text" x-model="priceText" @change="generateStatus()" placeholder="e.g. Special: ₹899"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Brand Name</label>
                        <input type="text" x-model="brandName" @change="generateStatus()"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Brand Tagline</label>
                        <input type="text" x-model="tagline" @change="generateStatus()"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">WhatsApp Order Number</label>
                        <input type="text" x-model="whatsappNumber" @change="generateStatus()"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">CTA Button Text</label>
                        <input type="text" x-model="ctaText" @change="generateStatus()"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-2">
                    <button type="button" @click="generateStatus()" :disabled="isRendering"
                            class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 disabled:opacity-50 transition-colors flex items-center gap-1.5">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span x-text="isRendering ? 'Rendering 9:16 Canvas...' : 'Refresh Status Canvas'"></span>
                    </button>
                    <template x-if="errorMessage">
                        <span class="text-xs font-semibold text-rose-600" x-text="errorMessage"></span>
                    </template>
                </div>
            </div>

        </div>

        <!-- Right Column: Mobile 9:16 Mockup & Download CTAs (5 cols) -->
        <div class="lg:col-span-5 space-y-4 flex flex-col items-center">

            <!-- Smartphone Frame Simulator -->
            <div class="relative w-[280px] sm:w-[320px] aspect-[9/16] bg-slate-950 rounded-[40px] p-3 shadow-2xl border-[6px] border-slate-800 ring-1 ring-slate-900/50">

                <!-- Dynamic Island / Speaker Notch -->
                <div class="absolute top-4 left-1/2 -translate-x-1/2 w-24 h-5 bg-black rounded-full z-20 flex items-center justify-center">
                    <span class="size-2 rounded-full bg-slate-800 mr-2"></span>
                    <span class="size-2.5 rounded-full bg-slate-900 border border-slate-800"></span>
                </div>

                <!-- Status Screen Area -->
                <div class="w-full h-full rounded-[30px] overflow-hidden bg-slate-900 relative flex items-center justify-center">

                    <template x-if="isRendering">
                        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-xs flex flex-col items-center justify-center text-white z-30">
                            <div class="size-8 border-3 border-amber-400 border-t-transparent rounded-full animate-spin mb-2"></div>
                            <span class="text-xs font-semibold">Generating 1080&times;1920 Status...</span>
                        </div>
                    </template>

                    <template x-if="previewUrl">
                        <img :src="previewUrl" alt="Status Card Preview" class="w-full h-full object-cover">
                    </template>

                    <template x-if="!previewUrl && !isRendering">
                        <div class="p-6 text-center text-slate-400">
                            <svg class="size-12 mx-auto text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                            <p class="text-xs font-semibold text-slate-300">Select an image to preview 9:16 WhatsApp Status</p>
                        </div>
                    </template>

                    <!-- Home Bar Pill -->
                    <div class="absolute bottom-2 left-1/2 -translate-x-1/2 w-28 h-1 bg-white/40 rounded-full z-20"></div>
                </div>
            </div>

            <!-- Download & Export Actions Box -->
            <div class="w-full max-w-[320px] bg-white rounded-2xl border border-slate-200 p-4 shadow-xs space-y-2.5">
                <!-- Download Single Card Button -->
                <a :href="'{{ route('admin.social-commerce.whatsapp-status.download') }}?path=' + encodeURIComponent(cardPath)"
                   :class="!cardPath ? 'pointer-events-none opacity-50' : ''"
                   class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-2">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download 1080&times;1920 Status (JPG)
                </a>

                <!-- WhatsApp Web Direct Launch -->
                <a :href="whatsappWebUrl" target="_blank"
                   class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-2">
                    <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm.01 18.15c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.1 8.1 0 0 1-1.25-4.38c0-4.5 3.66-8.15 8.16-8.15 2.18 0 4.23.85 5.77 2.39a8.1 8.1 0 0 1 2.39 5.76c-.01 4.5-3.67 8.15-8.17 8.15zm4.47-6.11c-.25-.12-1.45-.72-1.68-.8-.22-.08-.39-.12-.55.12-.17.25-.64.8-.78.97-.15.17-.29.19-.54.07-.25-.12-1.04-.38-1.98-1.22-.73-.65-1.23-1.45-1.37-1.7-.15-.24-.02-.37.11-.49.11-.11.25-.29.37-.43.12-.15.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.12-.55-1.33-.76-1.82-.2-.48-.41-.41-.56-.42h-.48c-.17 0-.44.06-.67.31-.22.25-.87.85-.87 2.07 0 1.22.89 2.4 1.01 2.57.12.17 1.76 2.68 4.25 3.76.59.26 1.06.41 1.42.53.6.19 1.14.16 1.57.1.48-.07 1.45-.59 1.66-1.17.2-.57.2-1.06.14-1.17-.06-.1-.23-.17-.48-.29z"/>
                    </svg>
                    Send to Customer on WhatsApp
                </a>
            </div>

        </div>

    </div>

</div>
@endsection
