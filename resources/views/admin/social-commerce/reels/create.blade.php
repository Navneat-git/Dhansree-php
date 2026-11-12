@extends('layouts.admin')

@section('title', 'Compose Fashion Reel')
@section('page_title', 'Reel Studio: Compose Reel (9:16)')

@section('header_actions')
<a href="{{ route('admin.social-commerce.reels.index') }}"
   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs transition-colors">
    &larr; Back to Reels
</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.social-commerce.reels.store') }}"
      x-data="{
          templateCode: 'NEW_ARRIVALS',
          durationSeconds: 15,
          selectedItems: [],
          addProductMedia(prod, media) {
              this.selectedItems.push({
                  media_asset_id: media.id,
                  social_product_id: prod.id,
                  image_url: media.url,
                  headline: prod.name,
                  price_text: prod.special_offer_price_rupees ? '₹' + Math.round(prod.special_offer_price_rupees) : '',
                  display_seconds: 2.5
              });
          },
          removeItem(idx) {
              this.selectedItems.splice(idx, 1);
          },
          moveUp(idx) {
              if (idx > 0) {
                  const temp = this.selectedItems[idx];
                  this.selectedItems[idx] = this.selectedItems[idx - 1];
                  this.selectedItems[idx - 1] = temp;
              }
          },
          moveDown(idx) {
              if (idx < this.selectedItems.length - 1) {
                  const temp = this.selectedItems[idx];
                  this.selectedItems[idx] = this.selectedItems[idx + 1];
                  this.selectedItems[idx + 1] = temp;
              }
          }
      }" class="space-y-6 max-w-5xl mx-auto">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Reel Configuration & Media Sequencing -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Title & Template Selection -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    1. Choose Reel Template &amp; Title
                </h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reel Title *</label>
                    <input type="text" name="title" required
                           value="{{ old('title', $preselectedProduct ? 'New Arrivals: ' . $preselectedProduct->name : 'Fresh Fashion Collection - Daily Arrivals') }}"
                           placeholder="e.g. Festive Anarkali Suits Edit 2026"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                </div>

                <!-- 7 Template Selector Radio Cards -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">Reel Template Style *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($templates as $tpl)
                        <label class="relative flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all hover:bg-slate-50"
                               :class="templateCode === '{{ $tpl->code }}' ? 'border-amber-500 bg-amber-50/50 ring-2 ring-amber-500/20' : 'border-slate-200 bg-white'">
                            <input type="radio" name="template_code" value="{{ $tpl->code }}" x-model="templateCode" class="mt-0.5 text-amber-500 focus:ring-amber-500 size-4">
                            <div>
                                <span class="font-bold text-xs text-slate-900 block leading-tight">{{ $tpl->name }}</span>
                                <span class="text-[10px] text-slate-500 mt-0.5 block">{{ $tpl->tagline }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Total Reel Duration (Seconds)</label>
                        <select name="duration_seconds" x-model="durationSeconds" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                            <option value="12">12 seconds (Fast Paced)</option>
                            <option value="15" selected>15 seconds (Standard Instagram Reel)</option>
                            <option value="20">20 seconds (Multi-Product Showcase)</option>
                            <option value="30">30 seconds (Detailed Fashion Lookbook)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Audio / Music Track</label>
                        <select name="audio_track" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                            <option value="festive_beats">Festive Instrumental Indian Beats (Royalty Free)</option>
                            <option value="luxury_ambient">Luxury Fashion Ambient (Soft Sitar &amp; Flute)</option>
                            <option value="upbeat_trending">Upbeat Trending Reel Rhythm</option>
                            <option value="original_audio">Original Brand Jingle (Dhansree Fashion)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Product & Media Selector -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="text-sm font-semibold text-slate-900">
                        2. Add Slides from Supplier Products
                    </h3>
                    <span class="text-xs text-slate-400">Click an image below to add to reel slides</span>
                </div>

                <div class="space-y-3 max-h-72 overflow-y-auto p-1 scrollbar-thin">
                    @foreach($products as $prod)
                    <div class="p-2.5 rounded-xl border border-slate-200/80 bg-slate-50">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-800">{{ $prod->name }}</span>
                            <span class="font-mono text-[10px] text-slate-500">{{ $prod->product_code }}</span>
                        </div>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            @foreach($prod->mediaAssets as $med)
                            <div @click="addProductMedia({{ json_encode($prod) }}, {{ json_encode($med) }})"
                                 class="relative size-14 rounded-lg overflow-hidden border border-slate-200 hover:border-amber-500 cursor-pointer shrink-0 transition-transform hover:scale-105"
                                 title="Click to add to Reel">
                                <img src="{{ $med->url }}" alt="{{ $med->file_name }}" class="w-full h-full object-cover">
                                <span class="absolute bottom-0 inset-x-0 bg-slate-950/70 text-white text-[8px] text-center font-bold py-0.5">
                                    + ADD
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Reel Storyboard / Slide Sequencer -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">3. Reel Storyboard Sequence</h3>
                        <p class="text-xs text-slate-500">Reorder slides using &uarr; and &darr; buttons</p>
                    </div>
                    <span class="text-xs font-bold text-amber-600">
                        <span x-text="selectedItems.length"></span> Slide(s)
                    </span>
                </div>

                <template x-if="selectedItems.length === 0">
                    <div class="p-8 text-center text-slate-400 bg-slate-50 rounded-xl">
                        <p class="text-xs font-medium">No slides added yet.</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Click the "+ ADD" button on any product image above.</p>
                    </div>
                </template>

                <div class="space-y-2.5">
                    <template x-for="(item, idx) in selectedItems" :key="idx">
                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                            <!-- Index Pill -->
                            <span class="size-6 rounded-md bg-slate-900 text-amber-400 font-bold text-xs flex items-center justify-center shrink-0" x-text="idx + 1"></span>

                            <!-- Thumb -->
                            <img :src="item.image_url" class="size-12 rounded-lg object-cover bg-slate-200 shrink-0 border border-slate-200">

                            <!-- Inputs -->
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                <input type="hidden" :name="'items[' + idx + '][media_asset_id]'" :value="item.media_asset_id">
                                <input type="hidden" :name="'items[' + idx + '][social_product_id]'" :value="item.social_product_id">

                                <div class="sm:col-span-2">
                                    <input type="text" :name="'items[' + idx + '][headline]'" x-model="item.headline" placeholder="Slide Headline..."
                                           class="w-full text-xs rounded-lg border-slate-300 py-1 px-2">
                                </div>
                                <div class="flex items-center gap-1">
                                    <input type="text" :name="'items[' + idx + '][price_text]'" x-model="item.price_text" placeholder="Price tag..."
                                           class="w-20 text-xs rounded-lg border-slate-300 py-1 px-2">
                                    <input type="number" step="0.5" :name="'items[' + idx + '][display_seconds]'" x-model="item.display_seconds"
                                           class="w-16 text-xs rounded-lg border-slate-300 py-1 px-1.5 text-center" title="Duration (sec)">
                                    <span class="text-[10px] text-slate-400">s</span>
                                </div>
                            </div>

                            <!-- Controls -->
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" @click="moveUp(idx)" class="p-1 rounded bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold" title="Move Up">&uarr;</button>
                                <button type="button" @click="moveDown(idx)" class="p-1 rounded bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold" title="Move Down">&darr;</button>
                                <button type="button" @click="removeItem(idx)" class="p-1 rounded bg-rose-100 hover:bg-rose-200 text-rose-700 text-xs" title="Remove">&times;</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <!-- Right 1 Col: Captions, Branding & Save Actions -->
        <div class="space-y-6">

            <!-- Caption & Hashtags Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
                    Instagram &amp; Facebook Caption
                </h3>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reel Caption</label>
                    <textarea name="caption" rows="8" placeholder="Enter engaging description or use auto-generated caption..."
                              class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5 leading-relaxed">{{ old('caption', $suggestedCaption) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Hashtags</label>
                    <input type="text" name="hashtags"
                           value="{{ old('hashtags', '#Dhansree #WomenFashion #LatestSuits #EthnicWear #FashionReels') }}"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                </div>
            </div>

            <!-- Save CTA Box -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-3">
                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-2">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    Save Reel &amp; Review
                </button>
                <p class="text-[10px] text-slate-400 text-center leading-normal">
                    Reel is saved as Draft. Requires explicit admin approval before publishing to Instagram or Facebook.
                </p>
            </div>

        </div>

    </div>
</form>
@endsection
