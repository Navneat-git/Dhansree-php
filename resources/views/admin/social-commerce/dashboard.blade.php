@extends('layouts.admin')

@section('title', 'Social Commerce Studio')
@section('page_title', 'Social Commerce Studio')

@section('header_actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.social-commerce.inbox.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 shadow-xs transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
        </svg>
        Upload WhatsApp Media
    </a>
    <a href="{{ route('admin.social-commerce.products.create') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500 text-slate-950 hover:bg-amber-400 shadow-xs transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        New Supplier Product
    </a>
    <a href="{{ route('admin.social-commerce.whatsapp-status.index') }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-900 text-white hover:bg-slate-800 border border-slate-700 shadow-xs transition-colors">
        <svg class="size-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
        </svg>
        WhatsApp Status (9:16)
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Information Scope Banner -->
    <div class="rounded-2xl border border-amber-500/20 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-4 sm:p-5">
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-xl bg-amber-500/20 text-amber-500 shrink-0">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            </div>
            <div class="flex-1 text-sm text-slate-700">
                <p class="font-semibold text-slate-900">Dhansree Social Commerce &amp; Supplier Management</p>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Designed for women's fashion order-based selling model. Daily WhatsApp supplier designs are managed here independently without cluttering the public store catalog. Products are published to the main website store only upon explicit approval.
                </p>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-white text-slate-700 border border-slate-200 shadow-2xs">
                    <span class="size-2 rounded-full bg-emerald-500"></span>
                    Safe Inventory Isolation
                </span>
            </div>
        </div>
    </div>

    <!-- 4 Key Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
        <!-- Media Inbox Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-emerald-300 transition-colors">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Supplier Media</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="flex items-baseline justify-between">
                    <h3 class="text-2xl font-bold text-slate-900">{{ number_format($metrics['total_media']) }}</h3>
                    <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                        {{ $metrics['unprocessed_media'] }} unprocessed
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">Storage used: {{ $metrics['storage_used_formatted'] }}</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <a href="{{ route('admin.social-commerce.inbox.index') }}" class="font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
                    Open Media Inbox &rarr;
                </a>
            </div>
        </div>

        <!-- Supplier Products Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-amber-300 transition-colors">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Product Library</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="flex items-baseline justify-between">
                    <h3 class="text-2xl font-bold text-slate-900">{{ number_format($metrics['total_products']) }}</h3>
                    <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                        {{ $metrics['available_products'] }} Available
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">{{ $metrics['new_products'] }} New &bull; {{ $metrics['out_of_stock_products'] }} Out of stock</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <a href="{{ route('admin.social-commerce.products.index') }}" class="font-medium text-amber-600 hover:text-amber-700 flex items-center gap-1">
                    Manage Products &rarr;
                </a>
            </div>
        </div>

        <!-- Reels & Video Content Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-rose-300 transition-colors">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Reels &amp; Video</span>
                <span class="p-2 rounded-xl bg-rose-50 text-rose-600">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="flex items-baseline justify-between">
                    <h3 class="text-2xl font-bold text-slate-900">{{ number_format($metrics['total_reels']) }}</h3>
                    <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">
                        {{ $metrics['pending_reels'] }} In review
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">{{ $metrics['completed_reels'] }} Rendered &bull; 9:16 format</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <a href="{{ route('admin.social-commerce.reels.index') }}" class="font-medium text-rose-600 hover:text-rose-700 flex items-center gap-1">
                    Open Reel Studio &rarr;
                </a>
            </div>
        </div>

        <!-- Social Publishing Card -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-blue-300 transition-colors">
            <div class="flex items-center justify-between text-slate-500">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-600">Meta Publishing</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="flex items-baseline justify-between">
                    <h3 class="text-2xl font-bold text-slate-900">{{ number_format($metrics['queue_scheduled']) }}</h3>
                    <span class="text-xs font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                        {{ $metrics['queue_published'] }} Published
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">{{ $metrics['connected_accounts'] }} Connected account(s)</p>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <a href="{{ route('admin.social-commerce.queue.index') }}" class="font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1">
                    View Queue &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Action Launchpad -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
        <h4 class="text-sm font-semibold text-slate-900 mb-3">Quick Automation Workflows</h4>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <a href="{{ route('admin.social-commerce.inbox.index') }}" class="flex flex-col items-center text-center p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/60 transition-colors">
                <span class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 mb-2">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                </span>
                <span class="text-xs font-semibold text-slate-900">Upload Media</span>
                <span class="text-[10px] text-slate-500 mt-0.5">Drag &amp; Drop / Multi</span>
            </a>

            <a href="{{ route('admin.social-commerce.inbox.index') }}#zip-import" class="flex flex-col items-center text-center p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/60 transition-colors">
                <span class="p-2.5 rounded-xl bg-blue-500/10 text-blue-600 mb-2">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                </span>
                <span class="text-xs font-semibold text-slate-900">Import ZIP</span>
                <span class="text-[10px] text-slate-500 mt-0.5">Bulk WhatsApp Archive</span>
            </a>

            <a href="{{ route('admin.social-commerce.products.create') }}" class="flex flex-col items-center text-center p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/60 transition-colors">
                <span class="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 mb-2">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </span>
                <span class="text-xs font-semibold text-slate-900">Add Product</span>
                <span class="text-[10px] text-slate-500 mt-0.5">Supplier Catalog</span>
            </a>

            <a href="{{ route('admin.social-commerce.whatsapp-status.index') }}" class="flex flex-col items-center text-center p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/60 transition-colors">
                <span class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-600 mb-2">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                </span>
                <span class="text-xs font-semibold text-slate-900">WhatsApp Status</span>
                <span class="text-[10px] text-slate-500 mt-0.5">Generate 9:16 Card</span>
            </a>

            <a href="{{ route('admin.social-commerce.reels.create') }}" class="flex flex-col items-center text-center p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/60 transition-colors">
                <span class="p-2.5 rounded-xl bg-rose-500/10 text-rose-600 mb-2">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
                <span class="text-xs font-semibold text-slate-900">Reel Studio</span>
                <span class="text-[10px] text-slate-500 mt-0.5">7 Design Templates</span>
            </a>

            <a href="{{ route('admin.social-commerce.connections.index') }}" class="flex flex-col items-center text-center p-3 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200/60 transition-colors">
                <span class="p-2.5 rounded-xl bg-indigo-500/10 text-indigo-600 mb-2">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                    </svg>
                </span>
                <span class="text-xs font-semibold text-slate-900">Connections</span>
                <span class="text-[10px] text-slate-500 mt-0.5">Meta OAuth Setup</span>
            </a>
        </div>
    </div>

    <!-- Recent Media Inbox Grid & Recent Products Table -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Media Inbox -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-semibold text-slate-900">Recent Supplier Media Inbox</h4>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Latest 8</span>
                    </div>
                    <a href="{{ route('admin.social-commerce.inbox.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">View All</a>
                </div>

                @if($recent_media->isEmpty())
                <div class="py-12 text-center text-slate-400">
                    <svg class="size-10 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                    </svg>
                    <p class="text-sm font-medium text-slate-600">No supplier media uploaded yet</p>
                    <p class="text-xs text-slate-400 mt-1">Upload images or import a ZIP file received from WhatsApp supplier groups.</p>
                    <a href="{{ route('admin.social-commerce.inbox.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 hover:underline">
                        Upload First Batch &rarr;
                    </a>
                </div>
                @else
                <div class="grid grid-cols-4 gap-2 sm:gap-3">
                    @foreach($recent_media as $asset)
                    <div class="group relative aspect-square rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                        <img src="{{ $asset->url }}" alt="{{ $asset->file_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity p-1.5 flex flex-col justify-end text-[10px] text-white">
                            <span class="truncate font-medium">{{ $asset->supplier?->name ?? 'WhatsApp' }}</span>
                            <span class="text-[9px] text-amber-300">{{ $asset->formatted_size }}</span>
                        </div>
                        @if($asset->status === 'UNPROCESSED')
                        <span class="absolute top-1 right-1 size-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Duplicate detection active (SHA-256 + 64-bit dHash)</span>
                <a href="{{ route('admin.social-commerce.inbox.index') }}" class="font-medium text-slate-700 hover:text-slate-900">Manage Inbox &rarr;</a>
            </div>
        </div>

        <!-- Recent Products in Library -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-semibold text-slate-900">Supplier Product Library</h4>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Recent</span>
                    </div>
                    <a href="{{ route('admin.social-commerce.products.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">View All</a>
                </div>

                @if($recent_products->isEmpty())
                <div class="py-12 text-center text-slate-400">
                    <svg class="size-10 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    <p class="text-sm font-medium text-slate-600">No products created yet</p>
                    <p class="text-xs text-slate-400 mt-1">Convert items from your Media Inbox into products with codes, prices and availability.</p>
                    <a href="{{ route('admin.social-commerce.products.create') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-amber-600 hover:underline">
                        Create First Product &rarr;
                    </a>
                </div>
                @else
                <div class="divide-y divide-slate-100">
                    @foreach($recent_products as $prod)
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <img src="{{ $prod->primary_image_url }}" alt="{{ $prod->name }}" class="size-10 rounded-lg object-cover bg-slate-100 shrink-0">
                            <div class="min-w-0">
                                <a href="{{ route('admin.social-commerce.products.show', $prod) }}" class="text-xs font-semibold text-slate-900 truncate hover:text-amber-600 block">
                                    {{ $prod->name }}
                                </a>
                                <p class="text-[11px] text-slate-500">
                                    <span class="font-mono text-slate-600">{{ $prod->product_code }}</span>
                                    &bull; {{ $prod->category_name }}
                                    @if($prod->special_offer_price_rupees)
                                        &bull; <span class="font-semibold text-slate-900">₹{{ number_format($prod->special_offer_price_rupees, 0) }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @php
                                $badgeClass = match($prod->availability_status) {
                                    'AVAILABLE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'NEW' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'TEMPORARILY_UNAVAILABLE' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'OUT_OF_STOCK' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full border {{ $badgeClass }}">
                                {{ str_replace('_', ' ', $prod->availability_status) }}
                            </span>
                            <a href="{{ route('admin.social-commerce.whatsapp-status.index', ['product_id' => $prod->id]) }}"
                               title="Generate WhatsApp Status"
                               class="p-1 rounded text-slate-400 hover:text-emerald-600 hover:bg-emerald-50">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>{{ $metrics['promoted_to_web_count'] }} products published to store</span>
                <a href="{{ route('admin.social-commerce.products.create') }}" class="font-medium text-amber-600 hover:text-amber-700">+ Add Product</a>
            </div>
        </div>
    </div>
</div>
@endsection
