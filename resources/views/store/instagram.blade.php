@extends('layouts.store')

@section('title', $seoTitle ?? 'Dhansree Trends on Instagram (@dhansreetrends) — Reels, Styles & Shop The Look')
@section('description', $seoDescription ?? 'Explore the official Dhansree Trends Instagram lookbook. Watch trending Jaipur kurta reels, festive co-ord styling, and shop authentic ethnic fashion straight from our feed.')

@section('content')
<div class="bg-ivory/60 min-h-screen py-6 sm:py-10" x-data="{
    activeTab: 'all',
    modalOpen: false,
    currentIndex: 0,
    items: @js($posts->map(function ($p) {
        return [
            'id' => $p->id,
            'type' => $p->type,
            'caption' => $p->caption ?: '',
            'media_url' => $p->display_media_url,
            'video_url' => $p->video_url,
            'instagram_url' => $p->display_instagram_url,
            'likes' => (int) $p->likes_count,
            'formatted_likes' => $p->formatted_likes,
            'views' => $p->formatted_views,
            'comments_count' => (int) $p->comments_count,
            'tagged_product' => $p->taggedProduct ? [
                'id' => $p->taggedProduct->id,
                'name' => $p->taggedProduct->name,
                'price' => $p->taggedProduct->formatted_price,
                'mrp' => $p->taggedProduct->formatted_mrp,
                'image' => $p->taggedProduct->primary_image_url,
                'url' => url('/product/' . $p->taggedProduct->slug),
            ] : null,
            'liked' => false,
        ];
    })->values()),

    get currentItem() {
        return this.items[this.currentIndex] || null;
    },

    openItem(index) {
        this.currentIndex = index;
        this.modalOpen = true;
        document.body.classList.add('overflow-hidden');
    },

    closeModal() {
        this.modalOpen = false;
        document.body.classList.remove('overflow-hidden');
    },

    next() {
        this.currentIndex = (this.currentIndex + 1) % this.items.length;
    },

    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.items.length) % this.items.length;
    },

    toggleLike(item) {
        if (!item) return;
        if (!item.liked) {
            item.liked = true;
            item.likes += 1;
            item.formatted_likes = item.likes.toLocaleString();
            fetch('/instagram/posts/' + item.id + '/like', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                }
            }).catch(() => {});
        } else {
            item.liked = false;
            item.likes -= 1;
            item.formatted_likes = item.likes.toLocaleString();
        }
    },

    copyPostLink(item) {
        const url = item ? item.instagram_url : window.location.href;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Post link copied to clipboard!');
            });
        }
    }
}"
@keydown.escape.window="closeModal()"
@keydown.arrow-right.window="if(modalOpen) next()"
@keydown.arrow-left.window="if(modalOpen) prev()">

    <div class="max-w-4xl mx-auto px-4 sm:px-6">

        <!-- ─── Instagram Profile Header ────────────────────────────────────── -->
        <header class="border-b border-light-gold/60 pb-8 sm:pb-10 pt-2 sm:pt-4">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 sm:gap-10">
                
                <!-- Profile Avatar with Story Gradient Ring -->
                <div class="relative shrink-0">
                    <div class="size-24 sm:size-36 rounded-full p-1 bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] shadow-md flex items-center justify-center">
                        <div class="size-full rounded-full bg-ivory border-2 border-white flex items-center justify-center overflow-hidden">
                            <span class="font-serif font-bold text-2xl sm:text-4xl text-maroon-800 tracking-wider">DS</span>
                        </div>
                    </div>
                    <!-- Story Live Indicator -->
                    <span class="absolute bottom-1 right-1 size-5 rounded-full bg-emerald-500 border-2 border-white flex items-center justify-center text-[9px] text-white font-bold" title="Official Account">✓</span>
                </div>

                <!-- Profile Information Column -->
                <div class="flex-1 text-center sm:text-left space-y-4">
                    
                    <!-- Username & Action Buttons Row -->
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                        <div class="flex items-center gap-1.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-rich-black tracking-tight">
                                {{ $instagramHandle }}
                            </h1>
                            <!-- Verified Badge -->
                            <svg class="size-4.5 text-[#0095f6]" fill="currentColor" viewBox="0 0 24 24" title="Verified Brand">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>

                        <div class="flex items-center gap-2 pt-1 sm:pt-0">
                            <!-- Follow on Instagram CTA Button -->
                            <a href="{{ $instagramProfileUrl }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1.5 px-5 py-1.5 rounded-lg text-xs font-bold text-white bg-gradient-to-r from-rose-600 via-pink-600 to-amber-600 hover:opacity-95 transition-opacity shadow-sm">
                                <svg class="size-3.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                                <span>Follow</span>
                            </a>

                            <!-- Message / WhatsApp CTA -->
                            <a href="https://wa.me/919887776777?text=Hi%20Dhansree%20Trends,%20I%20saw%20your%20Instagram%20collection!" target="_blank"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-rich-black bg-white border border-light-gold/80 hover:bg-warm-beige transition-colors shadow-2xs">
                                <span>💬 Message</span>
                            </a>
                        </div>
                    </div>

                    <!-- Statistics Line (Posts, Followers, Following) -->
                    <div class="flex items-center justify-center sm:justify-start gap-7 text-xs sm:text-sm text-rich-black">
                        <div>
                            <span class="font-bold text-slate-900">{{ $posts->count() }}</span>
                            <span class="text-rich-black/70">posts</span>
                        </div>
                        <div>
                            <span class="font-bold text-slate-900">14.8K</span>
                            <span class="text-rich-black/70">followers</span>
                        </div>
                        <div>
                            <span class="font-bold text-slate-900">186</span>
                            <span class="text-rich-black/70">following</span>
                        </div>
                    </div>

                    <!-- Bio Section -->
                    <div class="text-xs sm:text-sm text-rich-black/85 space-y-1 leading-relaxed max-w-lg mx-auto sm:mx-0">
                        <p class="font-bold text-rich-black">{{ $settings->legal_name ?: 'Dhanshree Trends' }}</p>
                        <p class="text-rich-black/70 text-xs">Clothing Brand • Ethnic Kurties, Co-Ords &amp; Modern Living</p>
                        <p>🌸 Pure Handcrafted Jaipur Kurta Sets, Co-ords &amp; Anarkalis</p>
                        <p>✨ 100% Breathable Pure Cotton | Made with Love in Jaipur</p>
                        <p>🚚 Free Express Delivery across India on orders above ₹999</p>
                        <p class="pt-1">
                            <a href="{{ url('/') }}" class="font-semibold text-maroon-800 hover:underline flex items-center justify-center sm:justify-start gap-1">
                                <span>🔗</span>
                                <span>dhansree.com</span>
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Story Highlights Bar (Classic Instagram Circles) -->
            <div class="mt-8 pt-6 border-t border-light-gold/40 overflow-x-auto scrollbar-none flex items-center justify-start sm:justify-center gap-5 sm:gap-8 pb-2">
                <div class="flex flex-col items-center gap-1.5 shrink-0 group cursor-pointer" @click="activeTab = 'all'">
                    <div class="size-16 sm:size-18 rounded-full p-0.5 bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] group-hover:scale-105 transition-transform">
                        <div class="size-full rounded-full bg-white border border-light-gold flex items-center justify-center text-xl sm:text-2xl">
                            🌸
                        </div>
                    </div>
                    <span class="text-[11px] font-medium text-rich-black group-hover:text-maroon-800">Festive '26</span>
                </div>

                <div class="flex flex-col items-center gap-1.5 shrink-0 group cursor-pointer" @click="activeTab = 'reels'">
                    <div class="size-16 sm:size-18 rounded-full p-0.5 bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] group-hover:scale-105 transition-transform">
                        <div class="size-full rounded-full bg-white border border-light-gold flex items-center justify-center text-xl sm:text-2xl">
                            👗
                        </div>
                    </div>
                    <span class="text-[11px] font-medium text-rich-black group-hover:text-maroon-800">Kurta Sets</span>
                </div>

                <div class="flex flex-col items-center gap-1.5 shrink-0 group cursor-pointer" @click="activeTab = 'reels'">
                    <div class="size-16 sm:size-18 rounded-full p-0.5 bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] group-hover:scale-105 transition-transform">
                        <div class="size-full rounded-full bg-white border border-light-gold flex items-center justify-center text-xl sm:text-2xl">
                            ✨
                        </div>
                    </div>
                    <span class="text-[11px] font-medium text-rich-black group-hover:text-maroon-800">Co-Ords</span>
                </div>

                <div class="flex flex-col items-center gap-1.5 shrink-0 group cursor-pointer" @click="activeTab = 'shop'">
                    <div class="size-16 sm:size-18 rounded-full p-0.5 bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] group-hover:scale-105 transition-transform">
                        <div class="size-full rounded-full bg-white border border-light-gold flex items-center justify-center text-xl sm:text-2xl">
                            🛍️
                        </div>
                    </div>
                    <span class="text-[11px] font-medium text-rich-black group-hover:text-maroon-800">Shop Look</span>
                </div>

                <div class="flex flex-col items-center gap-1.5 shrink-0 group cursor-pointer" @click="activeTab = 'all'">
                    <div class="size-16 sm:size-18 rounded-full p-0.5 bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] group-hover:scale-105 transition-transform">
                        <div class="size-full rounded-full bg-white border border-light-gold flex items-center justify-center text-xl sm:text-2xl">
                            🧵
                        </div>
                    </div>
                    <span class="text-[11px] font-medium text-rich-black group-hover:text-maroon-800">Artisans</span>
                </div>

                <div class="flex flex-col items-center gap-1.5 shrink-0 group cursor-pointer" @click="activeTab = 'all'">
                    <div class="size-16 sm:size-18 rounded-full p-0.5 bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] group-hover:scale-105 transition-transform">
                        <div class="size-full rounded-full bg-white border border-light-gold flex items-center justify-center text-xl sm:text-2xl">
                            ⚡
                        </div>
                    </div>
                    <span class="text-[11px] font-medium text-rich-black group-hover:text-maroon-800">Smart Living</span>
                </div>
            </div>
        </header>

        <!-- ─── Instagram Navigation Tabs ───────────────────────────────────── -->
        <div class="flex items-center justify-center gap-8 sm:gap-16 border-b border-light-gold/60">
            <button type="button" @click="activeTab = 'all'"
                    class="py-3 sm:py-4 text-xs font-bold uppercase tracking-widest flex items-center gap-2 border-b-2 transition-all cursor-pointer"
                    :class="activeTab === 'all' ? 'border-maroon-800 text-maroon-800' : 'border-transparent text-rich-black/50 hover:text-rich-black'">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="2" y="2" width="8" height="8" rx="1.5"></rect>
                    <rect x="14" y="2" width="8" height="8" rx="1.5"></rect>
                    <rect x="2" y="14" width="8" height="8" rx="1.5"></rect>
                    <rect x="14" y="14" width="8" height="8" rx="1.5"></rect>
                </svg>
                <span>Posts ({{ $posts->count() }})</span>
            </button>

            <button type="button" @click="activeTab = 'reels'"
                    class="py-3 sm:py-4 text-xs font-bold uppercase tracking-widest flex items-center gap-2 border-b-2 transition-all cursor-pointer"
                    :class="activeTab === 'reels' ? 'border-maroon-800 text-maroon-800' : 'border-transparent text-rich-black/50 hover:text-rich-black'">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span>Reels ({{ $reels->count() }})</span>
            </button>

            <button type="button" @click="activeTab = 'shop'"
                    class="py-3 sm:py-4 text-xs font-bold uppercase tracking-widest flex items-center gap-2 border-b-2 transition-all cursor-pointer"
                    :class="activeTab === 'shop' ? 'border-maroon-800 text-maroon-800' : 'border-transparent text-rich-black/50 hover:text-rich-black'">
                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <span>Shop The Look ({{ $shopTheLook->count() }})</span>
            </button>
        </div>

        <!-- ─── 3-Column Instagram Content Grid ─────────────────────────────── -->
        <div class="grid grid-cols-3 gap-1 sm:gap-4 md:gap-6 py-6">
            <template x-for="(item, idx) in items" :key="item.id">
                <div x-show="activeTab === 'all' || (activeTab === 'reels' && item.type === 'reel') || (activeTab === 'shop' && item.tagged_product)"
                     @click="openItem(idx)"
                     class="group relative aspect-square sm:aspect-[4/5] rounded-none sm:rounded-xl overflow-hidden bg-warm-beige cursor-pointer border border-light-gold/40 shadow-2xs hover:shadow-lg transition-all">
                    
                    <!-- Cover Media Image -->
                    <img :src="item.media_url" :alt="item.caption"
                         class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                         loading="lazy">

                    <!-- Top Right Type Icon Badge (Reel clapperboard or carousel) -->
                    <div class="absolute top-2 right-2 z-10">
                        <template x-if="item.type === 'reel'">
                            <span class="size-6 sm:size-7 rounded-full bg-black/60 backdrop-blur-xs text-white flex items-center justify-center shadow-xs">
                                <svg class="size-3.5 fill-white" viewBox="0 0 24 24">
                                    <path d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </span>
                        </template>
                    </div>

                    <!-- Bottom Left Tagged Product Badge -->
                    <template x-if="item.tagged_product">
                        <div class="absolute bottom-2 left-2 z-10">
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-white/95 backdrop-blur-xs text-maroon-800 text-[10px] font-bold shadow-sm border border-light-gold">
                                <span>🛍️</span>
                                <span class="hidden sm:inline" x-text="item.tagged_product.price"></span>
                            </span>
                        </div>
                    </template>

                    <!-- Bottom View Count for Reels -->
                    <template x-if="item.type === 'reel'">
                        <div class="absolute bottom-2 right-2 z-10 text-[10px] font-bold text-white drop-shadow-md flex items-center gap-1">
                            <span>▶</span>
                            <span x-text="item.views"></span>
                        </div>
                    </template>

                    <!-- Instagram Hover Scrim (Likes ❤️ & Comments 💬 overlay) -->
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col items-center justify-center gap-2 text-white z-20">
                        <div class="flex items-center gap-4 text-xs sm:text-sm font-bold">
                            <div class="flex items-center gap-1">
                                <svg class="size-4 fill-white" viewBox="0 0 24 24">
                                    <path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/>
                                </svg>
                                <span x-text="item.formatted_likes"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg class="size-4 fill-white" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0 1 12 2.25c2.43 0 4.817.177 7.152.52 1.84.269 3.22 1.855 3.22 3.71v7.652c0 1.854-1.38 3.44-3.22 3.71a48.913 48.913 0 0 1-3.666.425v2.857a1.5 1.5 0 0 1-2.46 1.15l-3.32-2.766a49.256 49.256 0 0 1-2.026-.145c-2.43-.27-4.25-2.05-4.25-4.5V6.48c0-1.855 1.38-3.441 3.22-3.71Z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="item.comments_count"></span>
                            </div>
                        </div>

                        <template x-if="item.tagged_product">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-white text-maroon-800 text-[10px] font-bold shadow-md">
                                <span>🛍️</span>
                                <span>Shop This Look</span>
                            </span>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- ─── Bottom Community Callout Banner ─────────────────────────────── -->
        <div class="mt-8 rounded-2xl bg-gradient-to-r from-warm-beige via-ivory to-warm-beige p-6 sm:p-8 border border-light-gold text-center space-y-3 shadow-xs">
            <span class="text-xs uppercase tracking-widest text-gold-600 font-bold">Join Our Family • #DhansreeStyle</span>
            <h3 class="font-serif text-2xl sm:text-3xl text-maroon-800 font-normal">Want to Be Featured on Our Feed?</h3>
            <p class="text-xs sm:text-sm text-rich-black/70 max-w-xl mx-auto leading-relaxed">
                Post your Dhansree outfit reel or photo, tag <strong>@{{ $instagramHandle }}</strong> and use <strong>#DhansreeStyle</strong> to get featured on our official Instagram page &amp; website.
            </p>
            <div class="pt-2">
                <a href="{{ $instagramProfileUrl }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-xs font-bold uppercase tracking-wider text-white bg-maroon-800 hover:bg-maroon-900 transition-all shadow-md">
                    <span>Follow Us On Instagram ➔</span>
                </a>
            </div>
        </div>

    </div>

    <!-- ─── Full-Fidelity Interactive Instagram Reel / Post Modal ───────────── -->
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-md flex items-center justify-center p-2 sm:p-4 md:p-6"
         @click.self="closeModal()">
        
        <!-- Close Button -->
        <button type="button" @click="closeModal()"
                class="absolute top-4 right-4 z-50 text-white/80 hover:text-white p-2 rounded-full hover:bg-white/10 transition-colors cursor-pointer"
                title="Close (Esc)">
            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        <!-- Previous Navigation Arrow -->
        <button type="button" @click="prev()"
                class="hidden sm:flex absolute left-4 top-1/2 -translate-y-1/2 z-50 size-11 rounded-full bg-white/20 hover:bg-white/40 text-white items-center justify-center transition-all cursor-pointer backdrop-blur-xs"
                title="Previous Reel (Left Arrow)">
            ‹
        </button>

        <!-- Next Navigation Arrow -->
        <button type="button" @click="next()"
                class="hidden sm:flex absolute right-4 top-1/2 -translate-y-1/2 z-50 size-11 rounded-full bg-white/20 hover:bg-white/40 text-white items-center justify-center transition-all cursor-pointer backdrop-blur-xs"
                title="Next Reel (Right Arrow)">
            ›
        </button>

        <!-- Modal Dialog Box -->
        <div x-show="currentItem"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-4xl max-h-[92vh] bg-white rounded-2xl overflow-hidden shadow-2xl flex flex-col md:flex-row border border-white/20 relative"
             @click.stop>
            
            <!-- Left Side: Visual Media / Reel Player -->
            <div class="w-full md:w-3/5 bg-black flex items-center justify-center relative overflow-hidden aspect-[4/5] sm:aspect-square md:aspect-auto">
                <img :src="currentItem ? currentItem.media_url : ''"
                     :alt="currentItem ? currentItem.caption : ''"
                     class="max-h-[85vh] w-full object-contain">

                <!-- Reel watermark badge -->
                <div class="absolute top-4 left-4 flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/60 backdrop-blur-xs text-white text-[11px] font-semibold">
                    <span class="text-rose-400">🎬</span>
                    <span x-text="currentItem ? (currentItem.type === 'reel' ? 'Instagram Reel' : 'Instagram Post') : ''"></span>
                </div>

                <!-- Bottom audio badge -->
                <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-white/80 text-[11px] px-3 py-1 rounded-lg bg-black/40 backdrop-blur-xs">
                    <div class="flex items-center gap-1.5 truncate">
                        <span>♫</span>
                        <span class="truncate">dhansreetrends • Original audio</span>
                    </div>
                    <span class="text-[10px] text-white/60 shrink-0" x-text="currentItem ? currentItem.views + ' views' : ''"></span>
                </div>
            </div>

            <!-- Right Side: Instagram Profile & Caption & Shop Box -->
            <div class="w-full md:w-2/5 flex flex-col justify-between bg-white text-slate-900 border-t md:border-t-0 md:border-l border-slate-200 overflow-y-auto">
                
                <!-- Header: Profile Avatar & Name -->
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-full p-0.5 bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888]">
                            <div class="size-full rounded-full bg-white flex items-center justify-center font-serif font-bold text-xs text-maroon-800">
                                DS
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-xs text-slate-900">dhansreetrends</span>
                                <svg class="size-3 text-[#0095f6]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                            <p class="text-[10px] text-slate-400">Jaipur, Rajasthan</p>
                        </div>
                    </div>

                    <a :href="currentItem ? currentItem.instagram_url : '#'" target="_blank"
                       class="text-xs font-bold text-[#0095f6] hover:text-[#1877f2]">
                        Follow
                    </a>
                </div>

                <!-- Scrollable Body: Caption & Tagged Product -->
                <div class="p-5 flex-1 space-y-4 overflow-y-auto">
                    
                    <!-- Caption -->
                    <div class="text-xs leading-relaxed text-slate-700 whitespace-pre-line font-normal">
                        <strong class="text-slate-900 font-bold">dhansreetrends</strong>
                        <span x-text="currentItem ? currentItem.caption : ''"></span>
                    </div>

                    <!-- Tagged Product E-Commerce Card (Shop The Look) -->
                    <template x-if="currentItem && currentItem.tagged_product">
                        <div class="rounded-xl border border-light-gold bg-warm-beige/40 p-3 space-y-2 shadow-2xs">
                            <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-maroon-800">
                                <span class="flex items-center gap-1">
                                    <span>🛍️</span>
                                    <span>Featured In This Reel</span>
                                </span>
                                <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">In Stock</span>
                            </div>

                            <a :href="currentItem.tagged_product.url" class="flex items-center gap-3 group">
                                <img :src="currentItem.tagged_product.image" alt="" class="size-14 rounded-lg object-cover border border-light-gold shrink-0">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-xs font-semibold text-slate-900 truncate group-hover:text-maroon-800 transition-colors"
                                        x-text="currentItem.tagged_product.name"></h4>
                                    <div class="flex items-baseline gap-1.5 mt-0.5">
                                        <span class="text-xs font-bold text-maroon-800" x-text="currentItem.tagged_product.price"></span>
                                        <template x-if="currentItem.tagged_product.mrp">
                                            <del class="text-[10px] text-slate-400" x-text="currentItem.tagged_product.mrp"></del>
                                        </template>
                                    </div>
                                </div>
                            </a>

                            <div class="pt-1">
                                <a :href="currentItem.tagged_product.url"
                                   class="w-full py-2 px-3 rounded-lg bg-maroon-800 hover:bg-maroon-900 text-white text-[11px] font-bold text-center block transition-colors shadow-xs">
                                    Buy This Look ➔
                                </a>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer Engagement Bar -->
                <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50 shrink-0 space-y-2">
                    
                    <!-- Action Icons Row -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <!-- Interactive Heart Button -->
                            <button type="button" @click="toggleLike(currentItem)"
                                    class="p-1 rounded-full transition-transform hover:scale-110 cursor-pointer"
                                    :title="currentItem && currentItem.liked ? 'Unlike' : 'Like'">
                                <svg class="size-6 transition-colors"
                                     :class="currentItem && currentItem.liked ? 'fill-rose-600 text-rose-600' : 'text-slate-800 hover:text-rose-600'"
                                     fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                                </svg>
                            </button>

                            <!-- Comment Bubble Icon -->
                            <button type="button" class="p-1 text-slate-800 hover:text-slate-600">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" />
                                </svg>
                            </button>

                            <!-- Share Icon -->
                            <button type="button" @click="copyPostLink(currentItem)" class="p-1 text-slate-800 hover:text-slate-600 cursor-pointer" title="Share Post">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                </svg>
                            </button>
                        </div>

                        <!-- Open on Instagram button -->
                        <a :href="currentItem ? currentItem.instagram_url : '#'" target="_blank"
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 hover:text-rose-700">
                            <span>Open in Instagram</span>
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                        </a>
                    </div>

                    <!-- Likes count -->
                    <div class="text-xs font-bold text-slate-900">
                        <span x-text="currentItem ? currentItem.formatted_likes : '0'"></span>
                        <span>likes</span>
                    </div>

                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Recently Added</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
