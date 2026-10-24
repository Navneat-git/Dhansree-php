@extends('layouts.admin')

@section('title', 'Add Wholesale Purchase Entry')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{
    // Category Selector: 'women_fashion' or 'smart_living'
    purchaseType: '{{ $type ?? 'women_fashion' }}',

    // Women Fashion State
    wf: {
        brandCode: 'DS',
        purchaseDate: '{{ date('Y-m-d') }}',
        designNumber: '',
        categoryName: 'KURTI',
        vcode: '',
        categoryCode: 'KT',
        pcode: '',
        color: '',
        colorCode: '',
        sizes: ['S', 'M', 'L', 'XL'],
        availableSizes: ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL', 'Free Size'],
        sku: '',
        status: 'Available',
        productName: '',
        threadCount: '60x60',
        cost: '',
        gstPercent: 5.0,
        markup: 1.5,
        imageUrl: '',
        notes: '',

        toggleSize(s) {
            const idx = this.sizes.indexOf(s);
            if (idx === -1) {
                this.sizes.push(s);
            } else {
                this.sizes.splice(idx, 1);
            }
            this.recalculateAll();
        },

        selectCommon() {
            this.sizes = ['S', 'M', 'L', 'XL', '2XL', '3XL'];
            this.recalculateAll();
        },

        selectAll() {
            this.sizes = [...this.availableSizes];
            this.recalculateAll();
        },

        clearAll() {
            this.sizes = [];
            this.recalculateAll();
        },

        get generatedSkusList() {
            const brand = (this.brandCode || 'DS').toUpperCase().slice(0, 2);
            const p = this.pcode || '';
            const cat = this.categoryCode ? this.categoryCode.toUpperCase().trim() : '';
            const col = this.colorCode ? this.colorCode.toUpperCase().trim() : '';

            if (!this.sizes.length) {
                let parts = [];
                if (cat) parts.push(cat);
                if (col) parts.push(col);
                return [{
                    size: 'No size',
                    sku: brand + p + (parts.length ? '-' + parts.join('-') : '')
                }];
            }

            return this.sizes.map(sz => {
                let parts = [];
                if (cat) parts.push(cat);
                if (col) parts.push(col);
                if (sz) parts.push(sz.toUpperCase().trim());
                return {
                    size: sz,
                    sku: brand + p + (parts.length ? '-' + parts.join('-') : '')
                };
            });
        },

        // Update Category Code based on Category Name
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

        // Update Color Code based on selected Color
        updateColorCode(colorMap) {
            if (colorMap[this.color]) {
                this.colorCode = colorMap[this.color];
            }
            this.recalculateAll();
        },

        // Auto-generate pcode and SKU
        recalculateAll() {
            // 1. Date to ddmmyy
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

            // 2. pcode = vcode + ddmmyy + designNumber
            const v = (this.vcode || '').toUpperCase().trim();
            const des = (this.designNumber || '').trim();
            this.pcode = v + ddmmyy + des;

            // 3. First generated sku for single display
            const brand = (this.brandCode || 'DS').toUpperCase().slice(0, 2);
            let skuParts = [];
            if (this.categoryCode) skuParts.push(this.categoryCode.toUpperCase().trim());
            if (this.colorCode) skuParts.push(this.colorCode.toUpperCase().trim());
            const firstSize = this.sizes.length ? this.sizes[0] : '';
            if (firstSize) skuParts.push(firstSize.toUpperCase().trim());

            this.sku = brand + this.pcode + (skuParts.length ? '-' + skuParts.join('-') : '');
        },

        // Financial calculations
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
        sku: '',
        nextSerial: '{{ $nextSmartLivingSerial ?? '001' }}',
        productName: '',
        description: '',
        categoryId: '',
        costPrice: '',
        markup: 1.4,
        retailMarkup: 1.8,
        stockQty: 100,
        moq: '5 pcs',
        brand: 'Generic',
        model: '',
        badge: 'POPULAR',
        status: 'Available',
        supplier: '',
        purchaseDate: '{{ date('Y-m-d') }}',
        imageUrl: '',
        notes: '',

        formatSku() {
            let raw = (this.sku || '').trim().toUpperCase();
            if (!raw) return;

            // If it already ends with 3+ digits preceded by a hyphen, keep it (e.g. -061, -062)
            if (/-\d{3,}$/.test(raw)) {
                this.sku = raw;
                return;
            }

            // If it ends with 1-2 digits, e.g. -62 -> pad to -062
            const twoDigitMatch = raw.match(/^(.*?)-(\d{1,2})$/);
            if (twoDigitMatch) {
                this.sku = twoDigitMatch[1] + '-' + twoDigitMatch[2].padStart(3, '0');
                return;
            }

            // Strip trailing dashes, underscores, spaces
            const base = raw.replace(/[-_\s]+$/, '');
            this.sku = base + '-' + this.nextSerial;
        },

        get previewSku() {
            let raw = (this.sku || '').trim().toUpperCase();
            if (!raw) return '';
            if (/-\d{3,}$/.test(raw)) return raw;
            const twoDigitMatch = raw.match(/^(.*?)-(\d{1,2})$/);
            if (twoDigitMatch) {
                return twoDigitMatch[1] + '-' + twoDigitMatch[2].padStart(3, '0');
            }
            return raw.replace(/[-_\s]+$/, '') + '-' + this.nextSerial;
        },

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

    // Color Map lookup
    colorMap: {
        @foreach($colorCodes as $c)
            '{{ addslashes($c->color_name) }}': '{{ $c->color_code }}',
        @endforeach
    },

    imagePreview: null,
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
}" x-init="wf.updateCategoryCode(); wf.recalculateAll();">

    <!-- Header Breadcrumbs -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.wholesale.index') }}" class="hover:underline">Wholesale Purchases</a>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-medium">New Purchase Entry</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Add Wholesale Product Purchase</h1>
            <p class="text-xs text-slate-500 mt-1">Select the product category below to load the customized entry form.</p>
        </div>

        <a href="{{ route('admin.wholesale.index') }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-2xs">
            &larr; Back to Catalog Table
        </a>
    </div>

    <!-- Step 1: Category Selection Cards -->
    <div class="space-y-2">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
            Step 1: Which category are you adding this purchase entry for?
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Option A: Women Fashion -->
            <button type="button"
                    @click="purchaseType = 'women_fashion'"
                    :class="purchaseType === 'women_fashion' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/40' : 'border-slate-200 bg-white hover:border-slate-300'"
                    class="p-4 rounded-xl border text-left transition-all relative flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                     :class="purchaseType === 'women_fashion' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-600'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900">Women Fashion</h3>
                        <span x-show="purchaseType === 'women_fashion'" class="text-xs font-bold text-amber-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Selected
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Kurtis, 2 Suit Sets, 3 Suit Sets, Co-ord Sets, etc.</p>
                    <div class="flex items-center gap-2 mt-2 text-[11px] text-slate-400">
                        <span>Auto Pcode</span> &bull; <span>Auto SKU</span> &bull; <span>GST 5%</span> &bull; <span>Color Codes</span>
                    </div>
                </div>
            </button>

            <!-- Option B: Smart Living & Gadgets -->
            <button type="button"
                    @click="purchaseType = 'smart_living'"
                    :class="purchaseType === 'smart_living' ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/40' : 'border-slate-200 bg-white hover:border-slate-300'"
                    class="p-4 rounded-xl border text-left transition-all relative flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                     :class="purchaseType === 'smart_living' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900">Smart Living & Gadgets</h3>
                        <span x-show="purchaseType === 'smart_living'" class="text-xs font-bold text-blue-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Selected
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Fast Chargers, Cables, Earphones, Smart Gadgets, Accessories, etc.</p>
                    <div class="flex items-center gap-2 mt-2 text-[11px] text-slate-400">
                        <span>Wholesale Price</span> &bull; <span>Retail Price</span> &bull; <span>Stock Qty</span> &bull; <span>MOQ</span>
                    </div>
                </div>
            </button>
        </div>
    </div>

    <!-- FORM START -->
    <form method="POST" action="{{ route('admin.wholesale.store') }}" @submit="if (purchaseType === 'smart_living') sl.formatSku()" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <input type="hidden" name="purchase_type" :value="purchaseType">

        <!-- ========================================== -->
        <!-- FORM A: WOMEN FASHION                      -->
        <!-- ========================================== -->
        <fieldset :disabled="purchaseType !== 'women_fashion'" x-show="purchaseType === 'women_fashion'" class="space-y-6 border-0 p-0 m-0 min-w-0">
            <!-- SECTION 1: Product Identifiers & Codes -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Product Identifiers & Auto-Generated Codes
                    </h3>
                    <div class="text-[11px] text-slate-400">
                        SKU and Pcode will update dynamically as you type
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Brand Code -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Brand Code</label>
                        <input type="text" name="brand_code" x-model="wf.brandCode" @input="wf.recalculateAll()"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono uppercase font-semibold text-slate-900">
                        <p class="text-[10px] text-slate-400 mt-1">Default is 'DS' (Dhansree)</p>
                    </div>

                    <!-- Purchase Date -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Purchase Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="purchase_date" x-model="wf.purchaseDate" @change="wf.recalculateAll()"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900"
                               :required="purchaseType === 'women_fashion'">
                        <p class="text-[10px] text-slate-400 mt-1">Formats as ddmmyy in Pcode</p>
                    </div>

                    <!-- Vendor / Manufacturer Code (vcode) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Vendor Code (vcode) <span class="text-rose-500">*</span></label>
                        <input type="text" name="vcode" x-model="wf.vcode" @input="wf.recalculateAll()"
                               placeholder="e.g. ICF"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono uppercase text-slate-900"
                               :required="purchaseType === 'women_fashion'">
                        <p class="text-[10px] text-slate-400 mt-1">Supplier code (e.g. ICF)</p>
                    </div>

                    <!-- Design Number -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Design Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="design_number" x-model="wf.designNumber" @input="wf.recalculateAll()"
                               placeholder="e.g. 10"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900"
                               :required="purchaseType === 'women_fashion'">
                        <p class="text-[10px] text-slate-400 mt-1">Design / lot number</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 pt-2">
                    <!-- Category Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Category Name <span class="text-rose-500">*</span></label>
                        <select name="category_name" x-model="wf.categoryName" @change="wf.updateCategoryCode()"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900"
                                :required="purchaseType === 'women_fashion'">
                            <option value="KURTI">KURTI</option>
                            <option value="2 SUIT SET">2 SUIT SET</option>
                            <option value="3 SUIT SET">3 SUIT SET</option>
                            <option value="CO-ORD SET">CO-ORD SET</option>
                        </select>
                    </div>

                    <!-- Category Code (Auto) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Category Code (Auto)</label>
                        <input type="text" name="category_code" x-model="wf.categoryCode" @input="wf.recalculateAll()"
                               class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-mono uppercase font-bold text-slate-800">
                        <p class="text-[10px] text-slate-400 mt-1">KT, S2, S3, CS</p>
                    </div>

                    <!-- pcode (Auto) -->
                    <div>
                        <label class="block text-xs font-semibold text-purple-800 mb-1">Product Code (pcode)</label>
                        <input type="text" name="pcode" x-model="wf.pcode" readonly
                               class="w-full px-3 py-2 bg-purple-50 border border-purple-300 rounded-lg text-xs font-mono font-bold text-purple-900">
                        <p class="text-[10px] text-purple-600 mt-1">vcode + ddmmyy + designNo</p>
                    </div>

                    <!-- Color -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Color <span class="text-rose-500">*</span></label>
                        <select name="color" x-model="wf.color" @change="wf.updateColorCode(colorMap)"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900"
                                :required="purchaseType === 'women_fashion'">
                            <option value="">-- Select Color --</option>
                            @foreach($colorCodes as $col)
                                <option value="{{ $col->color_name }}">{{ $col->color_name }} ({{ $col->color_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Color Code (Auto) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Color Code (Auto)</label>
                        <input type="text" name="color_code" x-model="wf.colorCode" @input="wf.recalculateAll()"
                               placeholder="e.g. BLU"
                               class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-xs font-mono uppercase font-bold text-slate-800">
                        <p class="text-[10px] text-slate-400 mt-1">e.g. BLU, GRN</p>
                    </div>
                </div>

                <!-- MULTI-SIZE SELECTION & MULTI-ROW SKU GENERATION -->
                <div class="mt-4 p-4 rounded-xl border border-amber-200 bg-amber-50/40 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-amber-200/60 pb-2.5">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white uppercase">Multi-Size Enabled</span>
                                <h4 class="text-xs font-bold text-amber-950">Select Sizes for this Product (Creates 1 row per size)</h4>
                            </div>
                            <p class="text-[11px] text-amber-800/80 mt-0.5">
                                Select all sizes this design comes in. Each size will automatically receive its own unique SKU and create a separate row in the table!
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
                            <button type="button" @click="wf.selectCommon()"
                                    class="px-2.5 py-1 rounded bg-white hover:bg-amber-100 text-[11px] font-semibold text-amber-900 border border-amber-300 shadow-2xs transition-colors">
                                Standard (S-3XL)
                            </button>
                            <button type="button" @click="wf.selectAll()"
                                    class="px-2.5 py-1 rounded bg-white hover:bg-amber-100 text-[11px] font-semibold text-amber-900 border border-amber-300 shadow-2xs transition-colors">
                                Select All
                            </button>
                            <button type="button" @click="wf.clearAll()"
                                    class="px-2.5 py-1 rounded bg-white hover:bg-slate-100 text-[11px] font-medium text-slate-600 border border-slate-300 shadow-2xs transition-colors">
                                Clear
                            </button>
                        </div>
                    </div>

                    <!-- Size Pills -->
                    <div class="flex flex-wrap items-center gap-2">
                        <template x-for="sz in wf.availableSizes" :key="sz">
                            <button type="button"
                                    @click="wf.toggleSize(sz)"
                                    :class="wf.sizes.includes(sz) ? 'bg-amber-600 text-white font-bold border-amber-600 shadow-xs ring-2 ring-amber-500/20' : 'bg-white text-slate-700 border-slate-300 hover:border-amber-500 hover:bg-amber-50/50'"
                                    class="px-3 py-1.5 rounded-lg border text-xs transition-all flex items-center gap-1.5">
                                <span x-show="wf.sizes.includes(sz)" class="text-[11px]">✓</span>
                                <span x-text="sz"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Hidden inputs for each selected size -->
                    <template x-for="sz in wf.sizes" :key="'input_' + sz">
                        <input type="hidden" name="sizes[]" :value="sz">
                    </template>

                    <!-- Real-Time Multi-SKU Generation Preview -->
                    <div class="mt-3 p-3 rounded-lg bg-white border border-amber-200 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Generating <strong class="text-amber-700" x-text="wf.sizes.length"></strong> Wholesale Entries (1 row per size):
                            </span>
                            <button type="button"
                                    x-show="wf.sizes.length > 0"
                                    @click="navigator.clipboard.writeText(wf.generatedSkusList.map(x => x.sku).join('\n')); alert('Copied ' + wf.sizes.length + ' SKUs to clipboard!')"
                                    class="text-[11px] font-semibold text-amber-700 hover:underline">
                                Copy All SKUs
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <template x-for="item in wf.generatedSkusList" :key="item.sku">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 border border-amber-200/80 text-amber-950 text-xs font-mono">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white" x-text="item.size"></span>
                                    <span class="font-semibold" x-text="item.sku"></span>
                                </div>
                            </template>
                        </div>

                        <div x-show="!wf.sizes.length" class="text-xs text-rose-600 font-semibold py-1">
                            ⚠️ Please select at least one size above to generate the SKU and entries.
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Title, Fabric Details & Pricing -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3">
                    Product Details & Selling Price Calculation
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Title / Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="product_name" x-model="wf.productName"
                               placeholder="e.g. Pure Cotton Anarkali Kurti with Dupatta"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900"
                               :required="purchaseType === 'women_fashion'">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Thread Count</label>
                        <input type="text" name="thread_count" x-model="wf.threadCount"
                               placeholder="e.g. 60x60, 40s Cambric"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>
                </div>

                <!-- Financial Calculation Matrix -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cost (₹) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="cost" x-model="wf.cost"
                               placeholder="500.00"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold text-slate-900"
                               :required="purchaseType === 'women_fashion'">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">GST Rate</label>
                        <select name="gst_percent" x-model="wf.gstPercent"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                            <option value="5">5% (Standard)</option>
                            <option value="12">12%</option>
                            <option value="18">18%</option>
                            <option value="0">0% (Nil)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Total Cost (₹)</label>
                        <div class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-mono font-bold text-slate-900">
                            ₹<span x-text="wf.totalCost"></span>
                        </div>
                        <p class="text-[9px] text-slate-400 mt-0.5">Includes GST: ₹<span x-text="wf.gstAmount"></span></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Markup (Multiplier)</label>
                        <input type="number" step="0.05" name="markup" x-model="wf.markup"
                               placeholder="1.5"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-emerald-800 mb-1">Calculated Selling Price</label>
                        <div class="px-3 py-2 bg-emerald-100 border border-emerald-300 rounded-lg text-xs font-mono font-bold text-emerald-950">
                            ₹<span x-text="wf.sellingPrice"></span>
                        </div>
                        <p class="text-[9px] text-emerald-700 mt-0.5">Total Cost &times; Markup</p>
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
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Image URL (Direct / CDN)</label>
                        <input type="text" name="image_url" x-model="wf.imageUrl"
                               placeholder="https://... (or leave empty to upload image file below)"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- ========================================== -->
        <!-- FORM B: SMART LIVING & GADGETS             -->
        <!-- ========================================== -->
        <fieldset :disabled="purchaseType !== 'smart_living'" x-show="purchaseType === 'smart_living'" class="space-y-6 border-0 p-0 m-0 min-w-0">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        Gadget Specification & Stock
                    </h3>
                    <div class="text-[11px] text-slate-400">
                        Wholesale Price and Retail Price calculated automatically
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-slate-700">
                                SKU <span class="text-rose-500">*</span>
                            </label>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                Next Serial: <strong x-text="sl.nextSerial"></strong>
                            </span>
                        </div>
                        <div class="relative">
                            <input type="text" name="sku" x-model="sl.sku"
                                   @input="sl.sku = sl.sku.toUpperCase()"
                                   @blur="sl.formatSku()"
                                   placeholder="e.g. DS-JWL-BOX"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold text-slate-900 pr-16"
                                   :required="purchaseType === 'smart_living'">
                            <button type="button"
                                    @click="sl.formatSku()"
                                    title="Auto-append next serial"
                                    class="absolute right-1 top-1 bottom-1 px-2.5 rounded bg-blue-100/70 hover:bg-blue-200 text-blue-800 text-[10px] font-mono font-bold border border-blue-300 transition-colors flex items-center gap-1">
                                <span>+</span><span x-text="sl.nextSerial"></span>
                            </button>
                        </div>
                        <div class="mt-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-[11px]">
                            <div class="text-slate-500 flex items-center gap-1">
                                <span>Preview:</span>
                                <span class="font-mono font-bold text-blue-900 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200"
                                      x-text="sl.previewSku || ('DS-EXAMPLE-' + sl.nextSerial)"></span>
                            </div>
                            <span class="text-[10px] text-slate-400">Typing <code class="font-mono text-slate-600">DS-JWL-BOX</code> auto-appends <strong class="text-blue-700 font-mono" x-text="'-' + sl.nextSerial"></strong></span>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="product_name" x-model="sl.productName"
                               placeholder="e.g. Fast Charger 80W Type-C"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900"
                               :required="purchaseType === 'smart_living'">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Store Category</label>
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
                               placeholder="e.g. Generic, Vivo, boAt"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Model</label>
                        <input type="text" name="model" x-model="sl.model"
                               placeholder="e.g. CHG-80W"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Badge</label>
                        <input type="text" name="badge" x-model="sl.badge"
                               placeholder="e.g. POPULAR, BESTSELLER"
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
                        <label class="block text-xs font-semibold text-slate-700 mb-1">MOQ (Minimum Order Qty)</label>
                        <input type="text" name="moq" x-model="sl.moq"
                               placeholder="e.g. 5 pcs"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier</label>
                        <input type="text" name="supplier" x-model="sl.supplier"
                               placeholder="e.g. Supplier A"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Purchase Date</label>
                        <input type="date" name="purchase_date" x-model="sl.purchaseDate"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>
                </div>

                <!-- Pricing & Markup Matrix -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cost Price (₹)</label>
                        <input type="number" step="0.01" name="cost_price" x-model="sl.costPrice"
                               placeholder="35.00"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono font-semibold text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Markup</label>
                        <input type="number" step="0.05" name="markup" x-model="sl.markup"
                               placeholder="1.4"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-800 mb-1">Wholesale Price (₹)</label>
                        <div class="px-3 py-2 bg-amber-100 border border-amber-300 rounded-lg text-xs font-mono font-bold text-amber-950">
                            ₹<span x-text="sl.wholesalePrice"></span>
                        </div>
                        <p class="text-[9px] text-amber-700 mt-0.5">Cost &times; Markup</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Retail Markup</label>
                        <input type="number" step="0.05" name="retail_markup" x-model="sl.retailMarkup"
                               placeholder="1.8"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-emerald-800 mb-1">Retail Price (₹)</label>
                        <div class="px-3 py-2 bg-emerald-100 border border-emerald-300 rounded-lg text-xs font-mono font-bold text-emerald-950">
                            ₹<span x-text="sl.retailPrice"></span>
                        </div>
                        <p class="text-[9px] text-emerald-700 mt-0.5">Cost &times; Retail Markup</p>
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
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Image URL (Direct / CDN)</label>
                        <input type="text" name="image_url" x-model="sl.imageUrl"
                               placeholder="https://... (or upload image below)"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="2" x-model="sl.description"
                              placeholder="Compact fast charging adapter, charge virtually any USB device..."
                              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900"></textarea>
                </div>
            </div>
        </fieldset>

        <!-- SECTION 3: Common Image Upload & Preview -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Product Image Upload
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-center">
                <div class="sm:col-span-2 space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Upload Image File (WebP, JPG, PNG)</label>
                    <input type="file" name="image" accept="image/*" @change="onFileSelected($event)"
                           class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100 border border-slate-300 rounded-lg cursor-pointer">
                    <p class="text-[11px] text-slate-400">
                        Image will be automatically converted to high-quality WebP, stored on the server, and given a public URL you can copy and use on any marketplace.
                    </p>
                </div>

                <!-- Small Thumbnail Preview -->
                <div class="text-center sm:text-left">
                    <span class="block text-[11px] font-semibold text-slate-500 mb-1.5">Thumbnail Reference Preview</span>
                    <div class="w-20 h-20 rounded-lg border border-slate-300 bg-slate-50 p-1 flex items-center justify-center overflow-hidden shadow-2xs">
                        <template x-if="imagePreview">
                            <img :src="imagePreview" class="w-full h-full object-contain rounded">
                        </template>
                        <template x-if="!imagePreview">
                            <span class="text-[10px] text-slate-400 text-center">No image selected</span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.wholesale.index') }}"
               class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-xs transition-all">
                Save Wholesale Product Entry
            </button>
        </div>
    </form>
</div>
@endsection
