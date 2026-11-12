@extends('layouts.admin')

@section('title', 'New Supplier Product')
@section('page_title', 'Create Supplier Product')

@section('header_actions')
<a href="{{ route('admin.social-commerce.products.index') }}"
   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs transition-colors">
    &larr; Back to Products
</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.social-commerce.products.store') }}"
      x-data="{
          primaryMediaId: null,
          selectedMediaIds: [],
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Main Product Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Basic Information Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    Product Identification &amp; Category
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Code *</label>
                        <input type="text" name="product_code" value="{{ old('product_code', $nextProductCode) }}" required
                               class="w-full text-xs font-mono font-bold rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Pure Georgette Chikankari Anarkali Suit"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
                        <select name="category_name" required class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category_name') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Fabric Details</label>
                        <input type="text" name="fabric" value="{{ old('fabric') }}" placeholder="e.g. Pure Cotton, Chanderi, Modal Silk"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Available Colours</label>
                        <input type="text" name="available_colors" value="{{ old('available_colors') }}" placeholder="Maroon, Mustard, Pink, Navy"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <p class="text-[10px] text-slate-400 mt-1">Comma separated</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Available Sizes</label>
                        <input type="text" name="available_sizes" value="{{ old('available_sizes', 'M, L, XL, XXL') }}" placeholder="M, L, XL, XXL or Unstitched"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <p class="text-[10px] text-slate-400 mt-1">Comma separated</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                    <textarea name="description" rows="3" placeholder="Key design features, embroidery highlights, dupatta length, styling advice..."
                              class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5">{{ old('description') }}</textarea>
                </div>
            </div>

            <!-- Pricing & Cost Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    Pricing &amp; Supplier Costing (₹)
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Cost (₹)</label>
                        <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price') }}" placeholder="e.g. 550"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <p class="text-[10px] text-slate-400 mt-0.5">Wholesale purchase rate</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Suggested MRP (₹)</label>
                        <input type="number" step="0.01" name="suggested_selling_price" value="{{ old('suggested_selling_price') }}" placeholder="e.g. 1299"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <p class="text-[10px] text-slate-400 mt-0.5">Crossed-out reference price</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Special Offer Price (₹)</label>
                        <input type="number" step="0.01" name="special_offer_price" value="{{ old('special_offer_price') }}" placeholder="e.g. 899"
                               class="w-full text-xs font-bold text-emerald-700 rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 py-2">
                        <p class="text-[10px] text-slate-400 mt-0.5">Final selling / status promo price</p>
                    </div>
                </div>
            </div>

            <!-- Media Selection from Media Inbox Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Attach Media from Inbox</h3>
                        <p class="text-xs text-slate-500">Click images to select. First clicked becomes the Primary Thumbnail.</p>
                    </div>
                    <span class="text-xs font-bold text-amber-600">
                        <span x-text="selectedMediaIds.length"></span> Selected
                    </span>
                </div>

                <!-- Hidden inputs for selected media and primary -->
                <input type="hidden" name="primary_media_asset_id" :value="primaryMediaId">
                <template x-for="id in selectedMediaIds" :key="id">
                    <input type="hidden" name="media_asset_ids[]" :value="id">
                </template>

                @if($unassignedAssets->isEmpty())
                <div class="p-6 text-center text-slate-400 bg-slate-50 rounded-xl">
                    <p class="text-xs font-medium">No unprocessed media in inbox.</p>
                    <a href="{{ route('admin.social-commerce.inbox.index') }}" class="text-xs text-emerald-600 font-semibold underline mt-1 inline-block">
                        Upload to Media Inbox First &rarr;
                    </a>
                </div>
                @else
                <div class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-6 gap-2.5 max-h-80 overflow-y-auto p-1">
                    @foreach($unassignedAssets as $asset)
                    <div @click="toggleMedia({{ $asset->id }})"
                         class="relative aspect-square rounded-xl overflow-hidden cursor-pointer border-2 transition-all group"
                         :class="selectedMediaIds.includes({{ $asset->id }}) ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-slate-200 hover:border-slate-300'">
                        <img src="{{ $asset->url }}" alt="{{ $asset->file_name }}" class="w-full h-full object-cover">

                        <!-- Primary badge -->
                        <template x-if="primaryMediaId === {{ $asset->id }}">
                            <span class="absolute top-1 left-1 px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-500 text-slate-950 shadow-xs">
                                PRIMARY
                            </span>
                        </template>

                        <!-- Selected Checkmark -->
                        <template x-if="selectedMediaIds.includes({{ $asset->id }})">
                            <span class="absolute bottom-1 right-1 size-5 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-xs shadow-xs">
                                ✓
                            </span>
                        </template>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>

        <!-- Right 1 Col: Supplier & Availability Status -->
        <div class="space-y-6">

            <!-- Availability & Status Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    Supplier &amp; Availability
                </h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Availability Status *</label>
                    <select name="availability_status" required
                            class="w-full text-xs font-semibold rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <option value="NEW" selected>NEW (Just Arrived from Supplier)</option>
                        <option value="AVAILABLE">AVAILABLE (Stock Confirmed)</option>
                        <option value="TEMPORARILY_UNAVAILABLE">TEMPORARILY UNAVAILABLE</option>
                        <option value="OUT_OF_STOCK">OUT OF STOCK</option>
                        <option value="ARCHIVED">ARCHIVED</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1 leading-normal">
                        Supplier stock fluctuates daily. Never assume availability without checking WhatsApp group.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier</label>
                    <select name="supplier_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        <option value="">-- Unassigned Supplier --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Product Code</label>
                    <input type="text" name="supplier_product_code" value="{{ old('supplier_product_code') }}" placeholder="e.g. S-4022"
                           class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Internal Notes</label>
                    <textarea name="notes" rows="3" placeholder="Supplier contact, MOQ, dispatch timeline, repeat order notes..."
                              class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-2">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Save Supplier Product
                </button>
                <a href="{{ route('admin.social-commerce.products.index') }}" class="w-full py-2 px-4 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-xs text-center block">
                    Cancel
                </a>
            </div>

        </div>

    </div>
</form>
@endsection
