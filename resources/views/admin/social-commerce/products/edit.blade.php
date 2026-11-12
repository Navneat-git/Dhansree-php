@extends('layouts.admin')

@section('title', 'Edit ' . $product->product_code)
@section('page_title', 'Edit Supplier Product: ' . $product->product_code)

@section('header_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('admin.social-commerce.products.show', $product) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs transition-colors">
        View Product
    </a>
    <a href="{{ route('admin.social-commerce.products.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
        &larr; Products List
    </a>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.social-commerce.products.update', $product) }}"
      x-data="{
          primaryMediaId: {{ $product->primary_media_asset_id ?? 'null' }},
          selectedMediaIds: [{{ $product->mediaAssets->pluck('id')->implode(',') }}],
          toggleMedia(id) {
              const idx = this.selectedMediaIds.indexOf(id);
              if (idx > -1) {
                  this.selectedMediaIds.splice(idx, 1);
                  if (this.primaryMediaId === id) {
                      this.primaryMediaId = this.selectedMediaIds[0] || null;
                  }
              } else {
                  this.selectedMediaIds.push(id);
                  if (!this.primaryMediaId) {
                      this.primaryMediaId = id;
                  }
              }
          }
      }" class="space-y-6 max-w-5xl mx-auto">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Main Details -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Basic Info -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    Product Identification &amp; Category
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Code</label>
                        <input type="text" value="{{ $product->product_code }}" readonly disabled
                               class="w-full text-xs font-mono font-bold bg-slate-100 rounded-lg border-slate-200 text-slate-600 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name *</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
                        <select name="category_name" required class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category_name', $product->category_name) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Fabric Details</label>
                        <input type="text" name="fabric" value="{{ old('fabric', $product->fabric) }}"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Available Colours</label>
                        <input type="text" name="available_colors"
                               value="{{ old('available_colors', is_array($product->available_colors) ? implode(', ', $product->available_colors) : '') }}"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Available Sizes</label>
                        <input type="text" name="available_sizes"
                               value="{{ old('available_sizes', is_array($product->available_sizes) ? implode(', ', $product->available_sizes) : '') }}"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>

            <!-- Pricing & Cost -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    Pricing &amp; Supplier Costing (₹)
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Cost (₹)</label>
                        <input type="number" step="0.01" name="purchase_price"
                               value="{{ old('purchase_price', $product->purchase_price_rupees) }}"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Suggested MRP (₹)</label>
                        <input type="number" step="0.01" name="suggested_selling_price"
                               value="{{ old('suggested_selling_price', $product->suggested_selling_price_rupees) }}"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Special Offer Price (₹)</label>
                        <input type="number" step="0.01" name="special_offer_price"
                               value="{{ old('special_offer_price', $product->special_offer_price_rupees) }}"
                               class="w-full text-xs font-bold text-emerald-700 rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 py-2">
                    </div>
                </div>
            </div>

            <!-- Media Assets -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Manage Attached Media</h3>
                        <p class="text-xs text-slate-500">Click to select/deselect. First is Primary Thumbnail.</p>
                    </div>
                    <span class="text-xs font-bold text-amber-600">
                        <span x-text="selectedMediaIds.length"></span> Selected
                    </span>
                </div>

                <input type="hidden" name="primary_media_asset_id" :value="primaryMediaId">
                <template x-for="id in selectedMediaIds" :key="id">
                    <input type="hidden" name="media_asset_ids[]" :value="id">
                </template>

                <div class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-6 gap-2.5 max-h-80 overflow-y-auto p-1">
                    @foreach($allAssets as $asset)
                    <div @click="toggleMedia({{ $asset->id }})"
                         class="relative aspect-square rounded-xl overflow-hidden cursor-pointer border-2 transition-all group"
                         :class="selectedMediaIds.includes({{ $asset->id }}) ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-slate-200 hover:border-slate-300'">
                        <img src="{{ $asset->url }}" alt="{{ $asset->file_name }}" class="w-full h-full object-cover">

                        <template x-if="primaryMediaId === {{ $asset->id }}">
                            <span class="absolute top-1 left-1 px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-500 text-slate-950 shadow-xs">
                                PRIMARY
                            </span>
                        </template>

                        <template x-if="selectedMediaIds.includes({{ $asset->id }})">
                            <span class="absolute bottom-1 right-1 size-5 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-xs shadow-xs">
                                ✓
                            </span>
                        </template>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Right 1 Col: Status & Actions -->
        <div class="space-y-6">

            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    Availability &amp; Supplier
                </h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Availability Status *</label>
                    <select name="availability_status" required
                            class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <option value="NEW" {{ $product->availability_status === 'NEW' ? 'selected' : '' }}>NEW (Just Arrived)</option>
                        <option value="AVAILABLE" {{ $product->availability_status === 'AVAILABLE' ? 'selected' : '' }}>AVAILABLE (Stock Confirmed)</option>
                        <option value="TEMPORARILY_UNAVAILABLE" {{ $product->availability_status === 'TEMPORARILY_UNAVAILABLE' ? 'selected' : '' }}>TEMPORARILY UNAVAILABLE</option>
                        <option value="OUT_OF_STOCK" {{ $product->availability_status === 'OUT_OF_STOCK' ? 'selected' : '' }}>OUT OF STOCK</option>
                        <option value="ARCHIVED" {{ $product->availability_status === 'ARCHIVED' ? 'selected' : '' }}>ARCHIVED</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier</label>
                    <select name="supplier_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <option value="">-- Unassigned Supplier --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Product Code</label>
                    <input type="text" name="supplier_product_code" value="{{ old('supplier_product_code', $product->supplier_product_code) }}"
                           class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Internal Notes</label>
                    <textarea name="notes" rows="4"
                              class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5">{{ old('notes', $product->notes) }}</textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-xs transition-colors">
                    Update Product
                </button>
                <a href="{{ route('admin.social-commerce.products.show', $product) }}" class="w-full py-2 px-4 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-xs text-center block">
                    Cancel
                </a>
            </div>

        </div>

    </div>
</form>
@endsection
