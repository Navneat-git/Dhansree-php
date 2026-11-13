@extends('layouts.admin')

@section('title', 'Supplier Media Inbox')
@section('page_title', 'Supplier Media Inbox')

@section('header_actions')
<div class="flex flex-wrap items-center gap-2">
    <!-- ZIP Import Button -->
    <button type="button" @click="$dispatch('open-modal', 'zip-upload-modal')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-500 shadow-xs transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
        </svg>
        Import ZIP Archive
    </button>

    <!-- Multi-file / Drag-and-drop Upload Button -->
    <button type="button" @click="$dispatch('open-modal', 'media-upload-modal')"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-500 shadow-xs transition-colors">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Upload Images &amp; Videos
    </button>
</div>
@endsection

@section('content')
<div x-data="{
    selectedIds: [],
    selectAll: false,
    bulkAction: '',
    toggleAll() {
        if (this.selectAll) {
            this.selectedIds = [{{ $assets->pluck('id')->implode(',') }}];
        } else {
            this.selectedIds = [];
        }
    },
    updateSelectAll() {
        this.selectAll = (this.selectedIds.length === {{ $assets->count() }}) && {{ $assets->count() }} > 0;
    }
}" class="space-y-5">

    <!-- Filters Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.social-commerce.inbox.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <!-- Supplier Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Supplier</label>
                <select name="supplier_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- WhatsApp Group Name Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">WhatsApp Group</label>
                <input type="text" name="supplier_group_name" value="{{ request('supplier_group_name') }}" placeholder="Group name..."
                       list="group-names-list"
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                <datalist id="group-names-list">
                    @foreach($groupNames as $gName)
                        <option value="{{ $gName }}"></option>
                    @endforeach
                </datalist>
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Category</label>
                <select name="category_name" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category_name') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Media Type Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Type</label>
                <select name="media_type" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                    <option value="">All Media</option>
                    <option value="IMAGE" {{ request('media_type') === 'IMAGE' ? 'selected' : '' }}>Images Only</option>
                    <option value="VIDEO" {{ request('media_type') === 'VIDEO' ? 'selected' : '' }}>Videos Only</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">Status</label>
                <select name="status" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                    <option value="">All Statuses</option>
                    <option value="UNPROCESSED" {{ request('status') === 'UNPROCESSED' ? 'selected' : '' }}>Unprocessed</option>
                    <option value="PROCESSED" {{ request('status') === 'PROCESSED' ? 'selected' : '' }}>Processed</option>
                    <option value="IN_PRODUCT" {{ request('status') === 'IN_PRODUCT' ? 'selected' : '' }}>In Product</option>
                </select>
            </div>

            <!-- Submit / Reset -->
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['supplier_id', 'supplier_group_name', 'category_name', 'media_type', 'status']))
                    <a href="{{ route('admin.social-commerce.inbox.index') }}" class="p-1.5 rounded-lg border border-slate-300 text-slate-500 hover:text-slate-900 hover:bg-slate-50" title="Reset Filters">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Bulk Action Floating Bar (Active when items selected) -->
    <div x-show="selectedIds.length > 0" x-cloak
         class="sticky top-4 z-20 bg-slate-900 text-white rounded-2xl p-3.5 shadow-xl border border-slate-800 flex flex-wrap items-center justify-between gap-3 animate-fade-in">
        <div class="flex items-center gap-3">
            <span class="size-2 rounded-full bg-amber-400"></span>
            <span class="text-xs font-semibold">
                <span x-text="selectedIds.length"></span> media item(s) selected
            </span>
            <button type="button" @click="selectedIds = []; selectAll = false" class="text-[11px] text-slate-400 hover:text-white underline">
                Clear selection
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Create Product from Selected -->
            <button type="button" @click="$dispatch('open-modal', 'create-product-modal')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500 text-slate-950 hover:bg-amber-400 shadow-xs transition-colors">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Product Library Item
            </button>

            <!-- Mark Processed -->
            <form method="POST" action="{{ route('admin.social-commerce.inbox.bulk') }}" class="inline">
                @csrf
                <input type="hidden" name="action" value="mark_processed">
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="asset_ids[]" :value="id">
                </template>
                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200">
                    Mark Processed
                </button>
            </form>

            <!-- Delete Selected -->
            <form method="POST" action="{{ route('admin.social-commerce.inbox.bulk') }}" class="inline" onsubmit="return confirm('Permanently delete selected media?');">
                @csrf
                <input type="hidden" name="action" value="delete">
                <template x-for="id in selectedIds" :key="id">
                    <input type="hidden" name="asset_ids[]" :value="id">
                </template>
                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-rose-600/80 hover:bg-rose-600 text-white">
                    Delete Selected
                </button>
            </form>
        </div>
    </div>

    <!-- Gallery Grid Header Bar -->
    <div class="flex items-center justify-between px-1">
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500 size-4">
                <span>Select All on Page ({{ $assets->count() }})</span>
            </label>
        </div>
        <p class="text-xs text-slate-500">
            Showing {{ $assets->firstItem() ?? 0 }}-{{ $assets->lastItem() ?? 0 }} of {{ $assets->total() }} media files
        </p>
    </div>

    <!-- Gallery Grid -->
    @if($assets->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="size-16 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mx-auto mb-3">
            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
        </div>
        <h3 class="text-base font-semibold text-slate-900">No media found</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            Try adjusting your search filters or upload your first batch of wholesale images/videos from WhatsApp groups.
        </p>
        <div class="mt-4 flex items-center justify-center gap-3">
            <button type="button" @click="$dispatch('open-modal', 'media-upload-modal')"
                    class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-500 shadow-xs">
                Upload Media Files
            </button>
            <button type="button" @click="$dispatch('open-modal', 'zip-upload-modal')"
                    class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 shadow-xs">
                Import WhatsApp ZIP
            </button>
        </div>
    </div>
    @else
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
        @foreach($assets as $asset)
        <div class="group relative bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs hover:shadow-md hover:border-slate-300 transition-all flex flex-col"
             :class="selectedIds.includes({{ $asset->id }}) ? 'ring-2 ring-amber-500 border-amber-500' : ''">

            <!-- Checkbox Overlay -->
            <label class="absolute top-2 left-2 z-10 cursor-pointer">
                <input type="checkbox" value="{{ $asset->id }}" x-model="selectedIds" @change="updateSelectAll()"
                       class="rounded border-white/80 bg-slate-900/60 checked:bg-amber-500 checked:border-amber-500 text-amber-500 focus:ring-0 size-4.5 shadow-sm">
            </label>

            <!-- Status Pill Top Right -->
            <div class="absolute top-2 right-2 z-10 flex flex-col items-end gap-1">
                @if($asset->media_type === 'VIDEO')
                    <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-purple-600/90 text-white backdrop-blur-xs shadow-2xs">
                        VIDEO
                    </span>
                @endif

                @php
                    $statusBadge = match($asset->status) {
                        'UNPROCESSED' => 'bg-emerald-600/90 text-white',
                        'PROCESSED' => 'bg-blue-600/90 text-white',
                        'IN_PRODUCT' => 'bg-amber-600/90 text-white',
                        default => 'bg-slate-600/90 text-white',
                    };
                @endphp
                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-semibold backdrop-blur-xs shadow-2xs {{ $statusBadge }}">
                    {{ $asset->status }}
                </span>
            </div>

            <!-- Media Preview -->
            <div class="relative aspect-square bg-slate-100 overflow-hidden">
                @if($asset->media_type === 'VIDEO')
                    <video src="{{ $asset->url }}" class="w-full h-full object-cover" muted preload="metadata"></video>
                    <div class="absolute inset-0 flex items-center justify-center bg-black/20 pointer-events-none">
                        <div class="size-9 rounded-full bg-white/90 text-slate-900 flex items-center justify-center shadow-md">
                            <svg class="size-4.5 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.3 2.841A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                            </svg>
                        </div>
                    </div>
                @else
                    <img src="{{ $asset->url }}" alt="{{ $asset->file_name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                         loading="lazy">
                @endif
            </div>

            <!-- Meta Details -->
            <div class="p-2.5 flex-1 flex flex-col justify-between text-[11px] bg-white">
                <div>
                    <p class="font-semibold text-slate-900 truncate" title="{{ $asset->supplier?->name ?? 'WhatsApp Group' }}">
                        {{ $asset->supplier?->name ?? 'WhatsApp Group' }}
                    </p>
                    <p class="text-[10px] text-slate-500 truncate" title="{{ $asset->supplier_group_name }}">
                        {{ $asset->supplier_group_name ?: 'Direct Upload' }}
                    </p>
                    @if(!empty($asset->metadata['raw_description']))
                        <p class="mt-1 text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded truncate border border-emerald-100" title="{{ $asset->metadata['raw_description'] }}">
                            💬 {{ \Illuminate\Support\Str::limit($asset->metadata['raw_description'], 28) }}
                        </p>
                    @endif
                </div>

                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                    <span>{{ $asset->formatted_size }}</span>
                    <span>{{ $asset->created_at->format('d M, H:i') }}</span>
                </div>

                <!-- Action links on hover -->
                <div class="mt-2 flex items-center justify-between gap-1 pt-1 border-t border-slate-100">
                    <a href="{{ route('admin.social-commerce.whatsapp-status.index', ['asset_id' => $asset->id]) }}"
                       class="text-[10px] font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-0.5">
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                        </svg>
                        Status
                    </a>

                    <form method="POST" action="{{ route('admin.social-commerce.inbox.destroy', $asset) }}"
                          onsubmit="return confirm('Delete this media file?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-[10px] text-slate-400 hover:text-rose-600">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="pt-2">
        {{ $assets->links() }}
    </div>
    @endif

    <!-- MODAL 1: Multi-file & Drag-and-Drop Upload Modal -->
    <div x-data="{ open: false, isDragging: false, fileCount: 0 }"
         x-show="open"
         x-on:open-modal.window="if ($event.detail === 'media-upload-modal') open = true"
         x-on:keydown.escape.window="open = false"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="open = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Upload WhatsApp Media</h3>
                            <p class="text-xs text-slate-500">Multi-image &amp; video drag-and-drop</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.social-commerce.inbox.upload') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <!-- Drag & Drop Target Box -->
                    <div class="relative border-2 border-dashed rounded-2xl p-6 text-center transition-colors cursor-pointer"
                         :class="isDragging ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-300 hover:border-slate-400 bg-slate-50'"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; fileCount = $refs.fileInput.files.length"
                         @click="$refs.fileInput.click()">
                        <input type="file" name="files[]" multiple accept="image/*,video/mp4,video/quicktime,video/webm"
                               x-ref="fileInput" @change="fileCount = $refs.fileInput.files.length" class="hidden">

                        <div class="flex flex-col items-center justify-center">
                            <svg class="size-10 text-slate-400 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            <p class="text-xs font-semibold text-slate-700">
                                Drag &amp; drop images or videos here, or <span class="text-emerald-600 underline">browse</span>
                            </p>
                            <p class="text-[10px] text-slate-400 mt-1">Supports JPG, PNG, WEBP, MP4 &bull; Max 100MB each</p>
                            <template x-if="fileCount > 0">
                                <p class="mt-2 text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                                    <span x-text="fileCount"></span> file(s) selected
                                </p>
                            </template>
                        </div>
                    </div>

                    <!-- Supplier Selection / Creation -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Select Supplier</label>
                            <select name="supplier_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                                <option value="">-- Choose Existing Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Or New Supplier Name</label>
                            <input type="text" name="supplier_name" placeholder="e.g. Surat Wholesale Hub"
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier WhatsApp Group</label>
                            <input type="text" name="supplier_group_name" placeholder="e.g. Surat Suits Group 3"
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Category</label>
                            <select name="category_name" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                                <option value="">Auto / Uncategorized</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- WhatsApp Message / Product Description -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            WhatsApp Product Description / Supplier Message (Optional)
                        </label>
                        <textarea name="whatsapp_description" rows="3"
                                  placeholder="Paste the supplier's WhatsApp text here (e.g. fabric details, sizes, pricing, cut, catalogue name...)"
                                  class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5"></textarea>
                        <p class="text-[10px] text-slate-400 mt-1">This text is saved with the photos and automatically used for generating captions &amp; live website product details.</p>
                    </div>

                    <!-- Optional Immediate Product Library Creation -->
                    <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200/80 space-y-2.5" x-data="{ autoCreate: false }">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_create_product" value="1" x-model="autoCreate"
                                   class="rounded text-amber-500 focus:ring-amber-500 size-4">
                            <span class="text-xs font-semibold text-slate-900">
                                Also create Product in Product Library immediately with these uploaded photos
                            </span>
                        </label>
                        <div x-show="autoCreate" x-cloak class="space-y-2.5 pt-2 border-t border-amber-200/60 text-xs">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Product Name</label>
                                <input type="text" name="product_name" placeholder="e.g. Royal Chanderi Embroidered Suit Set"
                                       class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Fabric</label>
                                    <input type="text" name="fabric" placeholder="e.g. Pure Cotton, Silk"
                                           class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Special Offer Price (₹)</label>
                                    <input type="number" step="0.01" name="special_offer_price" placeholder="e.g. 899"
                                           class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Available Colours</label>
                                    <input type="text" name="available_colors" placeholder="Maroon, Mustard, Teal"
                                           class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
                                </div>
                                <div>
                                    <label class="block font-semibold text-slate-700 mb-1">Available Sizes</label>
                                    <input type="text" name="available_sizes" placeholder="M, L, XL, XXL or Unstitched"
                                           class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="open = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-500 shadow-xs">
                            Upload &amp; Detect Duplicates
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: ZIP Archive Import Modal -->
    <div x-data="{ open: false }"
         x-show="open"
         x-on:open-modal.window="if ($event.detail === 'zip-upload-modal') open = true"
         x-on:keydown.escape.window="open = false"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="open = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Import ZIP File Archive</h3>
                            <p class="text-xs text-slate-500">Unpack all images &amp; videos automatically</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.social-commerce.inbox.upload-zip') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Select .ZIP Archive File</label>
                        <input type="file" name="zip_file" accept=".zip,application/zip" required
                               class="w-full text-xs file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 rounded-lg border border-slate-300 p-1">
                        <p class="text-[10px] text-slate-400 mt-1">Up to 512MB archive. Skips system files (.DS_Store, __MACOSX) automatically.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier</label>
                            <select name="supplier_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                                <option value="">-- Choose Existing Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Or New Supplier Name</label>
                            <input type="text" name="supplier_name" placeholder="Supplier name..."
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier WhatsApp Group</label>
                            <input type="text" name="supplier_group_name" placeholder="e.g. Daily Surat Designs"
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Default Category</label>
                            <select name="category_name" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                                <option value="">Auto / Uncategorized</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- WhatsApp Message / Product Description -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            WhatsApp Product Description / Supplier Message (Optional)
                        </label>
                        <textarea name="whatsapp_description" rows="3"
                                  placeholder="Paste the supplier's WhatsApp text here (e.g. fabric details, sizes, pricing, cut, catalogue name...)"
                                  class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="open = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-xs font-semibold text-white hover:bg-blue-500 shadow-xs">
                            Unpack &amp; Import ZIP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Convert Selected Assets to Product Modal -->
    <div x-data="{ open: false }"
         x-show="open"
         x-on:open-modal.window="if ($event.detail === 'create-product-modal') open = true"
         x-on:keydown.escape.window="open = false"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="open = false"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Create Supplier Product</h3>
                            <p class="text-xs text-slate-500">Group <span x-text="selectedIds.length" class="font-bold text-amber-600"></span> selected assets into one product</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.social-commerce.inbox.move-to-product') }}" class="mt-4 space-y-3.5">
                    @csrf
                    <template x-for="id in selectedIds" :key="id">
                        <input type="hidden" name="asset_ids[]" :value="id">
                    </template>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name *</label>
                        <input type="text" name="product_name" required placeholder="e.g. Royal Chanderi Embroidered Suit Set"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Category *</label>
                            <select name="category_name" required class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Fabric</label>
                            <input type="text" name="fabric" placeholder="e.g. Pure Cotton, Chanderi Silk, Georgette"
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Supplier Cost (₹)</label>
                            <input type="number" step="0.01" name="purchase_price" placeholder="e.g. 550"
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Suggested MRP (₹)</label>
                            <input type="number" step="0.01" name="suggested_selling_price" placeholder="e.g. 1299"
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Special Offer (₹)</label>
                            <input type="number" step="0.01" name="special_offer_price" placeholder="e.g. 899"
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Colours (comma separated)</label>
                            <input type="text" name="available_colors" placeholder="Maroon, Mustard, Teal, Pink"
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Sizes (comma separated)</label>
                            <input type="text" name="available_sizes" placeholder="M, L, XL, XXL or Unstitched"
                                   class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                    </div>

                    <!-- Description / WhatsApp Message Details -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            Product Description / WhatsApp Message
                        </label>
                        <textarea name="description" rows="3"
                                  placeholder="Enter description, cut, work, or leave blank to automatically use WhatsApp description from the uploaded photos..."
                                  class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5"></textarea>
                        <p class="text-[10px] text-slate-400 mt-1">If left blank, it automatically inherits the WhatsApp message saved with these photos.</p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="open = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 text-xs font-semibold text-slate-950 hover:bg-amber-400 shadow-xs">
                            Create &amp; Open Product &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
