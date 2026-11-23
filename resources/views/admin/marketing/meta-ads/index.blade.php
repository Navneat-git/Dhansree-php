@extends('layouts.admin')

@section('title', 'Meta Ad Studio')
@section('page_title', 'Meta Ad Studio')

@section('content')
<div x-data="metaAdStudio({
    initialProduct: {{ Js::from($initialAdSuite['product'] ?? null) }},
    initialSuite: {{ Js::from($initialAdSuite ?? null) }},
    selectedProductId: {{ $selectedProduct ? $selectedProduct->id : 'null' }},
    defaultProvider: '{{ $settings['default_ai_provider'] ?? 'smart_ai' }}',
    defaultCampaign: '{{ $settings['default_utm_campaign'] ?? 'dhansree_meta_ads' }}',
    hasGeminiKey: {{ !empty($settings['gemini_api_key']) ? 'true' : 'false' }},
    hasOpenAiKey: {{ !empty($settings['openai_api_key']) ? 'true' : 'false' }},
})" class="space-y-6">

    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 p-6 sm:p-8 text-white shadow-xl border border-slate-700/50">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-semibold uppercase tracking-wider">
                    <svg class="size-3.5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Meta Ads Manager Content Generator
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white flex items-center gap-3">
                    Meta Ad Studio
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30">Shop Now CTA</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                    Generate ready-to-run Facebook &amp; Instagram ad copies with the exact <strong>"Shop Now"</strong> Call to Action, UTM tracking, and live mobile previews. Powered by Built-in Smart D2C Copy Engine, Google Gemini, and ChatGPT.
                </p>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
                <button type="button" @click="settingsModalOpen = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white border border-white/20 text-xs font-semibold backdrop-blur-sm transition-all shadow-sm">
                    <svg class="size-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.15.894c-.09.542-.559.94-1.109.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.11v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span>AI &amp; UTM Settings</span>
                </button>
            </div>
        </div>

        <!-- Subtle backdrop decor -->
        <div class="absolute -right-8 -bottom-16 size-64 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-32 -top-16 size-48 rounded-full bg-amber-500/10 blur-2xl pointer-events-none"></div>
    </div>

    <!-- Toast Notification -->
    <div x-show="notification" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-2xl ring-1 ring-white/10">
        <svg class="size-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
        <span x-text="notification"></span>
    </div>

    <!-- Main Studio Workspace (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Left Column: Controls & Ad Copy Form (7 cols) -->
        <div class="lg:col-span-7 space-y-6">

            <!-- 1. Product Selection Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <label for="product_select" class="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <span class="size-2 rounded-full bg-amber-500"></span>
                        1. Select Website Product
                    </label>
                    <span class="text-[0.6875rem] text-slate-500 font-medium">
                        {{ $products->count() }} active products ready
                    </span>
                </div>

                <div class="relative">
                    <select id="product_select"
                            x-model="selectedProductId"
                            @change="onProductChange()"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-slate-50/50 text-sm font-semibold text-slate-900 focus:bg-white focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition-all shadow-2xs">
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" {{ $selectedProduct && $selectedProduct->id === $prod->id ? 'selected' : '' }}>
                                {{ $prod->name }} — {{ $prod->formatted_price }} ({{ $prod->category?->name ?? 'Ethnic Wear' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Product Preview Card -->
                <div x-show="product" class="p-4 rounded-xl bg-slate-50 border border-slate-200/70 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <img :src="product?.primary_image_url || '{{ asset('images/logo.png') }}'"
                             :alt="product?.name"
                             class="size-14 rounded-lg object-cover border border-slate-200 bg-white shrink-0">
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-slate-900 truncate" x-text="product?.name"></h4>
                            <p class="text-[0.6875rem] text-slate-500 flex items-center gap-2 mt-0.5">
                                <span class="font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded" x-text="product?.category"></span>
                                <span>•</span>
                                <span x-text="'Fabric: ' + (product?.fabric || 'Cotton')"></span>
                            </p>
                            <p class="text-[0.6875rem] text-slate-500 mt-0.5">
                                <span x-text="'Sizes: ' + (product?.sizes_text || 'S, M, L, XL')"></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center sm:flex-col sm:items-end justify-between w-full sm:w-auto shrink-0 border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-200">
                        <div class="text-right">
                            <span class="text-sm font-extrabold text-slate-900" x-text="product?.formatted_price"></span>
                            <span class="text-xs text-slate-400 line-through ml-1" x-text="product?.formatted_mrp"></span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.625rem] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                              x-show="product?.discount_pct > 0"
                              x-text="product?.discount_pct + '% OFF'"></span>
                    </div>
                </div>
            </div>

            <!-- 2. AI Generator Controls Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <span class="size-2 rounded-full bg-blue-500"></span>
                        2. AI Engine &amp; Creative Angle
                    </label>
                    <span class="text-[0.6875rem] text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full font-semibold">
                        Instant D2C Copy
                    </span>
                </div>

                <!-- Provider Selector -->
                <div class="grid grid-cols-3 gap-2.5">
                    <button type="button"
                            @click="provider = 'smart_ai'"
                            :class="provider === 'smart_ai' ? 'border-amber-500 bg-amber-50/70 text-amber-900 ring-2 ring-amber-500/20' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            class="p-3 rounded-xl border text-left transition-all relative">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold">Dhansree Smart AI</span>
                            <span class="size-2 rounded-full" :class="provider === 'smart_ai' ? 'bg-amber-500' : 'bg-slate-300'"></span>
                        </div>
                        <p class="text-[0.6875rem] text-slate-500 mt-1">Built-in D2C direct-response engine (Instant, 100% reliable)</p>
                    </button>

                    <button type="button"
                            @click="provider = 'gemini'"
                            :class="provider === 'gemini' ? 'border-blue-500 bg-blue-50/70 text-blue-900 ring-2 ring-blue-500/20' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            class="p-3 rounded-xl border text-left transition-all relative">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold">Google Gemini</span>
                            <span class="size-2 rounded-full" :class="provider === 'gemini' ? 'bg-blue-500' : 'bg-slate-300'"></span>
                        </div>
                        <p class="text-[0.6875rem] text-slate-500 mt-1">Gemini 1.5 Flash (Creative Indian copy &amp; emotional hooks)</p>
                    </button>

                    <button type="button"
                            @click="provider = 'chatgpt'"
                            :class="provider === 'chatgpt' ? 'border-emerald-500 bg-emerald-50/70 text-emerald-900 ring-2 ring-emerald-500/20' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            class="p-3 rounded-xl border text-left transition-all relative">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold">ChatGPT / OpenAI</span>
                            <span class="size-2 rounded-full" :class="provider === 'chatgpt' ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                        </div>
                        <p class="text-[0.6875rem] text-slate-500 mt-1">GPT-4o-mini (Persuasive AIDA &amp; PAS frameworks)</p>
                    </button>
                </div>

                <!-- Custom Instructions (Optional) -->
                <div>
                    <label for="custom_instructions" class="block text-xs font-semibold text-slate-700 mb-1">
                        Custom Hook or Instructions <span class="text-slate-400 font-normal">(Optional)</span>
                    </label>
                    <input type="text"
                           id="custom_instructions"
                           x-model="customInstructions"
                           placeholder="e.g., Emphasize Navratri Special, mention Buy 2 Get 10% Off, highlight Mulmul Dupatta..."
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs text-slate-900 placeholder:text-slate-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none">
                </div>

                <!-- Generate Action Button -->
                <button type="button"
                        @click="generateCopies()"
                        :disabled="loading"
                        class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold text-xs uppercase tracking-wider shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                    <template x-if="loading">
                        <svg class="animate-spin size-4 text-slate-950" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </template>
                    <template x-if="!loading">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                        </svg>
                    </template>
                    <span x-text="loading ? 'Generating Ad Package...' : '✨ Generate / Refresh Ad Copy'"></span>
                </button>
            </div>

            <!-- 3. Angle Tabs Selector -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        3. Meta Ad Copy Fields
                    </label>
                    <span class="text-[0.6875rem] text-slate-500">
                        Choose angle to copy or customize
                    </span>
                </div>

                <!-- Angle Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                    <template x-for="(angleData, angleKey) in angles" :key="angleKey">
                        <button type="button"
                                @click="currentAngle = angleKey"
                                :class="currentAngle === angleKey ? 'bg-slate-900 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                class="px-3.5 py-2 rounded-xl text-xs whitespace-nowrap transition-colors flex items-center gap-1.5 shrink-0">
                            <span x-text="angleData.label"></span>
                        </button>
                    </template>
                </div>

                <!-- Form Fields For Selected Angle -->
                <div class="space-y-4 pt-2" x-show="currentAd">
                    <!-- Angle Tagline -->
                    <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-200/60 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-amber-900" x-text="currentAd?.angle_label"></span>
                            <span class="text-amber-700/80 hidden sm:inline" x-text="'— ' + (currentAd?.angle_tagline || '')"></span>
                        </div>
                        <span class="text-[0.625rem] font-bold px-2 py-0.5 rounded-full bg-amber-200 text-amber-900" x-text="currentAd?.badge"></span>
                    </div>

                    <!-- Primary Text -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-800">
                                Primary Text <span class="text-slate-400 font-normal">(Post Caption)</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-[0.6875rem] text-slate-400" x-text="(currentAd?.primary_text?.length || 0) + ' chars'"></span>
                                <button type="button"
                                        @click="copyText(currentAd?.primary_text, 'Primary Text copied!')"
                                        class="text-xs font-semibold text-amber-800 hover:text-amber-950 flex items-center gap-1 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                    Copy
                                </button>
                            </div>
                        </div>
                        <textarea x-model="currentAd.primary_text"
                                  rows="7"
                                  class="w-full p-3.5 rounded-xl border border-slate-300 text-xs font-mono text-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none leading-relaxed"></textarea>
                    </div>

                    <!-- Headline -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-800">
                                Headline <span class="text-slate-400 font-normal">(Bold title beside CTA, max 40-45 chars)</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-[0.6875rem]"
                                      :class="(currentAd?.headline?.length || 0) > 45 ? 'text-rose-500 font-bold' : 'text-slate-400'"
                                      x-text="(currentAd?.headline?.length || 0) + '/45 chars'"></span>
                                <button type="button"
                                        @click="copyText(currentAd?.headline, 'Headline copied!')"
                                        class="text-xs font-semibold text-amber-800 hover:text-amber-950 flex items-center gap-1 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                    Copy
                                </button>
                            </div>
                        </div>
                        <input type="text"
                               x-model="currentAd.headline"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-900 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none">
                    </div>

                    <!-- Description -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-800">
                                Description <span class="text-slate-400 font-normal">(Link description under headline, max 30-35 chars)</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-[0.6875rem]"
                                      :class="(currentAd?.description?.length || 0) > 35 ? 'text-rose-500 font-bold' : 'text-slate-400'"
                                      x-text="(currentAd?.description?.length || 0) + '/35 chars'"></span>
                                <button type="button"
                                        @click="copyText(currentAd?.description, 'Description copied!')"
                                        class="text-xs font-semibold text-amber-800 hover:text-amber-950 flex items-center gap-1 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                    Copy
                                </button>
                            </div>
                        </div>
                        <input type="text"
                               x-model="currentAd.description"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-900 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none">
                    </div>

                    <!-- CTA & Destination URL Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                        <!-- CTA Pill -->
                        <div class="sm:col-span-4">
                            <label class="block text-xs font-bold text-slate-800 mb-1.5">
                                Call To Action (CTA)
                            </label>
                            <div class="px-3.5 py-2.5 rounded-xl border border-blue-200 bg-blue-50/50 flex items-center justify-between">
                                <span class="text-xs font-extrabold text-blue-900">Shop Now</span>
                                <span class="text-[0.625rem] font-bold text-blue-700 bg-blue-200/60 px-1.5 py-0.5 rounded">Meta Button</span>
                            </div>
                        </div>

                        <!-- Destination URL -->
                        <div class="sm:col-span-8">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-bold text-slate-800">
                                    Website URL <span class="text-slate-400 font-normal">(UTM Tagged)</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <a :href="currentAd?.destination_url" target="_blank"
                                       class="text-[0.6875rem] font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1">
                                        Test Link ↗
                                    </a>
                                    <button type="button"
                                            @click="copyText(currentAd?.destination_url, 'Destination URL copied!')"
                                            class="text-xs font-semibold text-amber-800 hover:text-amber-950 flex items-center gap-1 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" /></svg>
                                        Copy URL
                                    </button>
                                </div>
                            </div>
                            <input type="text"
                                   x-model="currentAd.destination_url"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono text-slate-700 bg-slate-50 focus:bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none truncate">
                        </div>
                    </div>

                    <!-- Master Action Buttons -->
                    <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <button type="button"
                                @click="copyAllPackage()"
                                class="w-full sm:w-auto px-5 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-sm transition-all">
                            <svg class="size-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                            <span>📋 Copy Complete Ad Package (All Fields)</span>
                        </button>

                        <button type="button"
                                @click="saveCreative()"
                                :disabled="saveLoading"
                                class="w-full sm:w-auto px-5 py-3 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-all disabled:opacity-50">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                            </svg>
                            <span x-text="saveLoading ? 'Saving...' : '💾 Save to Ad Library'"></span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Interactive Meta Ad Mockup (5 cols sticky) -->
        <div class="lg:col-span-5 sticky top-20 space-y-4">

            <!-- Mockup Header & Platform Toggle -->
            <div class="flex items-center justify-between px-2">
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="size-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    Live Meta Feed Preview
                </span>

                <!-- Platform Pills -->
                <div class="p-1 rounded-xl bg-slate-200/80 flex items-center gap-1 text-[0.6875rem]">
                    <button type="button"
                            @click="previewPlatform = 'facebook'"
                            :class="previewPlatform === 'facebook' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-lg transition-all flex items-center gap-1">
                        <span>Facebook</span>
                    </button>
                    <button type="button"
                            @click="previewPlatform = 'instagram'"
                            :class="previewPlatform === 'instagram' ? 'bg-white text-rose-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-lg transition-all flex items-center gap-1">
                        <span>Instagram</span>
                    </button>
                </div>
            </div>

            <!-- Phone / Card Mockup Frame -->
            <div class="rounded-2xl border-4 border-slate-800 bg-white shadow-2xl overflow-hidden max-w-sm mx-auto">

                <!-- ─── FACEBOOK FEED PREVIEW ─── -->
                <div x-show="previewPlatform === 'facebook'" class="divide-y divide-slate-100 text-slate-900">
                    <!-- Post Header -->
                    <div class="p-3.5 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="size-10 rounded-full bg-gradient-to-br from-maroon-800 to-maroon-950 text-gold-300 font-serif font-bold text-lg flex items-center justify-center ring-2 ring-gold-400/40">
                                d
                            </div>
                            <div>
                                <div class="flex items-center gap-1">
                                    <span class="text-xs font-bold text-slate-900 leading-tight">Dhansree Trends</span>
                                    <svg class="size-3 text-blue-500 fill-current" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                </div>
                                <p class="text-[0.625rem] text-slate-500 leading-tight mt-0.5 flex items-center gap-1">
                                    <span>Sponsored</span>
                                    <span>·</span>
                                    <svg class="size-2.5 fill-current text-slate-400" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                                </p>
                            </div>
                        </div>

                        <button type="button" class="text-slate-400 hover:text-slate-600 p-1">
                            <svg class="size-4.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        </button>
                    </div>

                    <!-- Post Primary Text -->
                    <div class="px-3.5 py-2.5 text-xs text-slate-800 whitespace-pre-line leading-relaxed">
                        <div :class="seeMoreOpen ? '' : 'line-clamp-3'" x-text="currentAd?.primary_text"></div>
                        <button type="button"
                                @click="seeMoreOpen = !seeMoreOpen"
                                class="text-slate-500 font-semibold hover:underline mt-1 text-[0.6875rem]"
                                x-text="seeMoreOpen ? 'See less' : '... See more'"></button>
                    </div>

                    <!-- Creative Image with Overlay Tag -->
                    <div class="relative bg-slate-100 aspect-square overflow-hidden group">
                        <img :src="selectedImage || product?.primary_image_url || '{{ asset('images/logo.png') }}'"
                             :alt="product?.name"
                             class="w-full h-full object-cover">
                        <div class="absolute top-3 left-3 bg-slate-900/80 backdrop-blur-xs text-white text-[0.625rem] font-bold px-2 py-0.5 rounded-full border border-white/20">
                            <span x-text="currentAd?.badge || 'Special Edit'"></span>
                        </div>
                    </div>

                    <!-- Link Card with Shop Now CTA -->
                    <div class="p-3 bg-slate-50 flex items-center justify-between gap-3 border-t border-slate-200">
                        <div class="min-w-0 flex-1">
                            <span class="text-[0.625rem] text-slate-500 uppercase tracking-wider font-semibold block truncate">DHANSREE.COM</span>
                            <h4 class="text-xs font-bold text-slate-900 truncate mt-0.5" x-text="currentAd?.headline || 'Handcrafted Jaipur Kurti Sets'"></h4>
                            <p class="text-[0.625rem] text-slate-500 truncate mt-0.5" x-text="currentAd?.description || 'Cash on Delivery Available'"></p>
                        </div>
                        <a :href="currentAd?.destination_url" target="_blank"
                           class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-900 text-xs font-bold shrink-0 shadow-2xs border border-slate-300/80 transition-colors flex items-center gap-1"
                           title="Clicks to live product page with UTM tags">
                            <span>Shop Now</span>
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        </a>
                    </div>
                </div>

                <!-- ─── INSTAGRAM FEED PREVIEW ─── -->
                <div x-show="previewPlatform === 'instagram'" class="divide-y divide-slate-100 text-slate-900">
                    <!-- Post Header -->
                    <div class="p-3 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="size-8 rounded-full p-0.5 bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600">
                                <div class="w-full h-full rounded-full bg-slate-900 text-gold-300 font-serif font-bold text-xs flex items-center justify-center">
                                    d
                                </div>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-900 block leading-tight">dhansree_trends</span>
                                <span class="text-[0.625rem] text-slate-500 block leading-tight">Sponsored</span>
                            </div>
                        </div>

                        <button type="button" class="text-slate-400 hover:text-slate-600">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 20 20"><path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"/></svg>
                        </button>
                    </div>

                    <!-- Creative Image -->
                    <div class="relative bg-slate-100 aspect-square overflow-hidden">
                        <img :src="selectedImage || product?.primary_image_url || '{{ asset('images/logo.png') }}'"
                             :alt="product?.name"
                             class="w-full h-full object-cover">
                    </div>

                    <!-- Instagram CTA Banner -->
                    <a :href="currentAd?.destination_url" target="_blank"
                       class="px-3.5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-between text-xs font-bold transition-colors">
                        <span class="flex items-center gap-1.5">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                            Shop now
                        </span>
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </a>

                    <!-- Action Icons Row -->
                    <div class="p-3 flex items-center justify-between text-slate-800">
                        <div class="flex items-center gap-3.5">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 0 1-.923 1.785A5.969 5.969 0 0 0 6 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337Z" /></svg>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        </div>
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                    </div>

                    <!-- Caption & Headline -->
                    <div class="px-3 pb-3 text-xs text-slate-800 space-y-1">
                        <p class="font-bold text-slate-900" x-text="currentAd?.headline"></p>
                        <div :class="seeMoreOpen ? '' : 'line-clamp-2'" class="whitespace-pre-line text-[0.6875rem] text-slate-700 leading-relaxed">
                            <span class="font-bold text-slate-900 mr-1">dhansree_trends</span>
                            <span x-text="currentAd?.primary_text"></span>
                        </div>
                        <button type="button"
                                @click="seeMoreOpen = !seeMoreOpen"
                                class="text-slate-400 font-medium hover:underline text-[0.625rem]"
                                x-text="seeMoreOpen ? 'less' : 'more'"></button>
                    </div>
                </div>

            </div>

            <!-- Creative Image Picker & Download Row -->
            <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-2xs space-y-3">
                <div class="flex items-center justify-between text-xs font-semibold text-slate-700">
                    <span>Select Ad Creative Image</span>
                    <a :href="selectedImage || product?.primary_image_url" download="dhansree-meta-creative.jpg" target="_blank"
                       class="text-blue-700 hover:text-blue-900 flex items-center gap-1 font-bold">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        Download Image
                    </a>
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                    <template x-for="(imgUrl, idx) in product?.all_images || []" :key="idx">
                        <button type="button"
                                @click="selectedImage = imgUrl"
                                :class="selectedImage === imgUrl ? 'ring-2 ring-amber-500 scale-105' : 'opacity-70 hover:opacity-100'"
                                class="size-12 rounded-lg overflow-hidden border border-slate-200 shrink-0 transition-all">
                            <img :src="imgUrl" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>
            </div>

        </div>

    </div>

    <!-- 4. Saved Ad Creatives Library Section -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <svg class="size-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                    Saved Meta Ad Library
                </h3>
                <p class="text-[0.6875rem] text-slate-500">Previously saved winning ad copies ready to paste into Meta Ads Manager</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700">
                {{ $savedCreatives->total() }} creatives saved
            </span>
        </div>

        @if($savedCreatives->isEmpty())
            <div class="py-12 text-center text-slate-400 text-xs">
                <svg class="size-10 mx-auto text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                No saved ad creatives yet. Generate your first ad above and click "Save to Ad Library".
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[0.6875rem] font-bold text-slate-600 uppercase border-y border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Product &amp; Angle</th>
                            <th class="py-3 px-4">Headline &amp; Copy Preview</th>
                            <th class="py-3 px-4">CTA &amp; Destination</th>
                            <th class="py-3 px-4">Saved Date</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($savedCreatives as $item)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ $item->creative_image_url ?: $item->product?->primary_image_url ?: asset('images/logo.png') }}"
                                         class="size-10 rounded-lg object-cover border border-slate-200 shrink-0">
                                    <div class="min-w-0 max-w-[14rem]">
                                        <p class="font-bold text-slate-900 truncate">{{ $item->product?->name ?? 'Custom Product' }}</p>
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[0.625rem] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            {{ ucfirst($item->angle) }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 max-w-xs">
                                <p class="font-bold text-slate-800 truncate">{{ $item->headline }}</p>
                                <p class="text-[0.6875rem] text-slate-500 truncate mt-0.5">{{ Str::limit($item->primary_text, 65) }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded text-[0.625rem]">
                                    {{ $item->cta }}
                                </span>
                                <a href="{{ $item->destination_url }}" target="_blank" class="block text-[0.625rem] text-slate-400 hover:text-slate-700 mt-1 truncate max-w-[12rem]">
                                    {{ $item->destination_url }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                                {{ $item->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button"
                                            @click="copyText({{ Js::from($item->full_copy_bundle) }}, 'Complete Ad Package copied!')"
                                            class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-2xs transition-colors">
                                        Copy Package
                                    </button>

                                    <form method="POST" action="{{ route('admin.meta-ads.destroy', $item) }}" onsubmit="return confirm('Delete this saved creative?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700 transition-colors" title="Delete">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-3 border-t border-slate-100">
                {{ $savedCreatives->links() }}
            </div>
        @endif
    </div>

    <!-- 5. AI & Settings Modal -->
    <div x-show="settingsModalOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
         @click.self="settingsModalOpen = false">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="size-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.15.894c-.09.542-.559.94-1.109.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.11v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" /></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">AI &amp; Meta UTM Settings</h3>
                        <p class="text-[0.6875rem] text-slate-500">Configure your optional AI API keys &amp; default campaign name</p>
                    </div>
                </div>
                <button type="button" @click="settingsModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form method="POST" action="{{ route('admin.meta-ads.settings.update') }}" class="space-y-4">
                @csrf

                <!-- Default AI Provider -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Default AI Provider</label>
                    <select name="default_ai_provider" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-900">
                        <option value="smart_ai" {{ ($settings['default_ai_provider'] ?? '') === 'smart_ai' ? 'selected' : '' }}>Dhansree Smart AI (Built-in, Instant, No key required)</option>
                        <option value="gemini" {{ ($settings['default_ai_provider'] ?? '') === 'gemini' ? 'selected' : '' }}>Google Gemini (Gemini 1.5 Flash)</option>
                        <option value="chatgpt" {{ ($settings['default_ai_provider'] ?? '') === 'chatgpt' ? 'selected' : '' }}>ChatGPT (OpenAI GPT-4o-mini)</option>
                    </select>
                </div>

                <!-- Google Gemini API Key -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold text-slate-700">Google Gemini API Key</label>
                        <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-[0.625rem] text-blue-600 hover:underline">Get Free Gemini Key ↗</a>
                    </div>
                    <input type="password"
                           name="gemini_api_key"
                           value="{{ $settings['gemini_api_key'] ?? '' }}"
                           placeholder="AIzaSy..."
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono text-slate-900">
                    <p class="text-[0.625rem] text-slate-400 mt-0.5">Used for Google Gemini 1.5 Flash generation</p>
                </div>

                <!-- OpenAI API Key -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold text-slate-700">OpenAI / ChatGPT API Key</label>
                        <a href="https://platform.openai.com/api-keys" target="_blank" class="text-[0.625rem] text-blue-600 hover:underline">Get OpenAI Key ↗</a>
                    </div>
                    <input type="password"
                           name="openai_api_key"
                           value="{{ $settings['openai_api_key'] ?? '' }}"
                           placeholder="sk-..."
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono text-slate-900">
                    <p class="text-[0.625rem] text-slate-400 mt-0.5">Used for ChatGPT GPT-4o-mini generation</p>
                </div>

                <!-- Default UTM Campaign -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Default UTM Campaign Tag</label>
                    <input type="text"
                           name="default_utm_campaign"
                           value="{{ $settings['default_utm_campaign'] ?? 'dhansree_meta_ads' }}"
                           class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono text-slate-900">
                    <p class="text-[0.625rem] text-slate-400 mt-0.5">Appended to product URLs: ?utm_source=meta&utm_medium=paid_social&utm_campaign=...</p>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="settingsModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-xs">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function metaAdStudio(config) {
    return {
        selectedProductId: config.selectedProductId,
        product: config.initialProduct,
        provider: config.defaultProvider || 'smart_ai',
        currentAngle: 'festive',
        angles: config.initialSuite?.angles || {},
        customInstructions: '',
        utmCampaign: config.defaultCampaign || 'dhansree_meta_ads',
        selectedImage: config.initialProduct?.primary_image_url || '',
        previewPlatform: 'facebook',
        loading: false,
        saveLoading: false,
        notification: '',
        seeMoreOpen: false,
        settingsModalOpen: false,

        get currentAd() {
            if (!this.angles || Object.keys(this.angles).length === 0) return null;
            return this.angles[this.currentAngle] || Object.values(this.angles)[0] || null;
        },

        init() {
            if (!this.selectedImage && this.product?.primary_image_url) {
                this.selectedImage = this.product.primary_image_url;
            }
        },

        async onProductChange() {
            if (!this.selectedProductId) return;
            this.loading = true;

            try {
                const res = await fetch(`/admin/meta-ads/product-data/${this.selectedProductId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success && data.product) {
                    this.product = data.product;
                    this.selectedImage = data.product.primary_image_url;
                    await this.generateCopies();
                }
            } catch (err) {
                console.error(err);
                this.showToast('Failed to load product data.');
            } finally {
                this.loading = false;
            }
        },

        async generateCopies() {
            if (!this.selectedProductId) return;
            this.loading = true;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch('/admin/meta-ads/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        product_id: this.selectedProductId,
                        provider: this.provider,
                        angle: 'all',
                        custom_instructions: this.customInstructions,
                        utm_campaign: this.utmCampaign,
                        image_url: this.selectedImage || undefined,
                    })
                });

                const json = await res.json().catch(() => null);
                if (res.ok && json?.success && json.data?.angles) {
                    this.angles = json.data.angles;
                    this.product = json.data.product;
                    this.showToast('✨ High-converting ad copy generated!');
                } else {
                    const errorMsg = json?.message || 'Error generating ad copy.';
                    this.showToast(errorMsg);
                }
            } catch (err) {
                console.error('Ad generator error:', err);
                this.showToast('Error connecting to ad generator.');
            } finally {
                this.loading = false;
            }
        },

        async saveCreative() {
            if (!this.selectedProductId) {
                this.showToast('Please select a product first.');
                return;
            }
            if (!this.currentAd) {
                this.showToast('No active ad copy to save. Please generate or select an angle.');
                return;
            }

            this.saveLoading = true;

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const angleLabel = this.currentAd.angle_label || this.currentAngle || 'Ad';
                const prodName = this.product?.name || '';
                const fallbackDestUrl = this.product?.url 
                    ? (this.product.url + '?utm_source=meta&utm_medium=paid_social&utm_campaign=' + encodeURIComponent(this.utmCampaign) + '&utm_content=' + encodeURIComponent(this.currentAngle))
                    : '';

                const payload = {
                    product_id: this.selectedProductId,
                    campaign_name: this.utmCampaign || 'Dhansree Meta Campaign',
                    ad_name: angleLabel + (prodName ? ' - ' + prodName.substring(0, 30) : ''),
                    angle: this.currentAngle,
                    primary_text: this.currentAd.primary_text || '',
                    headline: this.currentAd.headline || prodName,
                    description: this.currentAd.description || 'Cash on Delivery | Free Shipping',
                    cta: this.currentAd.cta || 'Shop Now',
                    destination_url: this.currentAd.destination_url || fallbackDestUrl,
                    utm_campaign: this.utmCampaign,
                    creative_image_url: this.selectedImage || this.product?.primary_image_url || '',
                    ai_provider: this.provider,
                };

                const res = await fetch('/admin/meta-ads/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify(payload)
                });

                const json = await res.json().catch(() => null);
                if (res.ok && json?.success) {
                    this.showToast('💾 Ad package saved to your library!');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    let errorMsg = json?.message;
                    if (json?.errors) {
                        errorMsg = Object.values(json.errors).flat().join(' ');
                    }
                    this.showToast(errorMsg || 'Could not save creative.');
                }
            } catch (err) {
                console.error('Save creative error:', err);
                this.showToast('Error saving creative to library.');
            } finally {
                this.saveLoading = false;
            }
        },

        copyText(text, successMsg = 'Copied to clipboard!') {
            if (!text) return;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showToast(successMsg);
                });
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    this.showToast(successMsg);
                } catch (e) {
                    prompt('Copy text:', text);
                }
                document.body.removeChild(ta);
            }
        },

        copyAllPackage() {
            if (!this.currentAd) return;
            const fullBundle = this.currentAd.full_copy_bundle || `=== META ADS MANAGER PACKAGE ===
Product: ${this.product?.name}
Angle: ${this.currentAd.angle_label}
Call To Action (CTA): Shop Now
Website URL (with UTM):
${this.currentAd.destination_url}

--- [PRIMARY TEXT] ---
${this.currentAd.primary_text}

--- [HEADLINE] ---
${this.currentAd.headline}

--- [DESCRIPTION] ---
${this.currentAd.description}
================================`;

            this.copyText(fullBundle, '📋 Complete Meta Ad Package copied to clipboard!');
        },

        showToast(msg) {
            this.notification = msg;
            setTimeout(() => {
                this.notification = '';
            }, 3000);
        }
    };
}
</script>
@endsection
