@extends('layouts.admin')

@section('title', 'Product Feeds — Google Merchant Center & Meta Business Catalog')

@section('content')
<div class="space-y-6">

    <!-- Page Header & Global Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-900">Product Feeds</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                    Google &amp; Meta Live
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Generate and serve automated CSV feeds for Meta Business (Facebook/Instagram Shops) and Google Merchant Center (Google Shopping).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('admin.feeds.generate') }}" class="inline">
                @csrf
                <input type="hidden" name="type" value="all">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Generate All Feeds Now</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span class="font-medium">Active Store Catalog</span>
                <span class="size-2 rounded-full bg-emerald-500"></span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-slate-900">{{ $activeVariantsCount }}</span>
                <span class="text-xs text-slate-500">SKUs across {{ $activeProductsCount }} Products</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Ready to be indexed on Meta &amp; Google</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span class="font-medium flex items-center gap-1.5">
                    <span class="text-blue-600">●</span> Meta Catalog Feed
                </span>
                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">{{ $meta['size_formatted'] }}</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-slate-900">{{ $meta['rows_count'] }}</span>
                <span class="text-xs text-slate-500">Items Indexed</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Updated {{ $meta['last_modified_human'] }}</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500">
                <span class="font-medium flex items-center gap-1.5">
                    <span class="text-amber-600">●</span> Google Merchant Feed
                </span>
                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">{{ $google['size_formatted'] }}</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold text-slate-900">{{ $google['rows_count'] }}</span>
                <span class="text-xs text-slate-500">Items Indexed</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-1">Updated {{ $google['last_modified_human'] }}</p>
        </div>
    </div>

    <!-- Feed Cards -->
    <div class="space-y-6">

        <!-- 1. Meta Business Catalog Feed -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden" x-data="{ copied: false, previewOpen: false }">
            <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-blue-50/40 via-white to-transparent">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="size-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-lg">
                            f
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-900">Meta Business Catalog Feed</h2>
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-blue-100 text-blue-800">Facebook &amp; Instagram Shop</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Official Meta CSV format (30 columns matching Facebook Business catalog specification). Supports color, size, multi-item variant grouping, and sale prices.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.feeds.download', 'meta') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Download CSV</span>
                        </a>

                        <form method="POST" action="{{ route('admin.feeds.generate') }}">
                            @csrf
                            <input type="hidden" name="type" value="meta">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Regenerate</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Feed URL Input with Copy Button -->
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Meta Scheduled Fetch URL (Paste into Meta Commerce Manager)
                    </label>
                    <div class="flex items-center gap-2 max-w-3xl">
                        <input type="text"
                               readonly
                               value="{{ $meta['url'] }}"
                               class="flex-1 px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono text-slate-900 select-all focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ $meta['url'] }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors shrink-0 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span x-text="copied ? 'Copied URL!' : 'Copy Link'">Copy Link</span>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5">
                        File location: <code class="font-mono text-slate-600">public/feeds/meta_catalog.csv</code> &bull; Publicly accessible for Meta crawlers 24/7.
                    </p>
                </div>
            </div>

            <!-- Meta Feed Details & Setup Steps -->
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Feed Format</span>
                        <p class="font-semibold text-slate-800">CSV &bull; UTF-8</p>
                        <p class="text-[11px] text-slate-500">30 Standard Meta Catalog Columns</p>
                    </div>
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Currency &amp; Country</span>
                        <p class="font-semibold text-slate-800">INR (Indian Rupee) &bull; IN</p>
                        <p class="text-[11px] text-slate-500">Automatic standard shipping format</p>
                    </div>
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Variant Grouping</span>
                        <p class="font-semibold text-slate-800">Enabled (item_group_id)</p>
                        <p class="text-[11px] text-slate-500">Variants display together as sizes/colors</p>
                    </div>
                </div>

                <!-- Meta Setup Accordion / Guide -->
                <div class="rounded-lg bg-blue-50/50 border border-blue-200/70 p-4 text-xs space-y-2">
                    <div class="flex items-center gap-2 text-blue-900 font-bold">
                        <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <span>How to connect this feed with Meta Commerce Manager:</span>
                    </div>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 pl-1 leading-relaxed">
                        <li>Log in to <a href="https://business.facebook.com/commerce" target="_blank" class="font-semibold text-blue-700 underline">Meta Commerce Manager</a> and open your Catalog.</li>
                        <li>Go to <strong>Data Sources</strong> &rsaquo; <strong>Add Products</strong> &rsaquo; Select <strong>Data Feed</strong>.</li>
                        <li>Select <strong>Scheduled Feed</strong> and set the schedule to <strong>Daily</strong>.</li>
                        <li>Paste your Meta Feed URL above into the <strong>Enter URL</strong> box (no username/password needed).</li>
                        <li>Click <strong>Start Upload</strong>. Meta will automatically fetch, validate, and publish all your active products!</li>
                    </ol>
                </div>

                <!-- Preview Sample Rows Toggle -->
                @if(!empty($meta['preview_rows']))
                    <div class="pt-2">
                        <button type="button"
                                @click="previewOpen = !previewOpen"
                                class="text-xs font-semibold text-blue-700 hover:text-blue-800 flex items-center gap-1">
                            <span x-text="previewOpen ? 'Hide Sample Preview' : 'Show Sample Rows (First 5 Items)'"></span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="previewOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="previewOpen" x-cloak class="mt-3 overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                <thead class="bg-slate-50 font-semibold text-slate-700">
                                    <tr>
                                        <th class="px-3 py-2">ID (SKU)</th>
                                        <th class="px-3 py-2">Title</th>
                                        <th class="px-3 py-2">Availability</th>
                                        <th class="px-3 py-2">Price</th>
                                        <th class="px-3 py-2">Sale Price</th>
                                        <th class="px-3 py-2">Color</th>
                                        <th class="px-3 py-2">Size</th>
                                        <th class="px-3 py-2">Group ID</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white font-mono text-[11px] text-slate-800">
                                    @foreach($meta['preview_rows'] as $row)
                                        <tr>
                                            <td class="px-3 py-2 font-bold">{{ $row['id'] }}</td>
                                            <td class="px-3 py-2 font-sans max-w-xs truncate">{{ $row['title'] }}</td>
                                            <td class="px-3 py-2">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] {{ $row['availability'] === 'in stock' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                    {{ $row['availability'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2">{{ $row['price'] }}</td>
                                            <td class="px-3 py-2 text-emerald-600 font-semibold">{{ $row['sale_price'] ?: '-' }}</td>
                                            <td class="px-3 py-2">{{ $row['color'] ?: '-' }}</td>
                                            <td class="px-3 py-2">{{ $row['size'] ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-400">{{ $row['item_group_id'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. Google Merchant Center Feed -->
        <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs overflow-hidden" x-data="{ copied: false, previewOpen: false }">
            <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-amber-50/40 via-white to-transparent">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="size-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs font-bold text-lg">
                            G
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-900">Google Merchant Center Feed</h2>
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-100 text-amber-800">Google Shopping &amp; Free Listings</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Official Google Shopping CSV specification with additional images, Google Product Taxonomy, GTIN/MPN fallback, condition, and free delivery shipping configuration.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.feeds.download', 'google') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Download CSV</span>
                        </a>

                        <form method="POST" action="{{ route('admin.feeds.generate') }}">
                            @csrf
                            <input type="hidden" name="type" value="google">
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Regenerate</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Feed URL Input with Copy Button -->
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Google Merchant Scheduled Fetch URL (Paste into Google Merchant Center)
                    </label>
                    <div class="flex items-center gap-2 max-w-3xl">
                        <input type="text"
                               readonly
                               value="{{ $google['url'] }}"
                               class="flex-1 px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-xs font-mono text-slate-900 select-all focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ $google['url'] }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors shrink-0 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span x-text="copied ? 'Copied URL!' : 'Copy Link'">Copy Link</span>
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1.5">
                        File location: <code class="font-mono text-slate-600">public/feeds/google_merchant.csv</code> &bull; Publicly accessible for Google Shopping crawler.
                    </p>
                </div>
            </div>

            <!-- Google Feed Details & Setup Steps -->
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Google Product Taxonomy</span>
                        <p class="font-semibold text-slate-800">Automatic Categorization</p>
                        <p class="text-[11px] text-slate-500">Traditional Clothing &amp; Gadgets</p>
                    </div>
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Barcode Identifier Fallback</span>
                        <p class="font-semibold text-slate-800">identifier_exists = no</p>
                        <p class="text-[11px] text-slate-500">Prevents disapproval for custom products</p>
                    </div>
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1">
                        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Multi-Angle Image Links</span>
                        <p class="font-semibold text-slate-800">additional_image_link Included</p>
                        <p class="text-[11px] text-slate-500">Displays all product photo angles</p>
                    </div>
                </div>

                <!-- Google Setup Accordion / Guide -->
                <div class="rounded-lg bg-amber-50/50 border border-amber-200/70 p-4 text-xs space-y-2">
                    <div class="flex items-center gap-2 text-amber-900 font-bold">
                        <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <span>How to connect this feed with Google Merchant Center:</span>
                    </div>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 pl-1 leading-relaxed">
                        <li>Log in to <a href="https://merchants.google.com" target="_blank" class="font-semibold text-amber-700 underline">Google Merchant Center</a>.</li>
                        <li>Click <strong>Products</strong> on the left sidebar &rsaquo; select <strong>Feeds</strong>.</li>
                        <li>Click the <strong>+ Primary Feed</strong> button &rsaquo; Choose Target Country: <strong>India</strong>, Language: <strong>English</strong>.</li>
                        <li>Give your feed a name (e.g. <em>Dhansree Store Catalog</em>) &rsaquo; Select <strong>Scheduled fetch</strong>.</li>
                        <li>Enter file name: <code class="font-mono bg-white px-1 py-0.5 rounded border border-amber-200">google_merchant.csv</code>.</li>
                        <li>Paste your Google Feed URL above into <strong>File URL</strong> and set fetch frequency (Daily recommended).</li>
                        <li>Click <strong>Create Feed</strong>. Google will automatically crawl and verify your inventory.</li>
                    </ol>
                </div>

                <!-- Preview Sample Rows Toggle -->
                @if(!empty($google['preview_rows']))
                    <div class="pt-2">
                        <button type="button"
                                @click="previewOpen = !previewOpen"
                                class="text-xs font-semibold text-amber-700 hover:text-amber-800 flex items-center gap-1">
                            <span x-text="previewOpen ? 'Hide Sample Preview' : 'Show Sample Rows (First 5 Items)'"></span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="previewOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="previewOpen" x-cloak class="mt-3 overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                <thead class="bg-slate-50 font-semibold text-slate-700">
                                    <tr>
                                        <th class="px-3 py-2">ID (SKU)</th>
                                        <th class="px-3 py-2">Title</th>
                                        <th class="px-3 py-2">Availability</th>
                                        <th class="px-3 py-2">Price</th>
                                        <th class="px-3 py-2">Sale Price</th>
                                        <th class="px-3 py-2">Category</th>
                                        <th class="px-3 py-2">Color</th>
                                        <th class="px-3 py-2">Size</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white font-mono text-[11px] text-slate-800">
                                    @foreach($google['preview_rows'] as $row)
                                        <tr>
                                            <td class="px-3 py-2 font-bold">{{ $row['id'] }}</td>
                                            <td class="px-3 py-2 font-sans max-w-xs truncate">{{ $row['title'] }}</td>
                                            <td class="px-3 py-2">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] {{ $row['availability'] === 'in_stock' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                    {{ $row['availability'] }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2">{{ $row['price'] }}</td>
                                            <td class="px-3 py-2 text-emerald-600 font-semibold">{{ $row['sale_price'] ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600 font-sans max-w-xs truncate">{{ $row['google_product_category'] }}</td>
                                            <td class="px-3 py-2">{{ $row['color'] ?: '-' }}</td>
                                            <td class="px-3 py-2">{{ $row['size'] ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
