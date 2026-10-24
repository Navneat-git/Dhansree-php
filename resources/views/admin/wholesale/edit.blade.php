@extends('layouts.admin')

@section('title', 'Edit Wholesale Purchase Entry')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{
    purchaseType: '{{ $purchase->purchase_type }}',

    // Women Fashion State
    wf: {
        brandCode: '{{ addslashes($purchase->brand_code ?? 'DS') }}',
        purchaseDate: '{{ $purchase->purchase_date?->format('Y-m-d') ?? date('Y-m-d') }}',
        designNumber: '{{ addslashes($purchase->design_number ?? '') }}',
        categoryName: '{{ addslashes($purchase->category_name ?? 'KURTI') }}',
        vcode: '{{ addslashes($purchase->vcode ?? '') }}',
        categoryCode: '{{ addslashes($purchase->category_code ?? 'KT') }}',
        pcode: '{{ addslashes($purchase->pcode ?? '') }}',
        color: '{{ addslashes($purchase->color ?? '') }}',
        colorCode: '{{ addslashes($purchase->color_code ?? '') }}',
        size: '{{ addslashes($purchase->size ?? 'M') }}',
        sku: '{{ addslashes($purchase->sku ?? '') }}',
        status: '{{ addslashes($purchase->status ?? 'Available') }}',
        productName: '{{ addslashes($purchase->product_name ?? '') }}',
        threadCount: '{{ addslashes($purchase->thread_count ?? '') }}',
        cost: '{{ $purchase->cost ?? '' }}',
        gstPercent: {{ $purchase->gst_percent ?? 5.0 }},
        markup: {{ $purchase->markup ?? 1.5 }},
        imageUrl: '{{ addslashes($purchase->image_url ?? '') }}',
        notes: '{{ addslashes($purchase->notes ?? '') }}',

        updateCategoryCode() {
            const map = {
                'KURTI': 'KT',
                '2 SUIT SET': 'S2',
                '3 SUIT SET': 'S3',
                'CO-ORD SET': 'CS'
            };
            const upper = (this.categoryName || '').toUpperCase().trim();
            this.categoryCode = map[upper] || (upper.replace(/[^A-Z0-9]/g, '').slice(0, 2) || '');
            this.recalculateAll();
        },

        updateColorCode(colorMap) {
            if (colorMap[this.color]) {
                this.colorCode = colorMap[this.color];
            }
            this.recalculateAll();
        },

        recalculateAll() {
            let ddmmyy = '';
            if (this.purchaseDate) {
                const parts = this.purchaseDate.split('-');
                if (parts.length === 3) {
                    const yy = parts[0].slice(-2);
                    const mm = parts[1];
                    const dd = parts[2];
                    ddmmyy = `${dd}${mm}${yy}`;
                }
            }

            const v = (this.vcode || '').toUpperCase().trim();
            const des = (this.designNumber || '').trim();
            this.pcode = v + ddmmyy + des;

            const brand = (this.brandCode || 'DS').toUpperCase().slice(0, 2);
            let skuParts = [];
            if (this.categoryCode) skuParts.push(this.categoryCode.toUpperCase().trim());
            if (this.colorCode) skuParts.push(this.colorCode.toUpperCase().trim());
            if (this.size) skuParts.push(this.size.toUpperCase().trim());

            this.sku = brand + this.pcode + (skuParts.length ? '-' + skuParts.join('-') : '');
        },

        get gstAmount() {
            const c = parseFloat(this.cost) || 0;
            const g = parseFloat(this.gstPercent) || 0;
            return (c * (g / 100)).toFixed(2);
        },
        get totalCost() {
            const c = parseFloat(this.cost) || 0;
            const gst = parseFloat(this.gstAmount) || 0;
            return (c + gst).toFixed(2);
        },
        get sellingPrice() {
            const tc = parseFloat(this.totalCost) || 0;
            const m = parseFloat(this.markup) || 1;
            return (tc * m).toFixed(2);
        }
    },

    // Smart Living State
    sl: {
        sku: '{{ addslashes($purchase->sku ?? '') }}',
        productName: '{{ addslashes($purchase->product_name ?? '') }}',
        description: '{{ addslashes($purchase->description ?? '') }}',
        categoryId: '{{ $purchase->category_id ?? '' }}',
        costPrice: '{{ $purchase->cost_price ?? '' }}',
        markup: {{ $purchase->markup ?? 1.4 }},
        retailMarkup: {{ $purchase->retail_markup ?? 1.8 }},
        stockQty: {{ $purchase->stock_qty ?? 0 }},
        moq: '{{ addslashes($purchase->moq ?? '5 pcs') }}',
        brand: '{{ addslashes($purchase->brand ?? '') }}',
        model: '{{ addslashes($purchase->model ?? '') }}',
        badge: '{{ addslashes($purchase->badge ?? '') }}',
        status: '{{ addslashes($purchase->status ?? 'Available') }}',
        supplier: '{{ addslashes($purchase->supplier ?? '') }}',
        purchaseDate: '{{ $purchase->purchase_date?->format('Y-m-d') ?? '' }}',
        imageUrl: '{{ addslashes($purchase->image_url ?? '') }}',
        notes: '{{ addslashes($purchase->notes ?? '') }}',

        get wholesalePrice() {
            const c = parseFloat(this.costPrice) || 0;
            const m = parseFloat(this.markup) || 1;
            return (c * m).toFixed(2);
        },
        get retailPrice() {
            const c = parseFloat(this.costPrice) || 0;
            const rm = parseFloat(this.retailMarkup) || 1;
            return (c * rm).toFixed(2);
        }
    },

    colorMap: {
        @foreach($colorCodes as $c)
            '{{ addslashes($c->color_name) }}': '{{ $c->color_code }}',
        @endforeach
    },

    imagePreview: '{{ $purchase->effective_image_url }}',
    onFileSelected(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imagePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
}">
    <!-- Header Breadcrumbs -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.wholesale.index') }}" class="hover:underline">Wholesale Purchases</a>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-medium">Edit Entry #{{ $purchase->id }}</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Wholesale Purchase: {{ $purchase->product_name }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                SKU: <span class="font-mono font-bold text-slate-800">{{ $purchase->sku }}</span> &bull;
                Category Type: <strong class="uppercase text-amber-800">{{ str_replace('_', ' ', $purchase->purchase_type) }}</strong>
            </p>
        </div>

        <a href="{{ route('admin.wholesale.index', ['tab' => $purchase->purchase_type]) }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs">
            &larr; Back to Catalog Table
        </a>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.wholesale.update', $purchase) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        @if($purchase->purchase_type === \App\Models\WholesalePurchase::TYPE_WOMEN_FASHION)
            <!-- WOMEN FASHION EDIT -->
            <div class="space-y-6">
                <!-- Section 1 -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Product Identifiers & Auto-Generated Codes
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Brand Code</label>
                            <input type="text" name="brand_code" x-model="wf.brandCode" @input="wf.recalculateAll()"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono uppercase font-semibold text-slate-900">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Purchase Date</label>
                            <input type="date" name="purchase_date" x-model="wf.purchaseDate" @change="wf.recalculateAll()"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900" required>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Vendor Code (vcode)</label>
                            <input type="text" name="vcode" x-model="wf.vcode" @input="wf.recalculateAll()"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono uppercase text-slate-900" required>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Design Number</label>
                            <input type="text" name="design_number" x-model="wf.designNumber" @input="wf.recalculateAll()"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Category Name</label>
                            <select name="category_name" x-model="wf.categoryName" @change="wf.updateCategoryCode()"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900" required>
                                <option value="KURTI">KURTI</option>
                                <option value="2 SUIT SET">2 SUIT SET</option>
                                <option value="3 SUIT SET">3 SUIT SET</option>
                                <option value="CO-ORD SET">CO-ORD SET</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Category Code</label>
                            <input type="text" name="category_code" x-model="wf.categoryCode" @input="wf.recalculateAll()"
                                   class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-mono uppercase font-bold text-slate-800">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-purple-800 mb-1">pcode (Auto)</label>
                            <input type="text" name="pcode" x-model="wf.pcode" readonly
                                   class="w-full px-3 py-2 bg-purple-50 border border-purple-300 rounded-lg text-xs font-mono font-bold text-purple-900">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Size</label>
                            <select name="size" x-model="wf.size" @change="wf.recalculateAll()"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900" required>
                                <option value="S">S</option>
                                <option value="M">M</option>
                                <option value="L">L</option>
                                <option value="XL">XL</option>
                                <option value="2XL">2XL</option>
                                <option value="3XL">3XL</option>
                                <option value="4XL">4XL</option>
                                <option value="5XL">5XL</option>
                                <option value="Free Size">Free Size</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Color</label>
                            <select name="color" x-model="wf.color" @change="wf.updateColorCode(colorMap)"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900" required>
                                <option value="">-- Select Color --</option>
                                @foreach($colorCodes as $col)
                                    <option value="{{ $col->color_name }}">{{ $col->color_name }} ({{ $col->color_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Color Code</label>
                            <input type="text" name="color_code" x-model="wf.colorCode" @input="wf.recalculateAll()"
                                   class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-mono uppercase font-bold text-slate-800">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-amber-800 mb-1">Product SKU</label>
                            <div class="flex items-center gap-2">
                                <input type="text" name="sku" x-model="wf.sku"
                                       class="flex-1 px-3 py-2 bg-amber-50/70 border border-amber-300 rounded-lg text-xs font-mono font-bold text-amber-950">
                                <button type="button" @click="wf.recalculateAll()"
                                        class="px-3 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-semibold">
                                    Regenerate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2 -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Product Details & Pricing
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Product Title / Name</label>
                            <input type="text" name="product_name" x-model="wf.productName"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900" required>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Thread Count</label>
                            <input type="text" name="thread_count" x-model="wf.threadCount"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Cost (₹)</label>
                            <input type="number" step="0.01" name="cost" x-model="wf.cost"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold text-slate-900" required>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">GST Rate</label>
                            <select name="gst_percent" x-model="wf.gstPercent"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                                <option value="5">5%</option>
                                <option value="12">12%</option>
                                <option value="18">18%</option>
                                <option value="0">0%</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Total Cost (₹)</label>
                            <div class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold text-slate-900">
                                ₹<span x-text="wf.totalCost"></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Markup</label>
                            <input type="number" step="0.05" name="markup" x-model="wf.markup"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-emerald-800 mb-1">Calculated Selling Price</label>
                            <div class="px-3 py-2 bg-emerald-100 border border-emerald-300 rounded-lg text-xs font-mono font-bold text-emerald-950">
                                ₹<span x-text="wf.sellingPrice"></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                            <select name="status" x-model="wf.status"
                                    class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                                <option value="Available">Available</option>
                                <option value="Not Available">Not Available</option>
                                <option value="In Production">In Production</option>
                                <option value="Out of Stock">Out of Stock</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Image URL</label>
                            <input type="text" name="image_url" x-model="wf.imageUrl"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- SMART LIVING EDIT -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    Gadget Specification & Stock
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">SKU</label>
                        <input type="text" name="sku" x-model="sl.sku"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold text-slate-900" required>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name</label>
                        <input type="text" name="product_name" x-model="sl.productName"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Category</label>
                        <select name="category_id" x-model="sl.categoryId"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Brand</label>
                        <input type="text" name="brand" x-model="sl.brand"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Model</label>
                        <input type="text" name="model" x-model="sl.model"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Badge</label>
                        <input type="text" name="badge" x-model="sl.badge"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Stock Qty</label>
                        <input type="number" name="stock_qty" x-model="sl.stockQty"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">MOQ</label>
                        <input type="text" name="moq" x-model="sl.moq"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier</label>
                        <input type="text" name="supplier" x-model="sl.supplier"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Purchase Date</label>
                        <input type="date" name="purchase_date" x-model="sl.purchaseDate"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>
                </div>

                <!-- Pricing -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cost Price (₹)</label>
                        <input type="number" step="0.01" name="cost_price" x-model="sl.costPrice"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Markup</label>
                        <input type="number" step="0.05" name="markup" x-model="sl.markup"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-800 mb-1">Wholesale Price (₹)</label>
                        <div class="px-3 py-2 bg-amber-100 border border-amber-300 rounded-lg text-xs font-mono font-bold text-amber-950">
                            ₹<span x-text="sl.wholesalePrice"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Retail Markup</label>
                        <input type="number" step="0.05" name="retail_markup" x-model="sl.retailMarkup"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-emerald-800 mb-1">Retail Price (₹)</label>
                        <div class="px-3 py-2 bg-emerald-100 border border-emerald-300 rounded-lg text-xs font-mono font-bold text-emerald-950">
                            ₹<span x-text="sl.retailPrice"></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status" x-model="sl.status"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                            <option value="Available">Available</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Not Available">Not Available</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Image URL</label>
                        <input type="text" name="image_url" x-model="sl.imageUrl"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="2" x-model="sl.description"
                              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900"></textarea>
                </div>
            </div>
        @endif

        <!-- Image Upload Section -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Replace or Upload Product Image
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-center">
                <div class="sm:col-span-2 space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Choose new image file (leave empty to keep current)</label>
                    <input type="file" name="image" accept="image/*" @change="onFileSelected($event)"
                           class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100 border border-slate-300 rounded-lg cursor-pointer">
                </div>

                <div class="text-center sm:text-left">
                    <span class="block text-[11px] font-semibold text-slate-500 mb-1.5">Current Thumbnail</span>
                    <div class="w-20 h-20 rounded-lg border border-slate-300 bg-slate-50 p-1 flex items-center justify-center overflow-hidden shadow-2xs">
                        <template x-if="imagePreview">
                            <img :src="imagePreview" class="w-full h-full object-contain rounded">
                        </template>
                        <template x-if="!imagePreview">
                            <span class="text-[10px] text-slate-400 text-center">No image</span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.wholesale.index', ['tab' => $purchase->purchase_type]) }}"
               class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-xs transition-all">
                Update Wholesale Entry
            </button>
        </div>
    </form>
</div>
@endsection
