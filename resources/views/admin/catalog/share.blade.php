@extends('layouts.admin')

@section('title', 'Share Catalog & WhatsApp Hub')

@section('content')
<div class="space-y-6" x-data="catalogShareHub()">
    <!-- Toast Notification -->
    <div x-show="toast.show" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
         x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
         x-transition:leave-end="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
         class="fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 max-w-md">
        <div class="size-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>
        <div>
            <p class="text-xs font-semibold text-emerald-400" x-text="toast.title"></p>
            <p class="text-xs text-slate-300" x-text="toast.message"></p>
        </div>
        <button type="button" @click="toast.show = false" class="text-slate-400 hover:text-white ml-2 text-lg leading-none">&times;</button>
    </div>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <svg class="size-3 text-emerald-600 fill-current" viewBox="0 0 24 24">
                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.771.821 2.791.821 3.181 0 5.767-2.587 5.768-5.766.001-3.182-2.585-5.767-5.768-5.767zm0 10.427c-.89 0-1.666-.25-2.422-.727l-.173-.109-1.58.414.422-1.541-.118-.188c-.521-.828-.795-1.58-.795-2.51 0-2.614 2.127-4.741 4.742-4.741 2.614 0 4.741 2.127 4.741 4.741 0 2.615-2.127 4.741-4.741 4.741zm6.969-4.661c-.04-.066-.145-.106-.304-.185-.158-.079-.937-.462-1.082-.515-.145-.053-.251-.079-.356.079-.106.158-.409.515-.502.621-.092.106-.185.119-.343.04-.158-.079-.669-.247-1.274-.786-.471-.42-.789-.939-.882-1.097-.092-.158-.01-.244.069-.323.071-.071.158-.185.238-.277.079-.092.106-.158.158-.264.053-.106.026-.198-.013-.277-.04-.079-.356-.859-.488-1.176-.129-.308-.26-.266-.356-.271-.092-.005-.198-.006-.304-.006s-.277.04-.422.198c-.145.158-.555.542-.555 1.321 0 .779.568 1.532.647 1.638.079.106 1.118 1.708 2.709 2.395.378.163.674.261.904.334.38.121.726.104 1.001.063.305-.046.937-.383 1.069-.753.132-.37.132-.687.092-.753z"/>
                    </svg>
                    WhatsApp &amp; Direct Share
                </span>
                <span class="text-xs text-slate-500">• In-Person Meetings &amp; Customer Outreach</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                Catalog &amp; Product WhatsApp Share Hub
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 mt-1 max-w-2xl">
                Generate personalized ready-to-send WhatsApp messages with direct product &amp; catalog links for one-click purchasing, customer outreach, and in-person meetings.
            </p>
        </div>

        <!-- Quick Top Action Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" 
                    @click="copyFullMessage()" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs tracking-wider uppercase shadow-xs transition-colors">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                </svg>
                <span>Copy Draft &amp; Link</span>
            </button>

            <a :href="whatsappUrl" 
               target="_blank" 
               rel="noopener noreferrer"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs tracking-wider uppercase shadow-xs transition-colors">
                <svg class="size-4 fill-current" viewBox="0 0 24 24">
                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.771.821 2.791.821 3.181 0 5.767-2.587 5.768-5.766.001-3.182-2.585-5.767-5.768-5.767zm0 10.427c-.89 0-1.666-.25-2.422-.727l-.173-.109-1.58.414.422-1.541-.118-.188c-.521-.828-.795-1.58-.795-2.51 0-2.614 2.127-4.741 4.742-4.741 2.614 0 4.741 2.127 4.741 4.741 0 2.615-2.127 4.741-4.741 4.741zm6.969-4.661c-.04-.066-.145-.106-.304-.185-.158-.079-.937-.462-1.082-.515-.145-.053-.251-.079-.356.079-.106.158-.409.515-.502.621-.092.106-.185.119-.343.04-.158-.079-.669-.247-1.274-.786-.471-.42-.789-.939-.882-1.097-.092-.158-.01-.244.069-.323.071-.071.158-.185.238-.277.079-.092.106-.158.158-.264.053-.106.026-.198-.013-.277-.04-.079-.356-.859-.488-1.176-.129-.308-.26-.266-.356-.271-.092-.005-.198-.006-.304-.006s-.277.04-.422.198c-.145.158-.555.542-.555 1.321 0 .779.568 1.532.647 1.638.079.106 1.118 1.708 2.709 2.395.378.163.674.261.904.334.38.121.726.104 1.001.063.305-.046.937-.383 1.069-.753.132-.37.132-.687.092-.753z"/>
                </svg>
                <span>Send via WhatsApp</span>
            </a>
        </div>
    </div>

    <!-- Main Grid: 2 Columns on desktop, 1 on mobile -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT: Form Controls & Builder (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Card 1: Customer Info & Meeting Context -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="size-7 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold text-xs">1</span>
                        <h2 class="text-base font-bold text-slate-900">Customer Details (Optional)</h2>
                    </div>
                    <span class="text-xs text-slate-600">Personalize with 1-click</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Customer Name
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   x-model="customerName" 
                                   placeholder="e.g. Pooja Ji, Ananya, Ritu" 
                                   class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 pl-9 py-2">
                            <svg class="size-4 text-slate-600 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1">If blank, defaults to a polite warm greeting.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            Customer WhatsApp Number
                        </label>
                        <div class="flex rounded-lg shadow-2xs">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-slate-300 bg-slate-50 text-slate-500 text-xs font-semibold">
                                +91
                            </span>
                            <input type="tel" 
                                   x-model="customerPhone" 
                                   placeholder="10-digit mobile number" 
                                   class="w-full text-sm rounded-none rounded-r-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                        </div>
                        <p class="text-[11px] text-slate-600 mt-1">If provided, WhatsApp opens direct chat with this customer.</p>
                    </div>
                </div>
            </div>

            <!-- Card 2: Catalog Target & Link Selection -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="size-7 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold text-xs">2</span>
                        <h2 class="text-base font-bold text-slate-900">Select Catalog Destination</h2>
                    </div>
                    <span class="text-xs font-medium text-emerald-600 flex items-center gap-1">
                        <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Active Links
                    </span>
                </div>

                <!-- Destination Type Radios / Tabs -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <button type="button" 
                            @click="setCatalogType('collection')" 
                            :class="catalogType === 'collection' ? 'bg-amber-50 border-amber-500 text-amber-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                            class="p-2.5 rounded-lg border text-xs text-center transition-all flex flex-col items-center gap-1">
                        <span class="text-base">👗</span>
                        <span>Collection</span>
                    </button>

                    <button type="button" 
                            @click="setCatalogType('category')" 
                            :class="catalogType === 'category' ? 'bg-amber-50 border-amber-500 text-amber-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                            class="p-2.5 rounded-lg border text-xs text-center transition-all flex flex-col items-center gap-1">
                        <span class="text-base">👘</span>
                        <span>Category</span>
                    </button>

                    <button type="button" 
                            @click="setCatalogType('product')" 
                            :class="catalogType === 'product' ? 'bg-amber-50 border-amber-500 text-amber-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                            class="p-2.5 rounded-lg border text-xs text-center transition-all flex flex-col items-center gap-1">
                        <span class="text-base">🏷️</span>
                        <span>Specific Product</span>
                    </button>

                    <button type="button" 
                            @click="setCatalogType('special')" 
                            :class="catalogType === 'special' ? 'bg-amber-50 border-amber-500 text-amber-900 font-bold shadow-2xs' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                            class="p-2.5 rounded-lg border text-xs text-center transition-all flex flex-col items-center gap-1">
                        <span class="text-base">🎥</span>
                        <span>Reels / Store</span>
                    </button>
                </div>

                <!-- Destination Details -->
                <div class="space-y-3 pt-2">
                    <!-- Collection Selector -->
                    <div x-show="catalogType === 'collection'" class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">Choose Collection</label>
                        <select x-model="selectedCollectionSlug" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                            @foreach($collections as $col)
                                <option value="{{ $col->slug }}" {{ $col->slug === 'women-fashion' ? 'selected' : '' }}>
                                    {{ $col->name }} ({{ $col->heading ?? 'Ethnic Wear Edit' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Selector -->
                    <div x-show="catalogType === 'category'" class="space-y-2" x-cloak>
                        <label class="block text-xs font-semibold text-slate-700">Choose Category</label>
                        <select x-model="selectedCategorySlug" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->slug }}">
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Product Selector -->
                    <div x-show="catalogType === 'product'" class="space-y-2" x-cloak>
                        <label class="block text-xs font-semibold text-slate-700">Choose Specific Product</label>
                        <select x-model="selectedProductSlug" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                            @foreach($products as $prod)
                                <option value="{{ $prod->slug }}">
                                    {{ $prod->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Special Pages Selector -->
                    <div x-show="catalogType === 'special'" class="space-y-2" x-cloak>
                        <label class="block text-xs font-semibold text-slate-700">Choose Special Hub</label>
                        <select x-model="selectedSpecialPage" class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                            <option value="storefront">Dhansree Main Storefront (dhansree.com)</option>
                            <option value="instagram">Instagram Video Reels &amp; Lookbook (/instagram)</option>
                            <option value="custom">Custom URL / PDF Brochure Link</option>
                        </select>
                    </div>

                    <!-- Custom URL field if custom chosen -->
                    <div x-show="catalogType === 'special' && selectedSpecialPage === 'custom'" class="pt-2" x-cloak>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Enter Custom URL or PDF Drive Link</label>
                        <input type="url" 
                               x-model="customUrl" 
                               placeholder="https://dhansree.com/custom-link or https://drive.google.com/..." 
                               class="w-full text-sm rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                    </div>

                    <!-- Active Catalog Link Display & Quick Copy -->
                    <div class="mt-3 p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-semibold text-slate-600 uppercase tracking-wider">Generated Catalog Link:</p>
                            <a :href="activeCatalogLink" target="_blank" class="text-xs font-mono font-medium text-amber-700 hover:underline truncate block" x-text="activeCatalogLink"></a>
                        </div>
                        <button type="button" 
                                @click="copyCatalogLinkOnly()" 
                                class="px-2.5 py-1.5 rounded-md bg-white border border-slate-300 hover:bg-slate-100 text-xs font-semibold text-slate-700 shrink-0 flex items-center gap-1.5 transition-colors shadow-2xs">
                            <svg class="size-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                            </svg>
                            <span>Copy Link</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card 3: Templates & Message Customization -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="size-7 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold text-xs">3</span>
                        <h2 class="text-base font-bold text-slate-900">Message Style &amp; Purpose</h2>
                    </div>
                    <span class="text-xs text-slate-600">Tailored for customer interaction</span>
                </div>

                <!-- Template Choices -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Select Draft Message Template</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Direct Buy & Order Online (Universal) -->
                        <div @click="selectTemplate('direct_buy')"
                             :class="template === 'direct_buy' ? 'border-emerald-500 bg-emerald-50/70 ring-1 ring-emerald-500 shadow-2xs' : 'border-slate-200 bg-white hover:bg-slate-50'"
                             class="p-3.5 rounded-lg border cursor-pointer transition-all sm:col-span-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">🛒</span>
                                    <span class="text-xs font-bold text-slate-900">Direct Buy &amp; Order Online</span>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase tracking-wider">
                                    All Products &amp; Essentials
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1">Direct product link with clear 1-minute steps for the customer to tap "Buy Now", enter their address, and complete payment or COD.</p>
                        </div>

                        <div @click="selectTemplate('in_person')"
                             :class="template === 'in_person' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                             class="p-3 rounded-lg border cursor-pointer transition-all">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🤝</span>
                                <span class="text-xs font-bold text-slate-900">In-Person Meeting</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1">Warm follow-up mentioning you met today, inviting them to explore the catalog.</p>
                        </div>

                        <div @click="selectTemplate('quick_link')"
                             :class="template === 'quick_link' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                             class="p-3 rounded-lg border cursor-pointer transition-all">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">⚡</span>
                                <span class="text-xs font-bold text-slate-900">Quick &amp; Concise</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1">Short 2-line direct link message without lengthy text.</p>
                        </div>

                        <div @click="selectTemplate('festive_kurti')"
                             :class="template === 'festive_kurti' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                             class="p-3 rounded-lg border cursor-pointer transition-all">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🌸</span>
                                <span class="text-xs font-bold text-slate-900">Festive &amp; Kurti Edit</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1">Focuses on handcrafted Jaipur Kurta sets, Anarkalis &amp; Co-ords.</p>
                        </div>

                        <div @click="selectTemplate('wholesale')"
                             :class="template === 'wholesale' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500' : 'border-slate-200 bg-white hover:bg-slate-50'"
                             class="p-3 rounded-lg border cursor-pointer transition-all">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">💼</span>
                                <span class="text-xs font-bold text-slate-900">Boutique &amp; Wholesale</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1">For retailers, boutique owners or bulk purchasing clients.</p>
                        </div>
                    </div>
                </div>

                <!-- Add-on Offers & Perks -->
                <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Coupon code inclusion -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Include Exclusive Coupon Code
                        </label>
                        <select x-model="selectedCouponCode" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                            <option value="">No coupon (regular catalog)</option>
                            @foreach($coupons as $cpn)
                                <option value="{{ $cpn->code }}">
                                    {{ $cpn->code }} ({{ $cpn->description ?? 'Special Discount' }})
                                </option>
                            @endforeach
                            <option value="CUSTOM">Custom Promo Code...</option>
                        </select>
                        <div x-show="selectedCouponCode === 'CUSTOM'" class="mt-2" x-cloak>
                            <input type="text" 
                                   x-model="customCouponCode" 
                                   placeholder="Enter promo code (e.g. VIP10)" 
                                   class="w-full text-xs uppercase rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-1.5">
                        </div>
                    </div>

                    <!-- Highlight Badges Toggle -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Value Highlights
                        </label>
                        <label class="flex items-center gap-2.5 p-2 rounded-lg border border-slate-200 bg-slate-50 cursor-pointer">
                            <input type="checkbox" x-model="includePerks" class="rounded text-amber-600 focus:ring-amber-500 size-4">
                            <span class="text-xs text-slate-700" x-text="template === 'festive_kurti' ? 'Mention Free Delivery & Jaipur Artisans' : 'Mention Free Delivery, COD & Quality Guarantees'"></span>
                        </label>
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT: Live WhatsApp Preview & Action Hub (5 cols) -->
        <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-6">
            
            <!-- WhatsApp Preview Card -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm overflow-hidden">
                <!-- WhatsApp Mobile Style Header -->
                <div class="bg-emerald-700 text-white px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="size-9 rounded-full bg-emerald-800 border border-emerald-500/40 flex items-center justify-center font-bold text-xs uppercase shadow-xs">
                            <span x-text="avatarInitials">DS</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold leading-tight" x-text="customerName || 'Customer WhatsApp'"></p>
                            <p class="text-[11px] text-emerald-200 font-mono" x-text="customerPhone ? ('+91 ' + customerPhone) : 'Direct Message Preview'"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-emerald-100">
                        <button type="button" 
                                @click="resetToTemplate()" 
                                title="Reset to original template"
                                class="text-[11px] font-medium bg-emerald-800/80 hover:bg-emerald-800 px-2 py-1 rounded border border-emerald-600/50 transition-colors">
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Chat Bubble Container (WhatsApp light green doodle background look) -->
                <div class="p-4 bg-[#ece5dd] min-h-[280px] flex flex-col justify-end space-y-3">
                    
                    <!-- WhatsApp Sent Message Bubble -->
                    <div class="bg-white rounded-lg rounded-tr-none p-3 shadow-xs border border-slate-200/60 max-w-full text-slate-800 space-y-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider flex items-center gap-1">
                                <svg class="size-3 fill-current" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.771.821 2.791.821 3.181 0 5.767-2.587 5.768-5.766.001-3.182-2.585-5.767-5.768-5.767z"/></svg>
                                Ready to Send Draft
                            </span>
                            <span class="text-[10px] text-slate-500 font-mono" x-text="currentTime">Now</span>
                        </div>

                        <!-- Editable Textarea so admin can tweak words directly -->
                        <textarea x-model="customMessage" 
                                  @input="isManuallyEdited = true"
                                  rows="11" 
                                  class="w-full text-xs font-sans text-slate-800 bg-transparent border-0 focus:ring-1 focus:ring-emerald-500 rounded p-1 leading-relaxed resize-y"
                                  placeholder="Type or customize your message here..."></textarea>

                        <div class="pt-1 flex items-center justify-between text-[11px] text-slate-600 border-t border-slate-100">
                            <span x-text="customMessage.length + ' characters'"></span>
                            <span class="flex items-center gap-1 text-emerald-600 font-medium">
                                Editable
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                            </span>
                        </div>
                    </div>

                </div>

                <!-- Action Buttons Box -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-2.5">
                    <!-- WhatsApp Primary Action -->
                    <a :href="whatsappUrl" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="w-full inline-flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-sm tracking-wide shadow-md hover:shadow-lg transition-all text-center">
                        <svg class="size-5 fill-current" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.771.821 2.791.821 3.181 0 5.767-2.587 5.768-5.766.001-3.182-2.585-5.767-5.768-5.767zm0 10.427c-.89 0-1.666-.25-2.422-.727l-.173-.109-1.58.414.422-1.541-.118-.188c-.521-.828-.795-1.58-.795-2.51 0-2.614 2.127-4.741 4.742-4.741 2.614 0 4.741 2.127 4.741 4.741 0 2.615-2.127 4.741-4.741 4.741zm6.969-4.661c-.04-.066-.145-.106-.304-.185-.158-.079-.937-.462-1.082-.515-.145-.053-.251-.079-.356.079-.106.158-.409.515-.502.621-.092.106-.185.119-.343.04-.158-.079-.669-.247-1.274-.786-.471-.42-.789-.939-.882-1.097-.092-.158-.01-.244.069-.323.071-.071.158-.185.238-.277.079-.092.106-.158.158-.264.053-.106.026-.198-.013-.277-.04-.079-.356-.859-.488-1.176-.129-.308-.26-.266-.356-.271-.092-.005-.198-.006-.304-.006s-.277.04-.422.198c-.145.158-.555.542-.555 1.321 0 .779.568 1.532.647 1.638.079.106 1.118 1.708 2.709 2.395.378.163.674.261.904.334.38.121.726.104 1.001.063.305-.046.937-.383 1.069-.753.132-.37.132-.687.092-.753z"/>
                        </svg>
                        <span>Send via WhatsApp</span>
                    </a>

                    <!-- Copy Message Button -->
                    <button type="button" 
                            @click="copyFullMessage()" 
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 active:bg-black text-white font-semibold text-xs tracking-wider uppercase transition-colors shadow-xs">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                        </svg>
                        <span>Copy Draft Message &amp; Link</span>
                    </button>

                    <!-- In-Person QR Code Toggle -->
                    <button type="button" 
                            @click="showQrModal = true" 
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors">
                        <svg class="size-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
                        </svg>
                        <span>Show QR Code for Customer to Scan</span>
                    </button>
                </div>
            </div>

            <!-- In-Person Meeting Best Practice Card -->
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-slate-800 space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-base">💡</span>
                    <h3 class="text-xs font-bold text-amber-950 uppercase tracking-wider">In-Person Meeting Pro-Tip</h3>
                </div>
                <p class="text-xs text-amber-900 leading-relaxed">
                    When meeting a customer face-to-face, ask for their WhatsApp number to send them this ready catalog link right away. If they prefer to scan, tap <strong>"Show QR Code"</strong> and they can open the collection on their mobile browser instantly!
                </p>
            </div>

        </div>

    </div>

    <!-- QR Code Modal (for In-Person Scanning) -->
    <div x-show="showQrModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="showQrModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showQrModal = false"
                 class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity" 
                 aria-hidden="true"></div>

            <!-- Modal Panel -->
            <div x-show="showQrModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-middle bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full p-6 text-center space-y-4">
                
                <div class="size-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                    </svg>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-900">Scan to Open Catalog</h3>
                    <p class="text-xs text-slate-500 mt-1">Let the customer scan this with their smartphone camera</p>
                </div>

                <!-- QR Image Generated on the fly -->
                <div class="p-4 bg-white border border-slate-200 rounded-xl inline-block shadow-inner mx-auto">
                    <img :src="qrCodeUrl" 
                         alt="Catalog QR Code" 
                         class="size-52 mx-auto rounded-lg">
                </div>

                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200">
                    <p class="text-[11px] font-mono text-slate-600 truncate" x-text="activeCatalogLink"></p>
                </div>

                <div class="flex items-center justify-center gap-3 pt-2">
                    <button type="button" 
                            @click="copyCatalogLinkOnly()" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-semibold transition-colors">
                        Copy Link
                    </button>
                    <button type="button" 
                            @click="showQrModal = false" 
                            class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function catalogShareHub() {
    return {
        // State
        storeBaseUrl: @json($storeBaseUrl),
        products: @json($products->map(fn($p) => ['slug' => $p->slug, 'name' => $p->name])),
        customerName: '',
        customerPhone: '',
        catalogType: 'collection',
        selectedCollectionSlug: '{{ $collections->firstWhere("slug", "women-fashion") ? "women-fashion" : ($collections->first()->slug ?? "") }}',
        selectedCategorySlug: '{{ $categories->firstWhere("slug", "kurta-set") ? "kurta-set" : ($categories->first()->slug ?? "") }}',
        selectedProductSlug: '{{ $products->first()->slug ?? "" }}',
        selectedSpecialPage: 'instagram',
        customUrl: '',
        template: 'in_person',
        selectedCouponCode: '',
        customCouponCode: '',
        includePerks: true,
        customMessage: '',
        isManuallyEdited: false,
        showQrModal: false,
        currentTime: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        toast: {
            show: false,
            title: '',
            message: ''
        },

        init() {
            this.generateMessage();
            this.$watch('customerName', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('catalogType', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('selectedCollectionSlug', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('selectedCategorySlug', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('selectedProductSlug', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('selectedSpecialPage', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('customUrl', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('template', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('selectedCouponCode', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('customCouponCode', () => { if (!this.isManuallyEdited) this.generateMessage(); });
            this.$watch('includePerks', () => { if (!this.isManuallyEdited) this.generateMessage(); });
        },

        setCatalogType(type) {
            this.catalogType = type;
            if (type === 'product') {
                if (this.template === 'festive_kurti' || this.template === 'wholesale' || this.template === 'in_person') {
                    this.template = 'direct_buy';
                }
            }
            if (!this.isManuallyEdited) this.generateMessage();
        },

        selectTemplate(type) {
            this.template = type;
            this.isManuallyEdited = false;
            this.generateMessage();
        },

        resetToTemplate() {
            this.isManuallyEdited = false;
            this.generateMessage();
            this.showToast('Reset to Template', 'Draft restored to template format.');
        },

        get avatarInitials() {
            if (!this.customerName.trim()) return 'DS';
            const parts = this.customerName.trim().split(' ');
            return parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0].substring(0, 2).toUpperCase();
        },

        get selectedProductName() {
            if (this.catalogType !== 'product') return '';
            const prod = this.products.find(p => p.slug === this.selectedProductSlug);
            return prod ? prod.name : '';
        },

        get activeCatalogLink() {
            const base = this.storeBaseUrl.replace(/\/+$/, '');
            if (this.catalogType === 'collection') {
                return `${base}/collections/${this.selectedCollectionSlug || 'women-fashion'}`;
            }
            if (this.catalogType === 'category') {
                return `${base}/${this.selectedCategorySlug || 'kurta-set'}`;
            }
            if (this.catalogType === 'product') {
                return `${base}/products/${this.selectedProductSlug || ''}`;
            }
            if (this.catalogType === 'special') {
                if (this.selectedSpecialPage === 'storefront') return base;
                if (this.selectedSpecialPage === 'instagram') return `${base}/instagram`;
                if (this.selectedSpecialPage === 'custom') return this.customUrl.trim() || base;
            }
            return `${base}/collections/women-fashion`;
        },

        get effectiveCouponCode() {
            if (this.selectedCouponCode === 'CUSTOM') {
                return this.customCouponCode.trim();
            }
            return this.selectedCouponCode;
        },

        get qrCodeUrl() {
            return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&margin=8&data=${encodeURIComponent(this.activeCatalogLink)}`;
        },

        get cleanCustomerPhone() {
            return this.customerPhone.replace(/[^0-9]/g, '');
        },

        get whatsappUrl() {
            const encodedText = encodeURIComponent(this.customMessage || '');
            const phone = this.cleanCustomerPhone;
            if (phone.length === 10) {
                return `https://wa.me/91${phone}?text=${encodedText}`;
            } else if (phone.length > 10) {
                return `https://wa.me/${phone}?text=${encodedText}`;
            }
            return `https://api.whatsapp.com/send?text=${encodedText}`;
        },

        generateMessage() {
            const name = this.customerName.trim();
            const greeting = name ? `Namaste ${name} ji! 🙏` : `Namaste! 🙏`;
            const helloGreeting = name ? `Hello ${name}! 🌸` : `Hello! 🌸`;
            const link = this.activeCatalogLink;
            const coupon = this.effectiveCouponCode 
                ? `\n🎁 *Special Discount for You:* Apply coupon code *${this.effectiveCouponCode}* at checkout for an instant discount!\n` 
                : '';
            
            const isEthnicFashion = (this.template === 'festive_kurti');

            const perksBlock = this.includePerks 
                ? (isEthnicFashion
                    ? `\n✨ *Why Dhansree Jaipur:*\n• 100% Pure Breathable Fabrics (Cotton, Rayon & Chanderi)\n• Handcrafted by Jaipur Artisans\n• Free & Fast Express Delivery across India\n• Easy Exchanges & Dedicated Sizing Support\n`
                    : `\n✨ *Why Shop With Dhansree:*\n• 100% Genuine Quality Assured\n• Cash on Delivery (COD) Available Across India\n• Free & Fast Express Shipping\n• Safe & 100% Secure Checkout\n`)
                : '';

            let msg = '';

            if (this.template === 'direct_buy') {
                const prodTitle = this.selectedProductName ? `*${this.selectedProductName}*\n\n` : '';
                msg = `${greeting}\n\nHere is the direct link to order your selected product online from *Dhansree*:\n\n🛒 ${prodTitle}👉 *Buy Now Link:*\n${link}\n\n*How to Order in 1 Minute:*\n1️⃣ Tap the link above\n2️⃣ Click on the *Buy Now* button\n3️⃣ Enter your delivery address & choose payment (UPI, Cards, NetBanking or Cash on Delivery)\n${coupon}${perksBlock}\nYour parcel will be safely packed & dispatched with live courier tracking updates on WhatsApp. Please let me know if you need any help with ordering!\n\nWarm regards,\n*Dhansree*`;
            } else if (this.template === 'in_person') {
                if (this.catalogType === 'product') {
                    const prodName = this.selectedProductName ? `*${this.selectedProductName}*` : 'the product we discussed';
                    msg = `${greeting}\n\nIt was truly wonderful meeting you today! As discussed, here is the direct link to view details and order ${prodName} online at *Dhansree*:\n\n🛒 *Product Link:*\n${link}\n\nYou can click the *Buy Now* button on the page to complete your order easily with UPI or Cash on Delivery.${coupon}${perksBlock}\nPlease feel free to reply right here on WhatsApp if you have any questions. Looking forward to staying connected! ✨\n\nWarm regards,\n*Dhansree*`;
                } else {
                    msg = `${greeting}\n\nIt was truly wonderful meeting you today! As discussed, here is the direct link to explore our exclusive collection at *Dhansree*:\n\n🌸 *Catalog Link:*\n${link}\n${coupon}${perksBlock}\nPlease feel free to reply right here on WhatsApp if you would like close-up fabric videos, sizing advice, or customized recommendations. Looking forward to staying connected! ✨\n\nWarm regards,\n*Dhansree Trends Jaipur*`;
                }
            } else if (this.template === 'festive_kurti') {
                msg = `${helloGreeting}\n\nDiscover our newest festive edit at *Dhansree Trends Jaipur* — handcrafted Kurta sets, regal Anarkalis, and trendy two-piece Co-ord sets crafted for effortless elegance.\n\n👗 *Explore the Collection:*\n${link}\n${coupon}${perksBlock}\nLet me know your favorite design, and I'll be glad to assist you with the perfect fit! ✨\n\n*Dhansree Trends Jaipur*`;
            } else if (this.template === 'quick_link') {
                const shortGreeting = name ? `Hi ${name}! ` : `Hi there! `;
                if (this.catalogType === 'product') {
                    const prodName = this.selectedProductName ? `*${this.selectedProductName}*` : 'your requested product';
                    msg = `${shortGreeting}Here is the direct link to buy ${prodName} online:\n\n🔗 ${link}\n${coupon}\nTap the link above and click *Buy Now* to order online with Free Express Delivery & COD across India!`;
                } else {
                    msg = `${shortGreeting}Here is the link to our latest *Dhansree* catalog:\n\n🔗 ${link}\n${coupon}\nExplore our collection online with Free Express Delivery across India!`;
                }
            } else if (this.template === 'wholesale') {
                if (this.catalogType === 'product') {
                    const prodName = this.selectedProductName ? `*${this.selectedProductName}*` : 'your requested item';
                    msg = `${greeting}\n\nThank you for connecting with *Dhansree*. You can review pricing and product details for ${prodName} at:\n\n📋 *Direct Product Link:*\n${link}\n\nWe offer bulk manufacturer pricing, verified lots, and reliable dispatch across India. Please share your required quantities right here so we can provide our best wholesale quote!\n\nWarm regards,\n*Dhansree*`;
                } else {
                    msg = `${greeting}\n\nThank you for connecting with *Dhansree Trends Jaipur*. You can review our current women's ethnic wear catalog and designs at:\n\n📋 *Direct Catalog Link:*\n${link}\n\nWe offer manufacturer-direct wholesale pricing, boutique curated lots, and reliable dispatch across India. Please share your shortlisted styles or required quantities right here so we can provide our best pricing!\n\nWarm regards,\n*Dhansree Trends Jaipur*`;
                }
            }

            this.customMessage = msg;
        },

        copyFullMessage() {
            this.copyToClipboard(this.customMessage)
                .then(() => {
                    this.showToast('Copied to Clipboard!', 'Draft message & catalog link copied. Ready to paste on WhatsApp or SMS.');
                })
                .catch(() => {
                    this.showToast('Copy Failed', 'Please select and copy the text manually.');
                });
        },

        copyCatalogLinkOnly() {
            this.copyToClipboard(this.activeCatalogLink)
                .then(() => {
                    this.showToast('Catalog Link Copied!', this.activeCatalogLink);
                })
                .catch(() => {
                    this.showToast('Copy Failed', 'Please copy link manually.');
                });
        },

        copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.left = '-999999px';
                textarea.style.top = '-999999px';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                return new Promise((resolve, reject) => {
                    try {
                        const successful = document.execCommand('copy');
                        textarea.remove();
                        successful ? resolve() : reject();
                    } catch (err) {
                        textarea.remove();
                        reject(err);
                    }
                });
            }
        },

        showToast(title, message) {
            this.toast.title = title;
            this.toast.message = message;
            this.toast.show = true;
            setTimeout(() => {
                this.toast.show = false;
            }, 3500);
        }
    };
}
</script>
@endsection
