@extends('layouts.admin')

@section('title', 'Studio Settings')
@section('page_title', 'Social Commerce Studio Settings')

@section('header_actions')
<a href="{{ route('admin.social-commerce.dashboard') }}"
   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs transition-colors">
    &larr; Studio Dashboard
</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.social-commerce.settings.update') }}" class="space-y-6 max-w-4xl mx-auto">
    @csrf

    <!-- Brand Identity Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
            Dhansree Brand Identity &amp; Colours
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Brand Name *</label>
                <input type="text" name="brand_name" value="{{ old('brand_name', $settings['brand_name'] ?? 'Dhansree') }}" required
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Brand Tagline *</label>
                <input type="text" name="tagline" value="{{ old('tagline', $settings['tagline'] ?? 'STYLE. TECH. EVERYDAY.') }}" required
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Primary Maroon Colour</label>
                <div class="flex items-center gap-2">
                    <input type="color" name="primary_color" value="{{ old('primary_color', $settings['primary_color'] ?? '#580000') }}"
                           class="size-9 rounded-lg border border-slate-300 p-0.5 cursor-pointer">
                    <input type="text" name="primary_color_text" value="{{ old('primary_color', $settings['primary_color'] ?? '#580000') }}" readonly
                           class="w-full text-xs font-mono rounded-lg border-slate-200 bg-slate-50 py-2">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Accent Gold Colour</label>
                <div class="flex items-center gap-2">
                    <input type="color" name="gold_color" value="{{ old('gold_color', $settings['gold_color'] ?? '#B08858') }}"
                           class="size-9 rounded-lg border border-slate-300 p-0.5 cursor-pointer">
                    <input type="text" name="gold_color_text" value="{{ old('gold_color', $settings['gold_color'] ?? '#B08858') }}" readonly
                           class="w-full text-xs font-mono rounded-lg border-slate-200 bg-slate-50 py-2">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Background Cream Colour</label>
                <div class="flex items-center gap-2">
                    <input type="color" name="cream_color" value="{{ old('cream_color', $settings['cream_color'] ?? '#FBFAF8') }}"
                           class="size-9 rounded-lg border border-slate-300 p-0.5 cursor-pointer">
                    <input type="text" name="cream_color_text" value="{{ old('cream_color', $settings['cream_color'] ?? '#FBFAF8') }}" readonly
                           class="w-full text-xs font-mono rounded-lg border-slate-200 bg-slate-50 py-2">
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Integration Card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
            WhatsApp Order Enquiry Settings
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">WhatsApp Business Phone Number *</label>
                <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '+919887776777') }}" required
                       placeholder="+919887776777"
                       class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
                <p class="text-[10px] text-slate-400 mt-1">Used on WhatsApp Status CTA badges and click-to-chat links.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">WhatsApp Call to Action Text *</label>
                <input type="text" name="whatsapp_order_cta" value="{{ old('whatsapp_order_cta', $settings['whatsapp_order_cta'] ?? 'DM or WhatsApp us to order') }}" required
                       placeholder="e.g. DM or WhatsApp us to order"
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
            </div>
        </div>
    </div>

    <!-- Default Social Hashtags -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
            Default Social Media Hashtags
        </h3>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Default Hashtag String</label>
            <textarea name="default_hashtags" rows="3"
                      class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 p-2.5 leading-relaxed">{{ old('default_hashtags', $settings['default_hashtags'] ?? '#Dhansree #WomenFashion #LatestSuits #EthnicWear #FashionCollection #IndianSuits #HandcraftedFashion') }}</textarea>
            <p class="text-[10px] text-slate-400 mt-1">Automatically appended to generated Instagram &amp; Facebook captions.</p>
        </div>
    </div>

    <!-- Meta Graph API Credentials -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs space-y-4">
        <h3 class="text-sm font-semibold text-slate-900 border-b border-slate-100 pb-2">
            Meta Graph API Credentials (Instagram &amp; Facebook)
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Meta App ID</label>
                <input type="text" name="meta_app_id" value="{{ old('meta_app_id', $settings['meta_app_id'] ?? '') }}" placeholder="e.g. 123456789012345"
                       class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Meta App Secret</label>
                <input type="password" name="meta_app_secret" value="{{ old('meta_app_secret', $settings['meta_app_secret'] ?? '') }}" placeholder="••••••••••••••••"
                       class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Graph API Version</label>
                <input type="text" name="meta_api_version" value="{{ old('meta_api_version', $settings['meta_api_version'] ?? 'v20.0') }}"
                       class="w-full text-xs font-mono rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500 py-2">
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('admin.social-commerce.dashboard') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
            Cancel
        </a>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-xs transition-colors">
            Save Studio Settings
        </button>
    </div>
</form>
@endsection
