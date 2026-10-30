<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Services\VideoStudio\FashionVideoPromptService;
use App\Services\VideoStudio\FfmpegService;
use App\Services\VideoStudio\GoogleVideoGenerationService;
use App\Services\VideoStudio\VideoStudioService;
use App\Support\Rbac\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminVideoStudioWebController extends Controller
{
    public function __construct(
        protected VideoStudioService $videoStudioService,
        protected FfmpegService $ffmpegService,
        protected GoogleVideoGenerationService $googleVideoService,
        protected FashionVideoPromptService $promptService
    ) {}

    /**
     * Video Studio Dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorizeAccess(Permissions::BANNER_VIEW, Permissions::PRODUCT_VIEW);

        $tableExists = $this->ensureTableExists();

        try {
            if ($tableExists) {
                $query = ProductVideo::with(['product', 'creator', 'reviewer'])->latest();

                // Search filter (Product name, SKU, or Video title)
                if ($search = trim((string) $request->input('search'))) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhereHas('product', function ($pq) use ($search) {
                              $pq->where('name', 'like', "%{$search}%")
                                 ->orWhere('slug', 'like', "%{$search}%")
                                 ->orWhereHas('variants', fn($vq) => $vq->where('sku', 'like', "%{$search}%"));
                          });
                    });
                }

                // Status filter
                if ($status = $request->input('status')) {
                    if ($status !== 'all') {
                        $query->where('status', $status);
                    }
                }

                // Approval Status filter (Ready for Review, Approved, Rejected)
                if ($approval = $request->input('approval_status')) {
                    if ($approval !== 'all') {
                        $query->where('approval_status', $approval);
                    }
                }

                // Aspect ratio filter
                if ($aspect = $request->input('aspect_ratio')) {
                    if ($aspect !== 'all') {
                        $query->where('aspect_ratio', $aspect);
                    }
                }

                // Template filter
                if ($template = $request->input('template')) {
                    if ($template !== 'all') {
                        $query->where('template', $template);
                    }
                }

                // Video type filter
                if ($type = $request->input('video_type')) {
                    if ($type !== 'all') {
                        $query->where('video_type', $type);
                    }
                }

                $videos = $query->paginate(12)->withQueryString();

                $stats = [
                    'total' => ProductVideo::count(),
                    'completed' => ProductVideo::where('status', 'completed')->count(),
                    'processing' => ProductVideo::whereIn('status', ['queued', 'processing'])->count(),
                    'failed' => ProductVideo::where('status', 'failed')->count(),
                    'pending_review' => ProductVideo::where('approval_status', 'pending_review')->count(),
                    'approved' => ProductVideo::where('approval_status', 'approved')->count(),
                ];
            } else {
                $videos = new LengthAwarePaginator([], 0, 12);
                $stats = [
                    'total' => 0,
                    'completed' => 0,
                    'processing' => 0,
                    'failed' => 0,
                    'pending_review' => 0,
                    'approved' => 0,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Video Studio dashboard query error: ' . $e->getMessage(), ['exception' => $e]);
            $videos = new LengthAwarePaginator([], 0, 12);
            $stats = [
                'total' => 0,
                'completed' => 0,
                'processing' => 0,
                'failed' => 0,
                'pending_review' => 0,
                'approved' => 0,
            ];
            $tableExists = false;
        }

        $ffmpegStatus = [
            'available' => false,
            'version' => null,
        ];
        try {
            $ffmpegStatus = [
                'available' => $this->ffmpegService->isAvailable(),
                'version' => $this->ffmpegService->getVersion(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Video Studio ffmpeg status check: ' . $e->getMessage());
        }

        return view('admin.video-studio.index', [
            'videos' => $videos,
            'stats' => $stats,
            'ffmpegStatus' => $ffmpegStatus,
            'googleVideoConfigured' => $this->googleVideoService->isConfigured(),
            'templates' => config('video_studio.templates', []),
            'aspectRatios' => config('video_studio.aspect_ratios', []),
            'tableMissing' => !$tableExists,
        ]);
    }

    /**
     * Create Video Screen.
     */
    public function create(Request $request): View
    {
        $this->authorizeAccess(Permissions::BANNER_CREATE, Permissions::PRODUCT_CREATE);

        $this->ensureTableExists();

        $selectedProduct = null;
        $selectedProductData = null;
        $availableImagesData = [];

        if ($productId = $request->input('product_id')) {
            $selectedProduct = Product::with(['images', 'category', 'variants'])->find($productId);
            if ($selectedProduct) {
                $imagesData = $selectedProduct->images->map(fn($i) => [
                    'id' => $i->id,
                    'url' => $i->url,
                    'position' => $i->position,
                ])->values()->all();

                $selectedProductData = [
                    'id' => $selectedProduct->id,
                    'name' => $selectedProduct->name,
                    'sku' => $selectedProduct->sku,
                    'price' => $selectedProduct->formatted_price,
                    'primary_image' => $selectedProduct->primary_image_url,
                    'images' => $imagesData,
                ];

                $availableImagesData = $imagesData;
            }
        }

        // Recent products with images for quick selection
        $recentProducts = Product::where('status', ProductStatus::ACTIVE)
            ->has('images')
            ->with(['images' => fn($q) => $q->orderBy('position')->limit(6)])
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.video-studio.create', [
            'selectedProduct' => $selectedProduct,
            'selectedProductData' => $selectedProductData,
            'availableImagesData' => $availableImagesData,
            'recentProducts' => $recentProducts,
            'templates' => config('video_studio.templates', []),
            'aspectRatios' => config('video_studio.aspect_ratios', []),
            'durations' => config('video_studio.durations', [5, 10, 15, 20]),
            'audioTracks' => config('video_studio.audio_tracks', []),
            'aiMotionEnabled' => config('video_studio.ai_motion.enabled', false),
            'ffmpegAvailable' => $this->ffmpegService->isAvailable(),
            'fashionStyles' => $this->promptService->getFashionStyles(),
            'googleVideoConfigured' => $this->googleVideoService->isConfigured(),
            'googleVideoModel' => config('video_studio.google_video.model', 'veo-2.0-generate-001'),
            'estimatedCostPerClip' => (float) config('video_studio.google_video.estimated_cost_per_clip', 0.15),
            'defaultNegativePrompt' => $this->promptService->getNegativePrompt(),
        ]);
    }

    /**
     * AJAX endpoint to preview generated prompt based on product and fashion style.
     */
    public function previewPrompt(Request $request): JsonResponse
    {
        $this->authorizeAccess(Permissions::BANNER_VIEW, Permissions::PRODUCT_VIEW);

        $productId = $request->input('product_id');
        $product = $productId ? Product::with('category')->find($productId) : null;
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Please select a valid product first.'], 404);
        }

        $style = (string) $request->input('style', 'runway_walk');
        $customNotes = $request->input('custom_notes');

        $prompt = $this->promptService->buildPrompt($product, $style, $customNotes);
        $negativePrompt = $this->promptService->getNegativePrompt();

        return response()->json([
            'success' => true,
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt,
            'style' => $style,
        ]);
    }

    /**
     * AJAX endpoint to search active products with usable images.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $this->authorizeAccess(Permissions::BANNER_VIEW, Permissions::PRODUCT_VIEW);

        $query = trim((string) $request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json(['products' => []]);
        }

        $products = Product::where('status', ProductStatus::ACTIVE)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('slug', 'like', "%{$query}%")
                  ->orWhereHas('variants', fn($vq) => $vq->where('sku', 'like', "%{$query}%"));
            })
            ->with(['images' => fn($q) => $q->orderBy('position')])
            ->limit(15)
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->formatted_price,
                    'primary_image' => $product->primary_image_url,
                    'image_count' => $product->images->count(),
                    'images' => $product->images->map(fn($img) => [
                        'id' => $img->id,
                        'url' => $img->url,
                        'position' => $img->position,
                    ]),
                ];
            });

        return response()->json(['products' => $products]);
    }

    /**
     * AJAX endpoint to fetch genuine images of a product.
     */
    public function productImages(Product $product): JsonResponse
    {
        $this->authorizeAccess(Permissions::BANNER_VIEW, Permissions::PRODUCT_VIEW);

        $images = $product->images()
            ->orderBy('position')
            ->get()
            ->map(fn($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'position' => $img->position,
                'is_primary' => (int) $img->position === 0,
            ]);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->formatted_price,
            ],
            'images' => $images,
        ]);
    }

    /**
     * Store new video generation request.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(Permissions::BANNER_CREATE, Permissions::PRODUCT_CREATE);

        $this->ensureTableExists();

        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'title' => 'nullable|string|max:150',
                'video_type' => 'required|in:slideshow,ai_fashion,ai_fashion_branded,ai_motion,imported_branded',
                'template' => 'required|string|in:' . implode(',', array_keys(config('video_studio.templates', []))),
                'aspect_ratio' => 'required|string|in:' . implode(',', array_keys(config('video_studio.aspect_ratios', []))),
                'duration_seconds' => 'required|integer|min:4|max:30',
                'export_preset' => 'nullable|string|in:clean_storefront,social_branded,dual',
                'image_ids' => 'nullable|required_unless:video_type,imported_branded|array',
                'image_ids.*' => 'integer|exists:product_images,id',
                'uploaded_video' => 'nullable|required_if:video_type,imported_branded|file|mimes:mp4,mov,webm,quicktime|max:61440',
                'primary_image_id' => 'nullable|integer|exists:product_images,id',
                'prompt_template' => 'nullable|string',
                'prompt' => 'nullable|string|max:2000',
                'negative_prompt' => 'nullable|string|max:1000',
                'enable_music' => 'nullable|boolean',
                'audio_track' => 'nullable|string',
                'text_overlay' => 'nullable|boolean',
                'show_price' => 'nullable|boolean',
                'call_to_action' => 'nullable|boolean',
                'custom_headline' => 'nullable|string|max:60',
            ], [
                'product_id.required' => 'Please select a product.',
                'image_ids.required_unless' => 'Please select at least one product image for the video.',
                'uploaded_video.required_if' => 'Please upload a raw video file to brand with FFmpeg.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first() ?: 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        // Handle direct raw video import & FFmpeg branding (e.g. from Google Flow)
        if ($validated['video_type'] === 'imported_branded') {
            if (!$request->hasFile('uploaded_video')) {
                $msg = 'Please upload a raw video file from Google Flow or your camera.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }

            try {
                $video = $this->videoStudioService->importAndBrand($validated, $request->file('uploaded_video'), $admin);
                session()->flash('success', "AI fashion video successfully imported and branded with FFmpeg for '{$video->title}'.");

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'video' => [
                            'id' => $video->id,
                            'title' => $video->title,
                            'status' => $video->status,
                            'is_completed' => $video->isCompleted(),
                            'video_url' => $video->video_url,
                        ],
                        'redirect' => route('admin.video-studio.index'),
                        'message' => "Video branded successfully.",
                    ]);
                }

                return redirect()->route('admin.video-studio.index')
                    ->with('success', "Video branded and exported successfully for '{$video->title}'.");
            } catch (\Throwable $e) {
                Log::error('Video import and branding error: ' . $e->getMessage(), ['exception' => $e]);
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Branding failed: ' . $e->getMessage()], 500);
                }
                return back()->withInput()->with('error', 'Branding failed: ' . $e->getMessage());
            }
        }

        // Guard legacy/placeholder AI motion when disabled
        if ($validated['video_type'] === 'ai_motion' && !config('video_studio.ai_motion.enabled', false)) {
            $msg = 'AI Fashion Motion is disabled. Please select the FFmpeg Product Slideshow option or configure an AI video provider in settings.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }

        // Guard Google AI fashion video modes if disabled or quota reached
        if (in_array($validated['video_type'], ['ai_fashion', 'ai_fashion_branded'], true)) {
            $limits = $this->googleVideoService->checkLimits();
            if (!$limits['allowed']) {
                $msg = $limits['reason'] ?? 'AI Fashion Video generation is currently disabled or quota exceeded.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return back()->withInput()->with('error', $msg);
            }
        }

        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $video = $this->videoStudioService->createVideo($validated, $admin);

            $label = $video->isAi() ? 'AI Fashion Shoot' : 'Slideshow';
            session()->flash('success', "Video generation queued for '{$video->title}' ({$label}). Processing has started in background.");

            if ($request->wantsJson() || $request->ajax()) {
                $video->refresh();

                return response()->json([
                    'success' => true,
                    'video' => [
                        'id' => $video->id,
                        'title' => $video->title,
                        'status' => $video->status,
                        'progress' => $video->progress,
                        'is_completed' => $video->isCompleted(),
                        'video_url' => $video->video_url,
                    ],
                    'redirect' => route('admin.video-studio.index'),
                    'status_url' => route('admin.video-studio.status', $video->id),
                    'message' => "Video generation queued for '{$video->title}'.",
                ]);
            }

            return redirect()->route('admin.video-studio.index')
                ->with('success', "Video generation queued for '{$video->title}'. Processing has started in background.");
        } catch (\Throwable $e) {
            Log::error('Video creation error: ' . $e->getMessage(), ['exception' => $e]);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create video request: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withInput()->with('error', 'Failed to create video request: ' . $e->getMessage());
        }
    }

    /**
     * AJAX endpoint to poll live status of a video rendering job.
     */
    public function status(ProductVideo $video): JsonResponse
    {
        $this->authorizeAccess(Permissions::BANNER_VIEW, Permissions::PRODUCT_VIEW);

        return response()->json([
            'id' => $video->id,
            'status' => $video->status,
            'progress' => $video->progress,
            'is_completed' => $video->isCompleted(),
            'is_failed' => $video->isFailed(),
            'is_processing' => $video->isProcessing(),
            'video_url' => $video->video_url,
            'thumbnail_url' => $video->thumbnail_url,
            'file_size' => $video->formatted_file_size,
            'error_message' => $video->error_message,
            'badge' => $video->status_badge,
        ]);
    }

    /**
     * Download rendered MP4 video.
     */
    public function download(ProductVideo $video): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeAccess(Permissions::BANNER_VIEW, Permissions::PRODUCT_VIEW);

        $format = request('format'); // 'webm', 'branded', or default
        $disk = config('video_studio.disk', 'public');
        $settings = $video->render_settings ?? [];

        $relativePath = $video->video_path;
        $cleanTitle = Str::slug($video->title ?: 'dhansree-product-video');
        $aspect = str_replace(':', 'x', $video->aspect_ratio);
        $downloadName = "Dhansree-{$cleanTitle}-{$aspect}.mp4";
        $contentType = 'video/mp4';

        if ($format === 'webm' && !empty($settings['webm_path']) && Storage::disk($disk)->exists($settings['webm_path'])) {
            $relativePath = $settings['webm_path'];
            $downloadName = "Dhansree-{$cleanTitle}-clean-{$aspect}.webm";
            $contentType = 'video/webm';
        } elseif ($format === 'branded' && !empty($settings['branded_video_path']) && Storage::disk($disk)->exists($settings['branded_video_path'])) {
            $relativePath = $settings['branded_video_path'];
            $downloadName = "Dhansree-{$cleanTitle}-branded-social-{$aspect}.mp4";
            $contentType = 'video/mp4';
        }

        if (!$relativePath || !Storage::disk($disk)->exists($relativePath)) {
            return back()->with('error', 'Requested video file is not available on disk.');
        }

        $absolutePath = Storage::disk($disk)->path($relativePath);

        return response()->download($absolutePath, $downloadName, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * Quality Control Review screen for AI-generated fashion videos.
     */
    public function review(ProductVideo $video): View
    {
        $this->authorizeAccess(Permissions::BANNER_VIEW, Permissions::PRODUCT_VIEW);

        $video->load(['product.images', 'creator', 'reviewer', 'primaryImage']);

        return view('admin.video-studio.review', [
            'video' => $video,
            'product' => $video->product,
            'images' => $video->product?->images ?? collect(),
            'fashionStyles' => $this->promptService->getFashionStyles(),
        ]);
    }

    /**
     * Approve AI-generated fashion video for public store and advertising use.
     */
    public function approve(ProductVideo $video, Request $request): RedirectResponse
    {
        $this->authorizeAccess(Permissions::BANNER_UPDATE, Permissions::PRODUCT_UPDATE);

        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();

        $checklist = $request->input('checklist', []);
        $this->videoStudioService->approve($video, $admin, is_array($checklist) ? $checklist : []);

        return redirect()->route('admin.video-studio.index')
            ->with('success', "AI Fashion Video #{$video->id} ({$video->title}) approved successfully and approved for store & marketing use.");
    }

    /**
     * Reject AI video with specific feedback.
     */
    public function reject(ProductVideo $video, Request $request): RedirectResponse
    {
        $this->authorizeAccess(Permissions::BANNER_UPDATE, Permissions::PRODUCT_UPDATE);

        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();

        $reason = trim((string) $request->input('reason', 'Quality does not meet brand guidelines.'));
        $checklist = $request->input('checklist', []);

        $this->videoStudioService->reject($video, $admin, $reason, is_array($checklist) ? $checklist : []);

        return redirect()->route('admin.video-studio.index')
            ->with('warning', "AI Fashion Video #{$video->id} rejected. Reason: {$reason}");
    }

    /**
     * Re-generate AI fashion video with updated prompt.
     */
    public function regenerate(ProductVideo $video, Request $request): RedirectResponse
    {
        $this->authorizeAccess(Permissions::BANNER_CREATE, Permissions::PRODUCT_CREATE);

        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();

        $prompt = $request->input('prompt');
        $this->videoStudioService->regenerate($video, $admin, $prompt);

        return redirect()->route('admin.video-studio.index')
            ->with('success', "AI Fashion Video #{$video->id} has been re-queued for generation with updated prompt.");
    }

    /**
     * Retry failed video render.
     */
    public function retry(ProductVideo $video): RedirectResponse
    {
        $this->authorizeAccess(Permissions::BANNER_UPDATE, Permissions::PRODUCT_UPDATE);

        $this->videoStudioService->retry($video);

        return back()->with('success', "Video #{$video->id} has been re-queued for rendering.");
    }

    /**
     * Delete video record and rendered files without touching source product images.
     */
    public function destroy(ProductVideo $video): RedirectResponse
    {
        $this->authorizeAccess(Permissions::BANNER_DELETE, Permissions::PRODUCT_DELETE);

        $this->videoStudioService->delete($video);

        return redirect()->route('admin.video-studio.index')
            ->with('success', 'Video deleted successfully. Source product images remain intact.');
    }

    /**
     * Run migrations if not yet applied.
     */
    public function migrateDb(): RedirectResponse
    {
        $this->authorizeAccess(Permissions::BANNER_UPDATE, Permissions::PRODUCT_UPDATE);
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
            return redirect()->route('admin.video-studio.index')
                ->with('success', 'Database migration executed: ' . (trim($output) ?: 'Tables are up to date.'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.video-studio.index')
                ->with('error', 'Database migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Ensure product_videos table exists with auto-migration.
     */
    protected function ensureTableExists(): bool
    {
        try {
            if (!Schema::hasTable('product_videos')) {
                Artisan::call('migrate', ['--force' => true]);
            }
            return Schema::hasTable('product_videos');
        } catch (\Throwable $e) {
            Log::warning('Video studio auto-migration notice: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper to enforce RBAC permissions.
     */
    protected function authorizeAccess(string ...$permissions): void
    {
        /** @var AdminUser|null $admin */
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            abort(401);
        }

        if ($admin->hasSuperAdmin()) {
            return;
        }

        foreach ($permissions as $permission) {
            if ($admin->hasPermission($permission)) {
                return;
            }
        }

        // Default: allow viewing dashboard for all authenticated admins
        if (in_array(Permissions::BANNER_VIEW, $permissions, true) || in_array(Permissions::PRODUCT_VIEW, $permissions, true)) {
            return;
        }

        abort(403, 'You do not have permission to access Dhansree Video Studio.');
    }
}
