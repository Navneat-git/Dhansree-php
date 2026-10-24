@extends('layouts.admin')

@section('title', 'Store Settings')

@section('content')
<div class="space-y-6" x-data="{ currentTab: 'general' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Store Settings</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Configure your business identity, tax rates, contact channels, payments, and SEO.</p>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto pb-px scrollbar-none">
        <button type="button" @click="currentTab = 'general'"
                :class="currentTab === 'general' ? 'border-amber-600 text-amber-700 bg-amber-50/60 font-semibold' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap rounded-t-lg">
            Store Profile
        </button>
        <button type="button" @click="currentTab = 'business'"
                :class="currentTab === 'business' ? 'border-amber-600 text-amber-700 bg-amber-50/60 font-semibold' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap rounded-t-lg">
            Business & GST
        </button>
        <button type="button" @click="currentTab = 'whatsapp'"
                :class="currentTab === 'whatsapp' ? 'border-amber-600 text-amber-700 bg-amber-50/60 font-semibold' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap rounded-t-lg">
            WhatsApp Chat
        </button>
        <button type="button" @click="currentTab = 'shipping_payments'"
                :class="currentTab === 'shipping_payments' ? 'border-amber-600 text-amber-700 bg-amber-50/60 font-semibold' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap rounded-t-lg">
            Payments & Shipping
        </button>
        <button type="button" @click="currentTab = 'social_seo'"
                :class="currentTab === 'social_seo' ? 'border-amber-600 text-amber-700 bg-amber-50/60 font-semibold' : 'border-transparent text-slate-600 hover:text-slate-900'"
                class="px-4 py-2.5 border-b-2 text-xs transition-all whitespace-nowrap rounded-t-lg">
            Social & SEO
        </button>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- TAB 1: GENERAL STORE -->
        <div x-show="currentTab === 'general'" class="space-y-6">
            <input type="hidden" name="section" value="general">
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200 pb-3">Store Contact & Identity</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Brand / Store Name</label>
                        <input type="text" name="store_name" value="{{ old('store_name', $settings->store_name ?? 'Dhansree') }}" required
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Support Email Address</label>
                        <input type="email" name="support_email" value="{{ old('support_email', $settings->support_email ?? 'info@dhansree.com') }}" required
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Displayed in website footer and customer transaction emails.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Support Phone / Helpdesk</label>
                        <input type="text" name="support_phone" value="{{ old('support_phone', $settings->support_phone ?? '+91 9887776777') }}"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Order Notification Email</label>
                        <input type="email" name="order_notification_email" value="{{ old('order_notification_email', $settings->order_notification_email ?? 'navneet.bhardwaj40553@gmail.com') }}"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Receives alerts on new customer orders.</p>
                    </div>

                    <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Default Top Bar Promo Coupon</label>
                        <input type="text" name="featured_coupon_code" value="{{ old('featured_coupon_code', $settings->featured_coupon_code ?? 'FESTIVE10') }}"
                               placeholder="e.g. FESTIVE10"
                               class="w-full sm:w-1/2 px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono font-bold uppercase text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Displayed on the continuous running top marquee bar and checkout coupon placeholder across dhansree.com.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: BUSINESS & GST -->
        <div x-show="currentTab === 'business'" class="space-y-6">
            <input type="hidden" name="section" value="business">
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200 pb-3">Legal Entity & Tax Configuration</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Legal Business Name</label>
                        <input type="text" name="legal_name" value="{{ old('legal_name', $settings->legal_name ?? 'Dhansree') }}"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">GSTIN (Goods and Services Tax ID)</label>
                        <input type="text" name="gstin" value="{{ old('gstin', $settings->gstin) }}" placeholder="e.g. 08AAAAA0000A1Z5" maxlength="15"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono uppercase text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Printed on customer GST tax invoices.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Default GST Rate</label>
                        <select name="default_gst_rate_bps" class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                            <option value="500" {{ ($settings->default_gst_rate_bps ?? 1800) == 500 ? 'selected' : '' }}>5% (Apparel &le; ₹1000)</option>
                            <option value="1200" {{ ($settings->default_gst_rate_bps ?? 1800) == 1200 ? 'selected' : '' }}>12% (Apparel &gt; ₹1000)</option>
                            <option value="1800" {{ ($settings->default_gst_rate_bps ?? 1800) == 1800 ? 'selected' : '' }}>18% (Standard / Accessories / Electronics)</option>
                            <option value="2800" {{ ($settings->default_gst_rate_bps ?? 1800) == 2800 ? 'selected' : '' }}>28% (Luxury Goods)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3 pt-6">
                        <input type="checkbox" name="prices_include_tax" id="tax_inc" value="1" {{ ($settings->prices_include_tax ?? true) ? 'checked' : '' }}
                               class="size-4 rounded text-amber-600 border-slate-300 focus:ring-amber-500">
                        <label for="tax_inc" class="text-xs font-semibold text-slate-700">Display prices include all taxes (GST)</label>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Registered Office Address</label>
                        <input type="text" name="address_line1" value="{{ old('address_line1', $settings->address_line1) }}" placeholder="Address Line 1"
                               class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 mb-2 shadow-2xs">
                        <div class="grid grid-cols-3 gap-3">
                            <input type="text" name="city" value="{{ old('city', $settings->city ?? 'Jaipur') }}" placeholder="City"
                                   class="px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs">
                            <input type="text" name="state_code" value="{{ old('state_code', $settings->state_code ?? 'RJ') }}" placeholder="State Code (e.g. RJ)"
                                   class="px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs uppercase text-slate-900 shadow-2xs">
                            <input type="text" name="postal_code" value="{{ old('postal_code', $settings->postal_code ?? '302001') }}" placeholder="PIN Code"
                                   class="px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: WHATSAPP CHAT -->
        <div x-show="currentTab === 'whatsapp'" class="space-y-6">
            <input type="hidden" name="section" value="whatsapp">
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h2 class="text-base font-bold text-slate-900">WhatsApp Floating Widget & Chat</h2>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="whatsapp_enabled" value="1" {{ ($settings->whatsapp_enabled ?? true) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        <span class="ml-2 text-xs font-semibold text-slate-700">Enable Widget</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">WhatsApp Number (with Country Code)</label>
                        <input type="text" name="whatsapp_phone" value="{{ old('whatsapp_phone', $settings->whatsapp_phone ?? '+919887776777') }}" placeholder="+919887776777"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Include country code (e.g. +91 9887776777).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Floating Button Text</label>
                        <input type="text" name="whatsapp_button_label" value="{{ old('whatsapp_button_label', $settings->whatsapp_button_label ?? 'Chat with Us') }}"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pre-filled Chat Message</label>
                        <input type="text" name="whatsapp_default_message" value="{{ old('whatsapp_default_message', $settings->whatsapp_default_message ?? 'Hi Dhansree! I have a question about my order.') }}"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: PAYMENTS & SHIPPING -->
        <div x-show="currentTab === 'shipping_payments'" class="space-y-6">
            <input type="hidden" name="section" value="payments_shipping">
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200 pb-3">Payment Gateways & Delivery Fees</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900">Razorpay Payment Gateway</span>
                            <input type="checkbox" name="razorpay_enabled" value="1" {{ ($settings->razorpay_enabled ?? true) ? 'checked' : '' }}
                                   class="size-4 rounded text-amber-600 border-slate-300 focus:ring-amber-500">
                        </div>
                        <p class="text-xs text-slate-500">Enables UPI, Credit/Debit cards, Net Banking, and Wallets via Razorpay checkout.</p>
                    </div>

                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900">Cash on Delivery (COD)</span>
                            <input type="checkbox" name="cod_enabled" value="1" {{ ($settings->cod_enabled ?? true) ? 'checked' : '' }}
                                   class="size-4 rounded text-amber-600 border-slate-300 focus:ring-amber-500">
                        </div>
                        <p class="text-xs text-slate-500">Allow customers to pay cash to the courier delivery agent.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Free Shipping Threshold (₹)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-slate-400 text-xs">₹</span>
                            <input type="number" step="1" min="0" name="free_shipping_threshold_rupees"
                                   value="{{ old('free_shipping_threshold_rupees', ($settings->free_shipping_threshold_paise ?? 99900) / 100) }}"
                                   class="w-full pl-7 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Orders at or above this amount receive free delivery.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Standard Delivery Fee (₹)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-slate-400 text-xs">₹</span>
                            <input type="number" step="1" min="0" name="shipping_flat_rate_rupees"
                                   value="{{ old('shipping_flat_rate_rupees', ($settings->shipping_flat_rate_paise ?? 9900) / 100) }}"
                                   class="w-full pl-7 pr-3 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Applied to orders below the free shipping threshold.</p>
                    </div>

                    <div class="sm:col-span-2 p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900">Shiprocket Courier Integration</span>
                            <input type="checkbox" name="shiprocket_enabled" value="1" {{ ($settings->shiprocket_enabled ?? false) ? 'checked' : '' }}
                                   class="size-4 rounded text-amber-600 border-slate-300 focus:ring-amber-500">
                        </div>
                        <p class="text-xs text-slate-500">Connects automated AWB generation, courier pickup, and live tracking.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 5: SOCIAL & SEO -->
        <div x-show="currentTab === 'social_seo'" class="space-y-6">
            <input type="hidden" name="section" value="social_seo">
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-xs p-6 space-y-5">
                <h2 class="text-base font-bold text-slate-900 border-b border-slate-200 pb-3">Social Profiles & Search Engine Verification</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Instagram Profile URL</label>
                        <input type="url" name="instagram_url" value="{{ old('instagram_url', $settings->instagram_url ?? 'https://instagram.com/dhansree_jaipur') }}"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Facebook Page URL</label>
                        <input type="url" name="facebook_url" value="{{ old('facebook_url', $settings->facebook_url ?? 'https://facebook.com/dhansree') }}"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">YouTube Channel URL</label>
                        <input type="url" name="youtube_url" value="{{ old('youtube_url', $settings->youtube_url ?? 'https://youtube.com/@dhansree') }}"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pinterest Profile URL</label>
                        <input type="url" name="pinterest_url" value="{{ old('pinterest_url', $settings->pinterest_url) }}"
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2 border-t border-slate-200 pt-5 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Marketing, Analytics & Tracking Scripts</h3>
                                <p class="text-[11px] text-slate-500">Configure your Google Tag Manager, GA4, Meta Pixel, or Search Console tags for external ad platforms.</p>
                            </div>
                        </div>

                        <!-- In-House Tracking Notice -->
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3.5 flex items-start gap-3">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div class="text-xs text-emerald-900 space-y-1">
                                <p class="font-bold">In-House Customer Journey Tracking is 100% Active & Server-Side</p>
                                <p class="text-emerald-800 leading-relaxed">
                                    You <strong>do not need</strong> to add any script or external tag manager for customer journeys to work. Dhansree tracks unique & repeat visitors (by email & IP), acquisition channels (Meta Ads, Google CPC, Instagram, Organic), device types, cities, and live page views automatically in-house. View them anytime at <a href="{{ route('admin.analytics.journeys') }}" class="font-semibold underline hover:text-emerald-950">Customers &rsaquo; Customer Journeys</a>.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Google Tag Manager Container ID</label>
                                <input type="text" name="gtm_container_id" value="{{ old('gtm_container_id', $settings->gtm_container_id) }}" placeholder="e.g. GTM-XXXXXXX"
                                       class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                                <p class="text-[10px] text-slate-400 mt-1">Automatically renders GTM &lt;head&gt; script and &lt;noscript&gt; iframe across all storefront pages.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Meta Pixel ID (Facebook / Instagram Ads)</label>
                                <input type="text" name="ads_meta_pixel_id" value="{{ old('ads_meta_pixel_id', $settings->ads_meta_pixel_id) }}" placeholder="e.g. 123456789012345"
                                       class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                                <p class="text-[10px] text-slate-400 mt-1">Pushes PageView, ViewContent, AddToCart, InitiateCheckout, and Purchase to Meta Ads Manager.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Google Analytics 4 Measurement ID</label>
                                <input type="text" name="analytics_google_measurement_id" value="{{ old('analytics_google_measurement_id', $settings->analytics_google_measurement_id) }}" placeholder="e.g. G-XXXXXXXXXX"
                                       class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                                <p class="text-[10px] text-slate-400 mt-1">Injects gtag.js with e-commerce event tracking (view_item, add_to_cart, purchase).</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Google Search Console Verification Code</label>
                                <input type="text" name="seo_google_verification" value="{{ old('seo_google_verification', $settings->seo_google_verification) }}" placeholder="e.g. AbCdEfGhIjKlMnOpQrSt"
                                       class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-lg text-xs font-mono text-slate-900 shadow-2xs focus:border-amber-500 focus:outline-none">
                                <p class="text-[10px] text-slate-400 mt-1">Renders &lt;meta name="google-site-verification"&gt; in &lt;head&gt;.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($canEdit)
            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs tracking-wider uppercase shadow-xs transition-all">
                    Save Store Settings
                </button>
            </div>
        @endif
    </form>
</div>
@endsection
