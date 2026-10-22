@extends('layouts.admin')

@section('title', 'Home Page Sections')

@section('content')
<div class="space-y-6" x-data="{ editingBlock: null }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Home Page Sections</h1>
                <span class="rounded-full bg-amber-500/10 border border-amber-500/20 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                    {{ $blocks->count() }} Sections
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Manage the content, layout, and sequence of sections displayed on the storefront home page.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ url('/') }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition-colors shadow-2xs">
                <svg class="size-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                View Live Store
            </a>
            <a href="{{ route('admin.banners.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-semibold tracking-wide uppercase transition-all shadow-sm">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                Manage Banners
            </a>
        </div>
    </div>

    <!-- Blocks List -->
    <div class="space-y-4">
        @forelse($blocks as $index => $block)
            <div class="rounded-xl border {{ $block->is_visible ? 'border-slate-200/90 bg-white' : 'border-dashed border-slate-300 bg-slate-50/70 opacity-75' }} shadow-xs p-5 transition-all">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Left: Position & Type Details -->
                    <div class="flex items-start gap-4">
                        <div class="flex flex-col items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-700 font-bold font-mono text-xs border border-slate-200 shrink-0 select-none">
                            {{ $index + 1 }}
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded text-[11px] font-bold tracking-wider uppercase
                                    @if($block->type->value === 'HERO') bg-purple-100 text-purple-800 border border-purple-200
                                    @elseif($block->type->value === 'PROMO_CAROUSEL') bg-amber-100 text-amber-900 border border-amber-200
                                    @elseif($block->type->value === 'TILE_GRID') bg-blue-100 text-blue-800 border border-blue-200
                                    @elseif($block->type->value === 'PRODUCT_RAIL') bg-emerald-100 text-emerald-800 border border-emerald-200
                                    @elseif($block->type->value === 'PROMO_BANNER') bg-rose-100 text-rose-800 border border-rose-200
                                    @else bg-slate-100 text-slate-800 border border-slate-200 @endif">
                                    {{ str_replace('_', ' ', $block->type->value) }}
                                </span>

                                @if($block->is_visible)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span> Live on page
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-600 border border-slate-300">
                                        <span class="size-1.5 rounded-full bg-slate-400"></span> Switched off
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-sm font-bold text-slate-900">
                                {{ $block->heading ?: 'Default ' . ucwords(strtolower(str_replace('_', ' ', $block->type->value))) . ' Section' }}
                            </h3>

                            @if($block->eyebrow)
                                <p class="text-xs text-amber-700 font-medium tracking-wide uppercase">{{ $block->eyebrow }}</p>
                            @endif

                            @if($block->body)
                                <p class="text-xs text-slate-500 line-clamp-1 max-w-xl">{{ $block->body }}</p>
                            @endif

                            @if($block->primary_label || $block->primary_path)
                                <div class="flex items-center gap-2 pt-1 text-[11px] text-slate-400">
                                    <span>CTA:</span>
                                    <span class="font-semibold text-slate-700">{{ $block->primary_label ?: 'Button' }}</span>
                                    <span class="font-mono text-slate-400">({{ $block->primary_path ?: '/' }})</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-2 shrink-0 self-end md:self-center">
                        @if($canEdit)
                            <!-- Move Up -->
                            <form method="POST" action="{{ route('admin.homepage.move', ['id' => $block->id, 'direction' => 'up']) }}" class="inline">
                                @csrf
                                <button type="submit" {{ $index === 0 ? 'disabled' : '' }}
                                        class="p-1.5 rounded border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 disabled:opacity-30 disabled:hover:bg-white transition-colors" title="Move Up">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                                    </svg>
                                </button>
                            </form>

                            <!-- Move Down -->
                            <form method="POST" action="{{ route('admin.homepage.move', ['id' => $block->id, 'direction' => 'down']) }}" class="inline">
                                @csrf
                                <button type="submit" {{ $index === $blocks->count() - 1 ? 'disabled' : '' }}
                                        class="p-1.5 rounded border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 disabled:opacity-30 disabled:hover:bg-white transition-colors" title="Move Down">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </form>

                            <!-- Visibility Toggle -->
                            <form method="POST" action="{{ route('admin.homepage.toggle', $block->id) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="px-2.5 py-1.5 rounded text-xs font-semibold border transition-colors {{ $block->is_visible ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                                    {{ $block->is_visible ? 'Hide' : 'Show' }}
                                </button>
                            </form>

                            <!-- Edit Details Button -->
                            <button type="button" @click="editingBlock = {{ json_encode($block) }}"
                                    class="px-3 py-1.5 rounded bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-2xs transition-colors">
                                Edit Section
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center rounded-xl border-2 border-dashed border-slate-200 bg-white">
                <p class="text-sm font-semibold text-slate-700">No home page sections found</p>
                <p class="text-xs text-slate-400 mt-1">Configure your home page sections or check database seeds.</p>
            </div>
        @endforelse
    </div>

    <!-- Edit Modal -->
    <div x-show="editingBlock" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="editingBlock = null"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200">
                <form method="POST" :action="'{{ url('/admin/homepage/blocks') }}/' + (editingBlock ? editingBlock.id : '')" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                            <h3 class="text-base font-bold text-slate-900">
                                Edit Section: <span class="text-amber-600" x-text="editingBlock ? editingBlock.type : ''"></span>
                            </h3>
                            <button type="button" @click="editingBlock = null" class="text-slate-400 hover:text-slate-700">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Eyebrow -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Eyebrow / Subheading</label>
                            <input type="text" name="eyebrow" :value="editingBlock ? editingBlock.eyebrow : ''" placeholder="e.g. Festive Collection"
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>

                        <!-- Heading -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Section Heading / Title</label>
                            <input type="text" name="heading" :value="editingBlock ? editingBlock.heading : ''" placeholder="e.g. Everyday essentials, thoughtfully chosen."
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>

                        <!-- Body text -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Body / Description</label>
                            <textarea name="body" rows="2" :value="editingBlock ? editingBlock.body : ''" placeholder="Optional paragraph below the title..."
                                      class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none"></textarea>
                        </div>

                        <!-- Primary CTA -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Primary Button Label</label>
                                <input type="text" name="primary_label" :value="editingBlock ? editingBlock.primary_label : ''" placeholder="e.g. Shop Now"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Primary Button URL</label>
                                <input type="text" name="primary_path" :value="editingBlock ? editingBlock.primary_path : ''" placeholder="e.g. /shop"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                            </div>
                        </div>

                        <!-- Secondary CTA -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Secondary Button Label</label>
                                <input type="text" name="secondary_label" :value="editingBlock ? editingBlock.secondary_label : ''" placeholder="e.g. Explore"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Secondary Button URL</label>
                                <input type="text" name="secondary_path" :value="editingBlock ? editingBlock.secondary_path : ''" placeholder="e.g. /smart-living"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                            </div>
                        </div>

                        <!-- Image upload -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Replace Image (WebP / JPG / PNG)</label>
                            <input type="file" name="image" accept="image/*"
                                   class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-3.5 flex items-center justify-end gap-3 border-t border-slate-200">
                        <button type="button" @click="editingBlock = null"
                                class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
