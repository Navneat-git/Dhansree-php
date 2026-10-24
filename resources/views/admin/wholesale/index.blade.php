@extends('layouts.admin')

@section('title', 'Wholesale Purchases & Procurement')

@section('content')
<div class="max-w-[98rem] mx-auto space-y-6" x-data="{
    copied: false,
    copiedUrlId: null,
    showColorModal: false,
    newColorName: '',
    newColorCode: '',
    newHexCode: '#000000',
    colorSaving: false,
    async copyTable() {
        try {
            const url = '{{ route('admin.wholesale.copy') }}?tab={{ $tab }}&q={{ urlencode($filters['q'] ?? '') }}&status={{ urlencode($filters['status'] ?? '') }}';
            const res = await fetch(url);
            const text = await res.text();
            await navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 3000);
        } catch (e) {
            alert('Failed to copy to clipboard: ' + e.message);
        }
    },
    async copyImageUrl(url, id) {
        if (!url) {
            alert('No image URL available for this product.');
            return;
        }
        try {
            await navigator.clipboard.writeText(url);
            this.copiedUrlId = id;
            setTimeout(() => { this.copiedUrlId = null; }, 2500);
        } catch (e) {
            alert('Failed to copy image URL: ' + e.message);
        }
    },
    async submitNewColor() {
        if (!this.newColorName || !this.newColorCode) {
            alert('Please enter both color name and color code.');
            return;
        }
        this.colorSaving = true;
        try {
            const res = await fetch('{{ route('admin.wholesale.colors.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    color_name: this.newColorName,
                    color_code: this.newColorCode,
                    hex_code: this.newHexCode
                })
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Error saving color');
            }
        } catch (e) {
            alert('Error saving color: ' + e.message);
        } finally {
            this.colorSaving = false;
        }
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <span>Store</span>
                <span>&rsaquo;</span>
                <span class="text-slate-800 font-medium">Wholesale Purchases</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Wholesale Procurement & Purchases</h1>
            <p class="text-xs text-slate-500 mt-1">
                Manage all product purchases from wholesalers. Tabular spreadsheet format with automatic SKU, Pcode, and markup calculations.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <!-- Manage Color Codes Button -->
            <button type="button" @click="showColorModal = true"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-all">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4 4 4 0 014-4h4a4 4 0 014 4 4 4 0 01-4 4H7zm0 0v-4m0 0h4m-4 0a4 4 0 00-4-4 4 4 0 004-4h4a4 4 0 004 4 4 4 0 00-4 4H7z"/></svg>
                <span>Color Codes Master ({{ count($colorCodes) }})</span>
            </button>

            <!-- Copy TSV Button for Google Sheets / Excel -->
            <button type="button" @click="copyTable()"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-all">
                <template x-if="!copied">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        <span>Copy for Sheets / Excel</span>
                    </span>
                </template>
                <template x-if="copied">
                    <span class="inline-flex items-center gap-1.5 text-emerald-600 font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Copied to Clipboard!</span>
                    </span>
                </template>
            </button>

            <!-- Download CSV Export -->
            <a href="{{ route('admin.wholesale.export', ['tab' => $tab, 'q' => $filters['q'] ?? null, 'status' => $filters['status'] ?? null]) }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-all">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
            </a>

            <!-- Add Purchase Entry -->
            <a href="{{ route('admin.wholesale.create', ['type' => $tab === 'smart_living' ? 'smart_living' : 'women_fashion']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span>Add Purchase Entry</span>
            </a>
        </div>
    </div>

    <!-- Category Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto text-xs font-semibold">
        <a href="{{ route('admin.wholesale.index', ['tab' => 'all', 'q' => $filters['q'] ?? null]) }}"
           class="px-4 py-3 border-b-2 transition-all flex items-center gap-2 rounded-t-md whitespace-nowrap {{ $tab === 'all' ? 'border-amber-600 text-amber-800 bg-amber-50/60 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
            <span>All Purchases</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] {{ $tab === 'all' ? 'bg-amber-200 text-amber-900 font-bold' : 'bg-slate-100 text-slate-600' }}">{{ $totalCount }}</span>
        </a>

        <a href="{{ route('admin.wholesale.index', ['tab' => 'women_fashion', 'q' => $filters['q'] ?? null]) }}"
           class="px-4 py-3 border-b-2 transition-all flex items-center gap-2 rounded-t-md whitespace-nowrap {{ $tab === 'women_fashion' ? 'border-rose-600 text-rose-800 bg-rose-50/60 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            <span>Women Fashion</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] {{ $tab === 'women_fashion' ? 'bg-rose-200 text-rose-900 font-bold' : 'bg-slate-100 text-slate-600' }}">{{ $womenCount }}</span>
        </a>

        <a href="{{ route('admin.wholesale.index', ['tab' => 'smart_living', 'q' => $filters['q'] ?? null]) }}"
           class="px-4 py-3 border-b-2 transition-all flex items-center gap-2 rounded-t-md whitespace-nowrap {{ $tab === 'smart_living' ? 'border-blue-600 text-blue-800 bg-blue-50/60 font-bold' : 'border-transparent text-slate-600 hover:text-slate-900' }}">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span>Smart Living & Gadgets</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] {{ $tab === 'smart_living' ? 'bg-blue-200 text-blue-900 font-bold' : 'bg-slate-100 text-slate-600' }}">{{ $smartCount }}</span>
            @if(!empty($nextSmartLivingSerial))
                <span class="hidden sm:inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-100 text-blue-800 border border-blue-200" title="Next Available Serial">Next: {{ $nextSmartLivingSerial }}</span>
            @endif
        </a>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.wholesale.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="w-64 sm:w-80">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Search by SKU, Product Name, Design No, Pcode, Brand, Supplier..."
                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-1 focus:ring-amber-500">
            </div>

            <div class="w-40">
                <select name="status" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-1 focus:ring-amber-500">
                    <option value="">All Statuses</option>
                    <option value="Available" {{ ($filters['status'] ?? '') === 'Available' ? 'selected' : '' }}>Available / Active</option>
                    <option value="Out of Stock" {{ ($filters['status'] ?? '') === 'Out of Stock' ? 'selected' : '' }}>Out of Stock</option>
                    <option value="Not Available" {{ ($filters['status'] ?? '') === 'Not Available' ? 'selected' : '' }}>Not Available</option>
                    <option value="In Production" {{ ($filters['status'] ?? '') === 'In Production' ? 'selected' : '' }}>In Production</option>
                </select>
            </div>

            @if($tab === 'smart_living')
            <div class="w-48">
                <select name="category_id" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 focus:ring-1 focus:ring-amber-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string)($filters['category_id'] ?? '') === (string)$cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <button type="submit" class="px-4 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors">
                Filter
            </button>

            @if(!empty($filters['q']) || !empty($filters['status']) || !empty($filters['category_id']))
                <a href="{{ route('admin.wholesale.index', ['tab' => $tab]) }}" class="text-xs text-slate-500 hover:underline">
                    Reset
                </a>
            @endif

            <div class="ml-auto text-xs text-slate-500">
                Showing <strong>{{ $purchases->firstItem() ?? 0 }}-{{ $purchases->lastItem() ?? 0 }}</strong> of <strong>{{ $purchases->total() }}</strong> entries
            </div>
        </form>
    </div>

    <!-- Tabular Spreadsheet View (Google Sheet Style) -->
    <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50/70">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-sm bg-emerald-600 inline-block"></span>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">
                    @if($tab === 'women_fashion')
                        Women Fashion Purchase Catalog
                    @elseif($tab === 'smart_living')
                        Smart Living & Gadgets Purchase Catalog
                    @else
                        All Wholesale Product Purchases
                    @endif
                </h3>
            </div>
            <div class="text-[11px] text-slate-400">
                Scroll horizontally to view all columns &rarr;
            </div>
        </div>

        <div class="overflow-x-auto relative max-h-[700px]">
            <table class="w-full text-left border-collapse text-xs whitespace-nowrap">
                <thead class="bg-slate-100 text-slate-700 font-bold sticky top-0 z-20 border-b border-slate-200 shadow-2xs">
                    <tr>
                        <th class="p-2.5 border-r border-slate-200 sticky left-0 bg-slate-100 z-30 w-12 text-center">Id</th>
                        <th class="p-2.5 border-r border-slate-200 sticky left-12 bg-slate-100 z-30 w-16 text-center">Images</th>
                        <th class="p-2.5 border-r border-slate-200">SKU</th>
                        <th class="p-2.5 border-r border-slate-200 min-w-[200px]">Product Name / Title</th>

                        @if($tab === 'women_fashion')
                            <th class="p-2.5 border-r border-slate-200">Brand Code</th>
                            <th class="p-2.5 border-r border-slate-200">Purchase Date</th>
                            <th class="p-2.5 border-r border-slate-200">Design No</th>
                            <th class="p-2.5 border-r border-slate-200">Category Name</th>
                            <th class="p-2.5 border-r border-slate-200">vcode</th>
                            <th class="p-2.5 border-r border-slate-200">Category Code</th>
                            <th class="p-2.5 border-r border-slate-200 font-mono text-purple-700">pcode</th>
                            <th class="p-2.5 border-r border-slate-200">Color</th>
                            <th class="p-2.5 border-r border-slate-200">Color Code</th>
                            <th class="p-2.5 border-r border-slate-200">Size</th>
                            <th class="p-2.5 border-r border-slate-200">Thread Count</th>
                            <th class="p-2.5 border-r border-slate-200 text-right">Cost (₹)</th>
                            <th class="p-2.5 border-r border-slate-200 text-center">GST</th>
                            <th class="p-2.5 border-r border-slate-200 text-right">Total Cost (₹)</th>
                            <th class="p-2.5 border-r border-slate-200 text-center">Markup</th>
                            <th class="p-2.5 border-r border-slate-200 text-right text-emerald-800 font-bold bg-emerald-50/50">Selling Price (₹)</th>
                        @elseif($tab === 'smart_living')
                            <th class="p-2.5 border-r border-slate-200">Category</th>
                            <th class="p-2.5 border-r border-slate-200 text-right text-amber-800 font-bold bg-amber-50/50">Wholesale Price (₹)</th>
                            <th class="p-2.5 border-r border-slate-200 text-center">MOQ</th>
                            <th class="p-2.5 border-r border-slate-200 text-center">Stock Qty</th>
                            <th class="p-2.5 border-r border-slate-200">Brand</th>
                            <th class="p-2.5 border-r border-slate-200">Model</th>
                            <th class="p-2.5 border-r border-slate-200">Badge</th>
                            <th class="p-2.5 border-r border-slate-200">Supplier</th>
                            <th class="p-2.5 border-r border-slate-200 text-right">Cost Price (₹)</th>
                            <th class="p-2.5 border-r border-slate-200 text-center">Markup</th>
                            <th class="p-2.5 border-r border-slate-200 text-center">Retail Markup</th>
                            <th class="p-2.5 border-r border-slate-200 text-right text-emerald-800 font-bold bg-emerald-50/50">Retail Price (₹)</th>
                            <th class="p-2.5 border-r border-slate-200">Purchase Date</th>
                            <th class="p-2.5 border-r border-slate-200 min-w-[220px]">Description</th>
                        @else
                            <th class="p-2.5 border-r border-slate-200">Category</th>
                            <th class="p-2.5 border-r border-slate-200 text-right">Cost / Cost Price (₹)</th>
                            <th class="p-2.5 border-r border-slate-200 text-center">Markup</th>
                            <th class="p-2.5 border-r border-slate-200 text-right font-bold text-amber-800">Wholesale / Total Cost (₹)</th>
                            <th class="p-2.5 border-r border-slate-200 text-right font-bold text-emerald-800">Selling / Retail Price (₹)</th>
                            <th class="p-2.5 border-r border-slate-200 text-center">Stock / Size</th>
                            <th class="p-2.5 border-r border-slate-200">Brand / Supplier</th>
                            <th class="p-2.5 border-r border-slate-200">Purchase Date</th>
                        @endif

                        <th class="p-2.5 border-r border-slate-200 text-center">Status</th>
                        <th class="p-2.5 border-r border-slate-200 text-center">Image URL</th>
                        <th class="p-2.5 text-center sticky right-0 bg-slate-100 z-20 shadow-2xs">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchases as $p)
                        @php
                            $imgUrl = $p->effective_image_url;
                            $isWomen = $p->purchase_type === \App\Models\WholesalePurchase::TYPE_WOMEN_FASHION;
                        @endphp
                        <tr class="hover:bg-amber-50/20 transition-colors">
                            <!-- ID -->
                            <td class="p-2.5 border-r border-slate-100 text-slate-500 sticky left-0 bg-white z-10 text-center font-mono text-[11px]">
                                {{ $p->id }}
                            </td>

                            <!-- Small Crisp Thumbnail Image (Reference Image Column) -->
                            <td class="p-1.5 border-r border-slate-100 sticky left-12 bg-white z-10 text-center">
                                @if($imgUrl)
                                    <div class="relative group inline-block">
                                        <img src="{{ $imgUrl }}"
                                             alt="{{ $p->product_name }}"
                                             class="w-11 h-11 object-contain rounded border border-slate-200 bg-white p-0.5 shadow-2xs cursor-pointer">
                                        <!-- Hover Enlarged Preview -->
                                        <div class="hidden group-hover:block absolute left-14 top-0 z-50 p-1 bg-white border border-slate-300 rounded-lg shadow-xl w-36 h-36">
                                            <img src="{{ $imgUrl }}" class="w-full h-full object-contain rounded">
                                        </div>
                                    </div>
                                @else
                                    <div class="w-11 h-11 rounded border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-[10px] text-slate-400">
                                        No img
                                    </div>
                                @endif
                            </td>

                            <!-- SKU -->
                            <td class="p-2.5 border-r border-slate-100 font-mono text-slate-900 font-semibold select-all">
                                {{ $p->sku }}
                            </td>

                            <!-- Product Name / Title -->
                            <td class="p-2.5 border-r border-slate-100 text-slate-900 font-medium max-w-xs truncate" title="{{ $p->product_name }}">
                                {{ $p->product_name }}
                            </td>

                            @if($tab === 'women_fashion')
                                <td class="p-2.5 border-r border-slate-100 text-slate-600 font-mono">{{ $p->brand_code ?? 'DS' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-600">{{ $p->purchase_date?->format('d-m-Y') ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-700 font-mono">{{ $p->design_number ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-800">{{ $p->category_name ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 font-mono text-slate-600">{{ $p->vcode ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 font-mono font-semibold text-slate-700">{{ $p->category_code ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 font-mono font-bold text-purple-700 bg-purple-50/30">{{ $p->pcode ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-700">
                                    <div class="flex items-center gap-1.5">
                                        @php
                                            $matchedColor = $colorCodes->firstWhere('color_name', $p->color);
                                        @endphp
                                        @if($matchedColor && $matchedColor->hex_code)
                                            <span class="w-2.5 h-2.5 rounded-full border border-slate-300" style="background-color: {{ $matchedColor->hex_code }}"></span>
                                        @endif
                                        <span>{{ $p->color ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="p-2.5 border-r border-slate-100 font-mono text-slate-600">{{ $p->color_code ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 font-semibold text-slate-800 text-center">{{ $p->size ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-500">{{ $p->thread_count ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-right font-mono text-slate-700">₹{{ number_format((float)$p->cost, 2) }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-center font-mono text-slate-600">{{ $p->gst_percent }}%</td>
                                <td class="p-2.5 border-r border-slate-100 text-right font-mono font-semibold text-slate-900">₹{{ number_format((float)$p->total_cost, 2) }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-center font-mono text-slate-600">{{ $p->markup }}x</td>
                                <td class="p-2.5 border-r border-slate-100 text-right font-mono font-bold text-emerald-700 bg-emerald-50/40">
                                    ₹{{ number_format((float)$p->selling_price, 2) }}
                                </td>
                            @elseif($tab === 'smart_living')
                                <td class="p-2.5 border-r border-slate-100 text-slate-800">{{ $p->category?->name ?? $p->category_name ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-right font-mono font-bold text-amber-800 bg-amber-50/40">
                                    ₹{{ number_format((float)$p->wholesale_price, 2) }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-center font-semibold text-rose-600">{{ $p->moq ?: '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-center font-semibold text-slate-800">{{ $p->stock_qty }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-700">{{ $p->brand ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 font-mono text-slate-600">{{ $p->model ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-700">
                                    @if($p->badge)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900">{{ $p->badge }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-700">{{ $p->supplier ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-right font-mono text-slate-600">₹{{ number_format((float)$p->cost_price, 2) }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-center font-mono text-slate-600">{{ $p->markup }}x</td>
                                <td class="p-2.5 border-r border-slate-100 text-center font-mono text-slate-600">{{ $p->retail_markup }}x</td>
                                <td class="p-2.5 border-r border-slate-100 text-right font-mono font-bold text-emerald-700 bg-emerald-50/40">
                                    ₹{{ number_format((float)$p->retail_price, 2) }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-600">{{ $p->purchase_date?->format('d-m-Y') ?? '—' }}</td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-500 max-w-xs truncate" title="{{ $p->description }}">{{ $p->description ?: '—' }}</td>
                            @else
                                <!-- All View -->
                                <td class="p-2.5 border-r border-slate-100">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isWomen ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $isWomen ? 'Women Fashion' : 'Smart Living' }}
                                    </span>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $p->category?->name ?? $p->category_name }}</div>
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-right font-mono">
                                    ₹{{ number_format((float)($isWomen ? $p->cost : $p->cost_price), 2) }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-center font-mono text-slate-600">
                                    {{ $p->markup }}x
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-right font-mono font-bold text-amber-800">
                                    ₹{{ number_format((float)($isWomen ? $p->total_cost : $p->wholesale_price), 2) }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-right font-mono font-bold text-emerald-800">
                                    ₹{{ number_format((float)($isWomen ? $p->selling_price : $p->retail_price), 2) }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-center font-semibold">
                                    {{ $isWomen ? ($p->size ?: '—') : ($p->stock_qty . ' pcs') }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-600">
                                    {{ $isWomen ? ($p->brand_code ?? 'DS') : ($p->supplier ?? $p->brand ?? '—') }}
                                </td>
                                <td class="p-2.5 border-r border-slate-100 text-slate-600">
                                    {{ $p->purchase_date?->format('d-m-Y') ?? '—' }}
                                </td>
                            @endif

                            <!-- Status -->
                            <td class="p-2.5 border-r border-slate-100 text-center">
                                @if(in_array(strtolower($p->status), ['available', 'active']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $p->status }}
                                    </span>
                                @elseif(in_array(strtolower($p->status), ['out of stock', 'not available']))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        {{ $p->status }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ $p->status }}
                                    </span>
                                @endif
                            </td>

                            <!-- 1-Click Image URL Copy Button -->
                            <td class="p-2.5 border-r border-slate-100 text-center">
                                @if($imgUrl)
                                    <button type="button"
                                            @click="copyImageUrl('{{ $imgUrl }}', {{ $p->id }})"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-50 text-[11px] font-medium text-slate-700 shadow-2xs transition-all"
                                            title="Click to copy full public image URL">
                                        <template x-if="copiedUrlId === {{ $p->id }}">
                                            <span class="text-emerald-600 font-bold flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>Copied!</span>
                                            </span>
                                        </template>
                                        <template x-if="copiedUrlId !== {{ $p->id }}">
                                            <span class="flex items-center gap-1 text-slate-600 hover:text-slate-900">
                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                <span>Copy Link</span>
                                            </span>
                                        </template>
                                    </button>
                                @else
                                    <span class="text-slate-400 text-[11px]">—</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="p-2.5 text-center sticky right-0 bg-white z-10 shadow-2xs">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('admin.wholesale.edit', $p) }}"
                                       class="p-1 rounded text-slate-500 hover:text-amber-700 hover:bg-amber-50 transition-colors"
                                       title="Edit Entry">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    <form method="POST" action="{{ route('admin.wholesale.destroy', $p) }}"
                                          onsubmit="return confirm('Are you sure you want to delete purchase entry \'{{ addslashes($p->product_name) }}\'?');"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1 rounded text-slate-400 hover:text-rose-700 hover:bg-rose-50 transition-colors"
                                                title="Delete Entry">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tab === 'women_fashion' ? 24 : ($tab === 'smart_living' ? 22 : 16) }}"
                                class="p-12 text-center text-slate-400">
                                <div class="max-w-sm mx-auto text-center space-y-3">
                                    <svg class="w-12 h-12 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                    <p class="text-sm font-medium text-slate-600">No wholesale purchase entries found.</p>
                                    <a href="{{ route('admin.wholesale.create', ['type' => $tab === 'smart_living' ? 'smart_living' : 'women_fashion']) }}"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 shadow-xs">
                                        <span>Add First Purchase Entry</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>

    <!-- Manage Color Codes Modal -->
    <div x-show="showColorModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showColorModal = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
                 @click.stop>
                <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Color & Color Code Master</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Manage color names and their respective abbreviation codes used for SKU generation.</p>
                    </div>
                    <button type="button" @click="showColorModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-5 space-y-5">
                    <!-- Add New Color Row -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Add New Color to Master</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Color Name</label>
                                <input type="text" x-model="newColorName" placeholder="e.g. Royal Blue"
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Color Code (Abbreviation)</label>
                                <input type="text" x-model="newColorCode" placeholder="e.g. RBLU"
                                       class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-mono uppercase text-slate-900">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Color Hex</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="newHexCode" class="h-8 w-12 rounded border border-slate-300 cursor-pointer">
                                    <button type="button" @click="submitNewColor()" :disabled="colorSaving"
                                            class="flex-1 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold transition-all">
                                        <span x-text="colorSaving ? 'Saving...' : 'Add Color'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Colors Table -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden max-h-72 overflow-y-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-slate-700 font-bold sticky top-0 border-b border-slate-200">
                                <tr>
                                    <th class="p-2.5">Preview</th>
                                    <th class="p-2.5">Color Name</th>
                                    <th class="p-2.5 font-mono">Code</th>
                                    <th class="p-2.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($colorCodes as $col)
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-2.5">
                                            <span class="w-5 h-5 rounded-full inline-block border border-slate-300 shadow-2xs"
                                                  style="background-color: {{ $col->hex_code ?? '#cccccc' }}"></span>
                                        </td>
                                        <td class="p-2.5 font-medium text-slate-900">{{ $col->color_name }}</td>
                                        <td class="p-2.5 font-mono font-bold text-purple-700">{{ $col->color_code }}</td>
                                        <td class="p-2.5 text-right">
                                            <form method="POST" action="{{ route('admin.wholesale.colors.destroy', $col) }}"
                                                  onsubmit="return confirm('Remove color {{ $col->color_name }} from master?');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-800 text-[11px] font-medium">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-200 bg-slate-50 flex justify-end">
                    <button type="button" @click="showColorModal = false"
                            class="px-4 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
