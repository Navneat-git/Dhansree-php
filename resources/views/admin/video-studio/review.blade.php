@extends('layouts.admin')

@section('page_title', 'Video Studio - Quality Review')
@section('title', 'Quality Control Review - Video #' . $video->id . ' | Dhansree Admin')

@section('content')
<div class="space-y-6" x-data="qcReviewApp()">

    <!-- Header Block -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.video-studio.index') }}"
                   class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-black text-slate-900 tracking-tight">Quality Control Review</h1>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-mono font-bold bg-slate-100 text-slate-800 border border-slate-200">
                            #{{ $video->id }}
                        </span>
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $video->video_type_badge['class'] }}">
                            {{ $video->video_type_badge['text'] }}
                        </span>
                        <span class="text-xs font-bold px-2.5 py-0.5 rounded-full flex items-center gap-1.5 {{ $video->approval_badge['class'] }}">
                            <span style="width: 6px; height: 6px;" class="w-1.5 h-1.5 rounded-full {{ $video->approval_badge['dot'] }}"></span>
                            <span>{{ $video->approval_badge['text'] }}</span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Verify garment fidelity, fabric drape, and model motion against the genuine product catalog reference before approval.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('admin.video-studio.download', [$video, 'format' => 'reel']) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-xs transition-colors"
                   title="Download Watch in Motion Reel (Storefront Style 9:16 MP4 for Instagram & Facebook Reels)">
                    <svg class="size-4 text-slate-950" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Download Reel (Watch in Motion)</span>
                </a>
                @if($video->video_url)
                <a href="{{ route('admin.video-studio.download', $video) }}"
                   style="background-color: #0f172a !important; color: #ffffff !important;"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold shadow-xs hover:opacity-90 transition-opacity">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Download Raw MP4</span>
                </a>
                @endif
                <a href="{{ route('admin.video-studio.index') }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300 transition-colors">
                    Back to Studio
                </a>
            </div>
        </div>
    </div>

    <!-- Rejection Banner if currently rejected -->
    @if($video->isRejected())
    <div class="rounded-xl border border-rose-300 bg-rose-50 p-4 text-rose-950 shadow-xs flex items-start gap-3">
        <svg class="size-5 shrink-0 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
        </svg>
        <div class="text-xs leading-relaxed">
            <p class="font-bold text-rose-950">This video is currently marked as Rejected</p>
            <p class="mt-0.5 text-rose-800"><strong>Reason:</strong> {{ $video->rejection_reason ?: 'Garment fidelity or model motion quality issues.' }}</p>
            @if($video->reviewer)
            <p class="text-[11px] text-rose-700 mt-0.5">Reviewed by {{ $video->reviewer->name }} on {{ $video->reviewed_at?->format('M d, Y H:i') }}</p>
            @endif
        </div>
    </div>
    @elseif($video->isApproved())
    <div class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-950 shadow-xs flex items-start gap-3">
        <svg class="size-5 shrink-0 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <div class="text-xs leading-relaxed">
            <p class="font-bold text-emerald-950">Approved for Dhansree Store &amp; Marketing Campaigns</p>
            <p class="mt-0.5 text-emerald-800">This video has passed garment fidelity QC and is approved for Instagram Reels, Facebook Ads, and Product Page embeds.</p>
            @if($video->reviewer)
            <p class="text-[11px] text-emerald-700 mt-0.5">Approved by {{ $video->reviewer->name }} on {{ $video->reviewed_at?->format('M d, Y H:i') }}</p>
            @endif
        </div>
    </div>
    @endif

    <!-- Side-by-Side Comparison Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Column: Source of Truth (Genuine Catalog Product & Images) (5 cols) -->
        <div class="lg:col-span-5 space-y-5">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-amber-500"></span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Source of Truth</h2>
                    </div>
                    <span class="text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                        Original Product
                    </span>
                </div>

                <!-- Main Reference Image -->
                <div class="mt-4">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Primary Reference Image</p>
                    <div class="relative rounded-xl border-2 border-amber-400/80 bg-slate-950 overflow-hidden aspect-[3/4] flex items-center justify-center shadow-sm">
                        @php
                            $primaryImgUrl = $video->primaryImage?->url ?? $video->product?->primary_image_url;
                        @endphp
                        @if($primaryImgUrl)
                        <img src="{{ $primaryImgUrl }}"
                             alt="{{ $video->product?->name ?? 'Reference' }}"
                             class="w-full h-full object-contain">
                        @else
                        <div class="text-slate-500 text-xs text-center p-6">
                            No primary image found.
                        </div>
                        @endif

                        <div class="absolute bottom-2 left-2 right-2 px-2.5 py-1.5 rounded-lg bg-black/70 backdrop-blur-sm text-white text-[11px] flex items-center justify-between">
                            <span class="font-bold truncate">{{ $video->product?->name }}</span>
                            <span class="text-amber-300 font-mono font-bold">{{ $video->product?->formatted_price }}</span>
                        </div>
                    </div>
                </div>

                <!-- Product Details Box -->
                <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Product Name:</span>
                        <span class="font-bold text-slate-900 text-right truncate max-w-[200px]">{{ $video->product?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">SKU:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $video->product?->sku ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Category:</span>
                        <span class="font-medium text-slate-800">{{ $video->product?->category?->name ?? 'Ethnic Wear' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Catalog Price:</span>
                        <span class="font-bold text-amber-900">{{ $video->product?->formatted_price ?? 'N/A' }}</span>
                    </div>
                </div>

                <!-- Genuine Images Strip -->
                @if($images->isNotEmpty())
                <div class="mt-4">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">All Catalog Reference Angles</p>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach($images as $img)
                        <div class="relative rounded-lg border border-slate-200 overflow-hidden aspect-square bg-slate-100 cursor-pointer hover:border-amber-500 transition-colors"
                             @click="activeRefImage = '{{ $img->url }}'">
                            <img src="{{ $img->url }}" class="w-full h-full object-cover">
                            @if($video->primary_image_id === $img->id)
                            <span class="absolute top-1 left-1 bg-amber-500 text-slate-950 font-black text-[9px] px-1 py-0.2 rounded shadow-2xs">
                                Primary
                            </span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Fidelity Directive Warning -->
            <div class="p-4 rounded-xl border border-amber-300 bg-amber-50 text-xs text-amber-950 leading-relaxed space-y-1.5 shadow-2xs">
                <p class="font-black text-amber-950 flex items-center gap-1.5">
                    <svg class="size-4 text-amber-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    Strict Garment Fidelity Rule
                </p>
                <p class="text-amber-900 text-[11px]">
                    The original product images represent the physical inventory in our warehouse. The AI video must reflect the real garment without altering neckline embroidery, sleeve length, or color tone.
                </p>
            </div>
        </div>

        <!-- Right Column: AI-Generated Video Player & Review Decision Form (7 cols) -->
        <div class="lg:col-span-7 space-y-5">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-purple-500"></span>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">AI-Generated Fashion Footage</h2>
                    </div>
                    <span class="text-[11px] font-bold text-purple-800 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">
                        {{ $video->aspect_ratio_label }}
                    </span>
                </div>

                <!-- Video Player Container -->
                <div class="mt-4 bg-slate-950 rounded-xl overflow-hidden flex items-center justify-center shadow-inner relative"
                     style="max-height: 520px; aspect-ratio: {{ $video->aspect_ratio === '1:1' ? '1/1' : ($video->aspect_ratio === '4:5' ? '4/5' : '9/16') }}; margin: 0 auto; max-width: 380px;">
                    @if($video->video_url)
                    <video src="{{ $video->video_url }}"
                           controls
                           autoplay
                           loop
                           playsinline
                           class="w-full h-full object-contain"></video>
                    @else
                    <div class="text-slate-500 text-xs p-8 text-center">
                        Video stream not available.
                    </div>
                    @endif
                </div>

                <!-- Video Metadata Card -->
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Mode</span>
                        <span class="font-bold text-slate-900 text-[11px]">{{ $video->video_type_badge['text'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">AI Model</span>
                        <span class="font-mono font-bold text-slate-900 text-[11px] truncate block">{{ $video->ai_model ?: 'veo-2.0' }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Duration</span>
                        <span class="font-bold text-slate-900 text-[11px]">{{ $video->formatted_duration }} ({{ $video->width }}×{{ $video->height }})</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">API Cost</span>
                        <span class="font-bold text-purple-700 text-[11px]">~${{ number_format($video->actual_cost ?: $video->estimated_cost ?: 0.15, 2) }} USD</span>
                    </div>
                </div>

                <!-- Prompt Details Accordion -->
                <div class="mt-4 border border-slate-200 rounded-xl overflow-hidden" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                            class="w-full px-4 py-2.5 bg-slate-50 text-left flex items-center justify-between text-xs font-bold text-slate-700 hover:bg-slate-100 transition-colors">
                        <span class="flex items-center gap-2">
                            <svg class="size-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                            </svg>
                            <span>Generation Prompt &amp; Settings</span>
                        </span>
                        <svg class="size-4 transform transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="p-4 bg-white space-y-3 text-xs">
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Style Template</span>
                            <p class="font-bold text-slate-900">{{ ucwords(str_replace('_', ' ', $video->prompt_template ?? 'runway_walk')) }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Veo Generation Prompt</span>
                            <div class="p-2.5 rounded-lg bg-slate-900 text-slate-100 font-mono text-[11px] leading-relaxed max-h-40 overflow-y-auto">
                                {{ $video->prompt ?: 'Default prompt' }}
                            </div>
                        </div>
                        @if($video->negative_prompt)
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Negative Prompt</span>
                            <div class="p-2.5 rounded-lg bg-slate-100 text-slate-700 font-mono text-[10px] leading-relaxed">
                                {{ $video->negative_prompt }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Quality Control Checklist -->
                <div class="mt-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg class="size-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>Quality Control Checklist</span>
                    </h3>
                    <p class="text-[11px] text-slate-600 mt-0.5">Confirm the following criteria before granting marketing approval:</p>

                    <div class="mt-3 space-y-2.5 text-xs text-slate-800">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" x-model="checklist.garment_match" class="size-4 mt-0.5 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                            <span><strong>Garment Silhouette &amp; Neckline:</strong> The model wears the exact suit cut, neckline design, and sleeve length shown in the catalog image.</span>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" x-model="checklist.color_fidelity" class="size-4 mt-0.5 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                            <span><strong>Color &amp; Fabric Fidelity:</strong> Colors match the genuine product; embroidery, border lace, and prints are preserved without hallucinated patterns.</span>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" x-model="checklist.dupatta_motion" class="size-4 mt-0.5 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                            <span><strong>Dupatta &amp; Fabric Flow:</strong> Fabric drapes naturally and flows without morphing, flickering, or disappearing limbs.</span>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" x-model="checklist.model_quality" class="size-4 mt-0.5 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                            <span><strong>Model Realism:</strong> Natural human motion, facial fidelity, and professional cinematic fashion campaign presentation.</span>
                        </label>
                        @if($video->video_type === 'ai_fashion_branded')
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" x-model="checklist.branding_legible" class="size-4 mt-0.5 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300">
                            <span><strong>Branding &amp; Overlays:</strong> Dhansree logo, selling price, and closing call-to-action screen are sharp, legible, and accurately styled.</span>
                        </label>
                        @endif
                    </div>
                </div>

                <!-- Review Decisions -->
                <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">

                    <!-- Approve Form -->
                    <form method="POST" action="{{ route('admin.video-studio.approve', $video) }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="checklist[garment_match]" :value="checklist.garment_match ? 1 : 0">
                        <input type="hidden" name="checklist[color_fidelity]" :value="checklist.color_fidelity ? 1 : 0">
                        <input type="hidden" name="checklist[dupatta_motion]" :value="checklist.dupatta_motion ? 1 : 0">
                        <input type="hidden" name="checklist[model_quality]" :value="checklist.model_quality ? 1 : 0">
                        <button type="submit"
                                style="background-color: #059669 !important; color: #ffffff !important;"
                                class="w-full py-2.5 px-4 rounded-xl font-bold text-xs shadow-sm hover:opacity-95 transition-opacity flex items-center justify-center gap-2">
                            <svg class="size-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            <span>Approve Video for Public Use</span>
                        </button>
                    </form>

                    <!-- Reject Modal Trigger -->
                    <button type="button" @click="showRejectModal = true"
                            style="background-color: #dc2626 !important; color: #ffffff !important;"
                            class="py-2.5 px-4 rounded-xl font-bold text-xs shadow-sm hover:opacity-95 transition-opacity flex items-center justify-center gap-1.5">
                        <svg class="size-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        <span>Reject</span>
                    </button>

                    <!-- Regenerate Modal Trigger -->
                    <button type="button" @click="showRegenModal = true"
                            class="py-2.5 px-4 rounded-xl font-bold text-xs bg-slate-900 text-white hover:bg-slate-800 transition-colors flex items-center justify-center gap-1.5">
                        <svg class="size-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Regenerate</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-show="showRejectModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs"
         @keydown.escape.window="showRejectModal = false">
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6" @click.outside="showRejectModal = false">
            <h3 class="text-sm font-black text-slate-900">Reject AI Video</h3>
            <p class="text-xs text-slate-600 mt-1">Please provide constructive feedback so the model or prompt can be adjusted.</p>

            <form method="POST" action="{{ route('admin.video-studio.reject', $video) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Rejection Reason</label>
                    <textarea name="reason" rows="3" required
                              class="w-full text-xs p-3 rounded-lg border border-slate-300 focus:border-rose-500 focus:ring-1 focus:ring-rose-500"
                              placeholder="e.g. Neckline embroidery did not match reference image; dupatta flow unnatural at 0:03..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showRejectModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                    <button type="submit" style="background-color: #dc2626 !important; color: #ffffff !important;" class="px-4 py-2 text-xs font-bold rounded-lg">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Regenerate Modal -->
    <div x-show="showRegenModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs"
         @keydown.escape.window="showRegenModal = false">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl p-6" @click.outside="showRegenModal = false">
            <h3 class="text-sm font-black text-slate-900">Regenerate AI Fashion Video</h3>
            <p class="text-xs text-slate-600 mt-1">You can fine-tune the prompt instructions before sending back to Google Veo.</p>

            <form method="POST" action="{{ route('admin.video-studio.regenerate', $video) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Fine-Tuned Prompt</label>
                    <textarea name="prompt" rows="5" required
                              class="w-full text-xs p-3 rounded-lg border border-slate-300 focus:border-amber-600 focus:ring-1 focus:ring-amber-600 font-mono">{{ $video->prompt }}</textarea>
                </div>

                <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-[11px] text-amber-900 leading-relaxed">
                    <strong>Note:</strong> Regenerating will invoke the Google Veo API and incur estimated billing (~$0.15 USD).
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showRegenModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
                    <button type="submit" style="background-color: #d97706 !important; color: #ffffff !important;" class="px-5 py-2 text-xs font-bold rounded-lg">
                        Regenerate Video Now
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function qcReviewApp() {
    return {
        showRejectModal: false,
        showRegenModal: false,
        checklist: {
            garment_match: true,
            color_fidelity: true,
            dupatta_motion: true,
            model_quality: true,
            branding_legible: true,
        }
    };
}
</script>
@endsection
