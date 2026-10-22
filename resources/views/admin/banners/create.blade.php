@extends('layouts.admin')

@section('title', 'Create Homepage Banner')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.banners.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-800 transition-colors mb-2">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Banners
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create Homepage Banner</h1>
            <p class="text-xs text-slate-500 mt-1">Configure banner images, overlay text, link target, and active schedule.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <p class="font-semibold">Please fix the following validation errors:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Text & Content Card -->
        <div class="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Banner Content & Text</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Eyebrow / Badge Text</label>
                    <input type="text"
                           name="eyebrow"
                           value="{{ old('eyebrow') }}"
                           placeholder="e.g. FESTIVE EDIT 2026"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Banner Title</label>
                    <input type="text"
                           name="title"
                           value="{{ old('title') }}"
                           placeholder="e.g. Pure Chanderi Silk Kurta Sets"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Subtitle / Short Tagline</label>
                <input type="text"
                       name="subtitle"
                       value="{{ old('subtitle') }}"
                       placeholder="e.g. Handcrafted elegance for celebrations. Free shipping on orders above ₹799."
                       class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Link Path</label>
                    <input type="text"
                           name="link_path"
                           value="{{ old('link_path') }}"
                           placeholder="e.g. /kurta-set"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                    <p class="text-[0.6875rem] text-slate-400 mt-1">Must start with slash (e.g. /kurta-set)</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">CTA Button Label</label>
                    <input type="text"
                           name="cta_label"
                           value="{{ old('cta_label') }}"
                           placeholder="e.g. Explore Collection"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Overlay Word Mode</label>
                    <select name="word_mode" class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                        <option value="ON_PICTURE" {{ old('word_mode') === 'ON_PICTURE' ? 'selected' : '' }}>On Picture (Dark Text)</option>
                        <option value="LIGHT_ON_PICTURE" {{ old('word_mode') === 'LIGHT_ON_PICTURE' ? 'selected' : '' }}>Light On Picture (White Text)</option>
                        <option value="ON_LIGHTENED" {{ old('word_mode') === 'ON_LIGHTENED' ? 'selected' : '' }}>On Lightened (Subtle Frosted)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Images Upload Card -->
        <div class="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Banner Images</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Desktop Image -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">
                        Desktop Image (Recommended: 1920x600 px)
                    </label>
                    <input type="file"
                           name="desktop_image"
                           accept="image/jpeg,image/png,image/webp,image/avif"
                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
                    <div class="mt-2">
                        <label class="block text-[0.6875rem] text-slate-600 mb-0.5">Desktop Image Alt Text</label>
                        <input type="text"
                               name="image_alt_text"
                               value="{{ old('image_alt_text') }}"
                               placeholder="e.g. Festive Kurta Sets Banner"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                    </div>
                </div>

                <!-- Mobile Image -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">
                        Mobile Image (Recommended: 750x750 or 750x900 px)
                    </label>
                    <input type="file"
                           name="mobile_image"
                           accept="image/jpeg,image/png,image/webp,image/avif"
                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800">
                    <div class="mt-2">
                        <label class="block text-[0.6875rem] text-slate-600 mb-0.5">Mobile Image Alt Text</label>
                        <input type="text"
                               name="mobile_alt_text"
                               value="{{ old('mobile_alt_text') }}"
                               placeholder="e.g. Festive Kurta Sets Mobile Banner"
                               class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Position & Scheduling Card -->
        <div class="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Position &amp; Schedule</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Display Position (0 = First)</label>
                    <input type="number"
                           name="position"
                           value="{{ old('position', 0) }}"
                           min="0"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Starts At (Optional)</label>
                    <input type="datetime-local"
                           name="starts_at"
                           value="{{ old('starts_at') }}"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ends At (Optional)</label>
                    <input type="datetime-local"
                           name="ends_at"
                           value="{{ old('ends_at') }}"
                           class="w-full text-xs rounded-lg border-slate-300 focus:border-amber-500 focus:ring-amber-500">
                </div>
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                           class="rounded text-amber-500 focus:ring-amber-500 size-4">
                    <span class="text-xs font-semibold text-slate-800">Banner is Active immediately</span>
                </label>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.banners.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-xs font-medium hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-amber-500 text-slate-950 font-bold text-xs uppercase tracking-wider hover:bg-amber-400 transition-colors shadow-sm">
                Save Banner
            </button>
        </div>
    </form>

</div>
@endsection
