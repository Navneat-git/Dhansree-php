@extends('layouts.admin')

@section('title', $product->product_code . ' - ' . $product->name)
@section('page_title', $product->product_code . ': ' . $product->name)

@section('header_actions')
<div class="flex flex-wrap items-center gap-2">
    <!-- WhatsApp Status Generator -->
    <a href="{{ route('admin.social-commerce.whatsapp-status.index', ['product_id' => $product->id]) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 shadow-xs transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
        </svg>
        WhatsApp Status (9:16)
    </a>

    <!-- ZIP Download -->
    @if($product->mediaAssets->isNotEmpty() || $product->primaryMedia)
    <a href="{{ route('admin.social-commerce.whatsapp-status.download-zip', $product) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-900 text-white hover:bg-slate-800 shadow-xs transition-colors">
        <svg class="size-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        Download Status ZIP
    </a>
    @endif

    <!-- Edit -->
    <a href="{{ route('admin.social-commerce.products.edit', $product) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 text-slate-950 hover:bg-amber-400 shadow-xs transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
        </svg>
        Edit Product
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Top Summary Banner -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="size-16 rounded-xl object-cover bg-slate-100 border border-slate-200 shrink-0">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-slate-500 uppercase">{{ $product->product_code }}</span>
                    @php
                        $badgeClass = match($product->availability_status) {
                            'AVAILABLE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'NEW' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'TEMPORARILY_UNAVAILABLE' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'OUT_OF_STOCK' => 'bg-rose-50 text-rose-700 border-rose-200',
                            default => 'bg-slate-50 text-slate-700 border-slate-200',
                        };
                    @endphp
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border {{ $badgeClass }}">
                        {{ str_replace('_', ' ', $product->availability_status) }}
                    </span>
                    @if($product->is_promoted_to_web)
                        <span class="text-xs font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-300">
                            Live on Website (Product #{{ $product->web_product_id }})
                        </span>
                    @endif
                </div>
                <h2 class="text-lg font-bold text-slate-900 mt-0.5">{{ $product->name }}</h2>
                <p class="text-xs text-slate-500">
                    Category: <span class="font-medium text-slate-700">{{ $product->category_name }}</span>
                    &bull; Added {{ $product->created_at->format('d M Y') }}
                    @if($product->creator)
                        by {{ $product->creator->name }}
                    @endif
                </p>
            </div>
        </div>

        <!-- Quick Status Switcher Form -->
        <form method="POST" action="{{ route('admin.social-commerce.products.availability', $product) }}" class="flex items-center gap-2">
            @csrf
            <label class="text-xs font-semibold text-slate-600">Update Status:</label>
            <select name="availability_status" onchange="this.form.submit()" class="text-xs font-semibold rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                <option value="NEW" {{ $product->availability_status === 'NEW' ? 'selected' : '' }}>NEW</option>
                <option value="AVAILABLE" {{ $product->availability_status === 'AVAILABLE' ? 'selected' : '' }}>AVAILABLE</option>
                <option value="TEMPORARILY_UNAVAILABLE" {{ $product->availability_status === 'TEMPORARILY_UNAVAILABLE' ? 'selected' : '' }}>TEMPORARILY UNAVAILABLE</option>
                <option value="OUT_OF_STOCK" {{ $product->availability_status === 'OUT_OF_STOCK' ? 'selected' : '' }}>OUT OF STOCK</option>
                <option value="ARCHIVED" {{ $product->availability_status === 'ARCHIVED' ? 'selected' : '' }}>ARCHIVED</option>
            </select>
        </form>
    </div>

    <!-- Main Content 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Details & Media Gallery -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Product Specs -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2 mb-4">
                    Product Details &amp; Specifications
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block">Supplier</span>
                        <span class="font-semibold text-slate-900 mt-0.5 block">
                            {{ $product->supplier?->name ?? 'WhatsApp Wholesale Group' }}
                        </span>
                        @if($product->supplier?->whatsapp_group_name)
                            <span class="text-[10px] text-slate-500 font-mono">{{ $product->supplier->whatsapp_group_name }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-slate-400 block">Supplier Product Code</span>
                        <span class="font-semibold font-mono text-slate-900 mt-0.5 block">
                            {{ $product->supplier_product_code ?: '—' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Fabric</span>
                        <span class="font-semibold text-slate-900 mt-0.5 block">
                            {{ $product->fabric ?: 'Standard Women Fashion' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Available Colours</span>
                        <span class="font-medium text-slate-900 mt-0.5 block">
                            {{ is_array($product->available_colors) && count($product->available_colors) ? implode(', ', $product->available_colors) : 'As per design' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Available Sizes</span>
                        <span class="font-medium text-slate-900 mt-0.5 block">
                            {{ is_array($product->available_sizes) && count($product->available_sizes) ? implode(', ', $product->available_sizes) : 'Standard / Free' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 block">Last Availability Check</span>
                        <span class="font-medium text-slate-900 mt-0.5 block">
                            {{ $product->last_availability_confirmed_at ? $product->last_availability_confirmed_at->format('d M Y, H:i') : 'Never' }}
                        </span>
                    </div>
                </div>

                <!-- Pricing Cards -->
                <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-3 gap-3">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60">
                        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">Supplier Cost</span>
                        <span class="text-base font-bold text-slate-900 mt-0.5 block">
                            {{ $product->purchase_price_rupees ? '₹' . number_format($product->purchase_price_rupees, 0) : '—' }}
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60">
                        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">Suggested MRP</span>
                        <span class="text-base font-bold text-slate-900 mt-0.5 block">
                            {{ $product->suggested_selling_price_rupees ? '₹' . number_format($product->suggested_selling_price_rupees, 0) : '—' }}
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200/60">
                        <span class="text-[10px] font-semibold text-emerald-700 uppercase tracking-wider block">Special Offer Price</span>
                        <span class="text-base font-bold text-emerald-800 mt-0.5 block">
                            {{ $product->special_offer_price_rupees ? '₹' . number_format($product->special_offer_price_rupees, 0) : '—' }}
                        </span>
                    </div>
                </div>

                @if($product->description)
                <div class="mt-4 pt-3 border-t border-slate-100">
                    <span class="text-xs font-semibold text-slate-700 block mb-1">Description</span>
                    <p class="text-xs text-slate-600 leading-relaxed">{{ $product->description }}</p>
                </div>
                @endif

                @if($product->notes)
                <div class="mt-3 p-3 rounded-xl bg-amber-50/70 border border-amber-200/60 text-xs text-amber-900">
                    <span class="font-bold block mb-0.5">Supplier / Ordering Notes:</span>
                    <p class="whitespace-pre-line text-[11px]">{{ $product->notes }}</p>
                </div>
                @endif
            </div>

            <!-- Media Gallery Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Attached Images &amp; Videos ({{ $product->mediaAssets->count() }})
                    </h3>
                    <a href="{{ route('admin.social-commerce.whatsapp-status.index', ['product_id' => $product->id]) }}"
                       class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                        Generate Status Cards &rarr;
                    </a>
                </div>

                @if($product->mediaAssets->isEmpty() && !$product->primaryMedia)
                <p class="text-xs text-slate-400 text-center py-6">No media attached to this product yet.</p>
                @else
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                    @foreach($product->mediaAssets as $media)
                    <div class="group relative aspect-square rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                        <img src="{{ $media->url }}" alt="{{ $media->file_name }}" class="w-full h-full object-cover">
                        @if($media->id === $product->primary_media_asset_id)
                            <span class="absolute top-1 left-1 px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-500 text-slate-950 shadow-xs">
                                PRIMARY
                            </span>
                        @endif
                        <div class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 p-1">
                            <a href="{{ route('admin.social-commerce.whatsapp-status.index', ['asset_id' => $media->id, 'product_id' => $product->id]) }}"
                               class="p-1.5 rounded-lg bg-emerald-600 text-white text-[10px] font-bold" title="Generate WhatsApp Status">
                                Status
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>

        <!-- Right 1 Col: Caption Generator & Web Publishing -->
        <div class="space-y-6">

            <!-- Caption Generator Card -->
            <div x-data="{
                copied: false,
                currentTone: 'new_arrival',
                captionText: `{{ $instagramCaption['full_caption'] }}`,
                copyCaption() {
                    navigator.clipboard.writeText(this.captionText);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                },
                switchTone(tone) {
                    this.currentTone = tone;
                    fetch('{{ route('admin.social-commerce.products.generate-caption', $product) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ tone: tone, platform: 'INSTAGRAM' })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.captionText = data.data.full_caption;
                        }
                    });
                }
            }" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-1.5">
                        <svg class="size-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Caption Generator
                    </h3>
                    <span class="text-[10px] text-slate-400">Strict Product Attributes</span>
                </div>

                <!-- Tone Select -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tone &amp; Preset</label>
                    <select x-model="currentTone" @change="switchTone($event.target.value)"
                            class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                        @foreach($tones as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Caption Preview Box -->
                <div class="relative">
                    <textarea x-model="captionText" rows="10"
                              class="w-full font-mono text-[11px] text-slate-800 bg-slate-50 rounded-xl border border-slate-200 p-3 leading-relaxed focus:bg-white focus:border-amber-500 focus:ring-amber-500"></textarea>
                </div>

                <div class="flex items-center justify-between gap-2 pt-1">
                    <button type="button" @click="copyCaption()"
                            class="w-full py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs shadow-xs transition-colors flex items-center justify-center gap-1.5">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.849A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.599m7.332 0c.055.194.084.4.084.615v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.215.03-.421.084-.615m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                        </svg>
                        <span x-text="copied ? 'Copied to Clipboard!' : 'Copy Caption & Hashtags'"></span>
                    </button>
                </div>
            </div>

            <!-- Publish to Website Store Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                    <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.614A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72m-13.5 8.65h.01m0 0h.01" />
                        </svg>
                    </span>
                    <h3 class="text-sm font-semibold text-slate-900">E-Commerce Storefront</h3>
                </div>

                @if($product->is_promoted_to_web && $product->webProduct)
                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 space-y-1.5">
                        <p class="font-bold">Published on Public Website Store</p>
                        <p class="text-[11px] text-emerald-700">Product: {{ $product->webProduct->name }} (#{{ $product->webProduct->id }})</p>
                        <a href="{{ url('/product/' . $product->webProduct->slug) }}" target="_blank" class="inline-flex items-center gap-1 font-semibold text-emerald-900 underline mt-1">
                            View Live Product Page &rarr;
                        </a>
                    </div>
                @else
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Supplier products are isolated by default. Clicking the button below will create a purchasable store listing on Dhansree with pricing and image.
                    </p>

                    <form method="POST" action="{{ route('admin.social-commerce.products.promote-to-web', $product) }}"
                          onsubmit="return confirm('Are you sure you want to publish this supplier product to the public Dhansree website store?');">
                        @csrf
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-1.5">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Publish to Website Catalog
                        </button>
                    </form>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
