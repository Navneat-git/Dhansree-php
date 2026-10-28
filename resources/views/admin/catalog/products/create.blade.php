@extends('layouts.admin')

@section('title', 'Add Product')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('admin.products.index') }}" class="hover:text-amber-600 transition-colors">Products</a>
        <span>&rsaquo;</span>
        <span class="text-slate-800 font-medium">Add Product</span>
    </div>

    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Add Product</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Quickly create a product with its default variant and inventory. You can add multi-variants and images on the next screen.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-6">
        @csrf

        <!-- Product Details Card -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Basic Information</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Product Title <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="product-name" value="{{ old('name') }}" required maxlength="150"
                           placeholder="e.g. Women Hand Embroidered Cotton Kurti"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Category <span class="text-rose-500">*</span></label>
                    <select name="category_id" required class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                        <option value="">Select Category...</option>
                        @foreach($categoryOptions as $opt)
                            <option value="{{ $opt['id'] }}" {{ old('category_id') == $opt['id'] ? 'selected' : '' }}>
                                {{ str_repeat('— ', $opt['depth'] - 1) }}{{ $opt['name'] }} ({{ $opt['label'] }})
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Brand (Optional)</label>
                    <input type="text" name="brand" value="{{ old('brand', 'Dhansree') }}" maxlength="80"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Display Ranking / Priority</label>
                    <input type="number" name="ranking" value="{{ old('ranking') }}" min="1" max="999999" placeholder="e.g. 1 (highest), 2, 3..."
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    <p class="text-[11px] text-slate-500 mt-1">Products with ranking 1, 2, 3... appear first on the New Arrivals section and listing pages.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Short Summary / Highlights</label>
                    <input type="text" name="short_description" value="{{ old('short_description') }}" maxlength="300"
                           placeholder="Key selling points, fabric, fit..."
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Description</label>
                    <textarea name="description" rows="4" placeholder="Detailed product description, fabric care instructions, sizing details..."
                              class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Pricing & Inventory Card -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">Pricing & Inventory</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Selling Price (₹) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">₹</span>
                        <input type="number" step="0.01" min="0" name="price_rupees" value="{{ old('price_rupees') }}" required placeholder="699.00"
                               class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    </div>
                    @error('price_rupees')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">MRP / Strikethrough (₹)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">₹</span>
                        <input type="number" step="0.01" min="0" name="mrp_rupees" value="{{ old('mrp_rupees') }}" placeholder="1299.00"
                               class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Must be &ge; Selling Price for discount display.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Cost Price (₹)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">₹</span>
                        <input type="number" step="0.01" min="0" name="cost_rupees" value="{{ old('cost_rupees') }}" placeholder="350.00"
                               class="w-full pl-8 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Internal cost for margin tracking.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">SKU (Stock Keeping Unit)</label>
                    <input type="text" name="sku" id="product-sku" value="{{ old('sku') }}" maxlength="64" placeholder="Auto-generated if blank"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm font-mono text-slate-900 placeholder-slate-400 uppercase focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    @error('sku')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Initial Stock Quantity</label>
                    <input type="number" min="0" name="initial_stock" value="{{ old('initial_stock', '10') }}"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    <p class="text-[11px] text-slate-500 mt-1">Allocated to default location (Main).</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Weight (Grams)</label>
                    <input type="number" min="0" name="weight_grams" value="{{ old('weight_grams', '250') }}"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    <p class="text-[11px] text-slate-500 mt-1">Required for Shiprocket courier rates.</p>
                </div>
            </div>
        </div>

        <!-- Tax & Logistics Card -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-200/80 pb-3">GST Tax & Invoicing</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">HSN Code <span class="text-amber-700 font-normal">(Required on GST invoices)</span></label>
                    <input type="text" name="hsn_code" value="{{ old('hsn_code') }}" maxlength="8" placeholder="e.g. 6204, 8517"
                           class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm font-mono text-slate-900 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                    <p class="text-[11px] text-slate-500 mt-1">4, 6, or 8 digits. Apparel: 6204/6206, Electronics: 8517.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">GST Rate</label>
                    <select name="gst_rate_bps" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                        <option value="">Inherit Store Default ({{ ($defaultGstRateBps ?? 1800) / 100 }}%)</option>
                        <option value="500" {{ old('gst_rate_bps') == 500 ? 'selected' : '' }}>5% (500 bps - Apparel &le; ₹1000)</option>
                        <option value="1200" {{ old('gst_rate_bps') == 1200 ? 'selected' : '' }}>12% (1200 bps - Apparel &gt; ₹1000)</option>
                        <option value="1800" {{ old('gst_rate_bps') == 1800 ? 'selected' : '' }}>18% (1800 bps - Standard / Gadgets)</option>
                        <option value="2800" {{ old('gst_rate_bps') == 2800 ? 'selected' : '' }}>28% (2800 bps - Luxury items)</option>
                        <option value="0" {{ old('gst_rate_bps') === '0' ? 'selected' : '' }}>0% (Exempt)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Submit Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 hover:text-slate-900 text-sm font-semibold transition-colors shadow-2xs">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm shadow-xs transition-all">
                Create Product & Continue &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
