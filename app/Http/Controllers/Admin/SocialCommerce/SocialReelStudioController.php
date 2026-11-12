<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SocialCommerce;

use App\Http\Controllers\Controller;
use App\Models\SocialMediaAsset;
use App\Models\SocialProduct;
use App\Models\SocialPublishingQueue;
use App\Models\SocialReel;
use App\Models\SocialReelItem;
use App\Models\SocialReelTemplate;
use App\Services\SocialCommerce\CaptionGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SocialReelStudioController extends Controller
{
    public function __construct(
        protected CaptionGeneratorService $captionService
    ) {}

    /**
     * List all reels with filtering and status.
     */
    public function index(Request $request): View
    {
        $query = SocialReel::with(['items.mediaAsset', 'items.socialProduct', 'approver', 'creator'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('template_code')) {
            $query->where('template_code', $request->input('template_code'));
        }

        $reels = $query->paginate(15)->withQueryString();
        $templates = SocialReelTemplate::where('is_active', true)->get();

        return view('admin.social-commerce.reels.index', [
            'reels' => $reels,
            'templates' => $templates,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Show form to compose a new Reel.
     */
    public function create(Request $request): View
    {
        $templates = SocialReelTemplate::where('is_active', true)->get();
        $products = SocialProduct::with(['mediaAssets', 'supplier'])
            ->whereIn('availability_status', ['NEW', 'AVAILABLE'])
            ->latest('id')
            ->take(40)
            ->get();

        $selectedProductId = $request->input('product_id');
        $preselectedProduct = $selectedProductId ? SocialProduct::with('mediaAssets')->find($selectedProductId) : null;

        // Suggested initial caption
        $suggestedCaption = '';
        if ($preselectedProduct) {
            $cap = $this->captionService->generateCaption($preselectedProduct, 'new_arrival', 'INSTAGRAM');
            $suggestedCaption = $cap['full_caption'];
        }

        return view('admin.social-commerce.reels.create', [
            'templates' => $templates,
            'products' => $products,
            'preselectedProduct' => $preselectedProduct,
            'suggestedCaption' => $suggestedCaption,
        ]);
    }

    /**
     * Save reel configuration and queue for rendering.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:200',
            'template_code' => 'required|string|exists:social_reel_templates,code',
            'duration_seconds' => 'required|integer|min:5|max:60',
            'caption' => 'nullable|string',
            'hashtags' => 'nullable|string',
            'audio_track' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.media_asset_id' => 'required|integer|exists:social_media_assets,id',
            'items.*.social_product_id' => 'nullable|integer|exists:social_products,id',
            'items.*.headline' => 'nullable|string|max:150',
            'items.*.price_text' => 'nullable|string|max:50',
            'items.*.display_seconds' => 'nullable|numeric|min:0.5|max:10',
        ]);

        $adminUser = $request->attributes->get('admin_user');
        $adminUserId = $adminUser?->id;

        $reel = DB::transaction(function () use ($request, $adminUserId) {
            $reelCode = 'REEL-' . strtoupper(Str::random(8));

            $reel = SocialReel::create([
                'reel_code' => $reelCode,
                'title' => trim($request->input('title')),
                'template_code' => $request->input('template_code'),
                'aspect_ratio' => '9:16',
                'width' => 1080,
                'height' => 1920,
                'duration_seconds' => (int) $request->input('duration_seconds', 15),
                'status' => 'DRAFT',
                'caption' => $request->input('caption'),
                'hashtags' => $request->input('hashtags'),
                'audio_track' => $request->input('audio_track'),
                'created_by_admin_user_id' => $adminUserId,
            ]);

            $items = $request->input('items', []);
            foreach ($items as $idx => $item) {
                SocialReelItem::create([
                    'social_reel_id' => $reel->id,
                    'social_product_id' => !empty($item['social_product_id']) ? (int) $item['social_product_id'] : null,
                    'media_asset_id' => (int) $item['media_asset_id'],
                    'position' => $idx,
                    'headline' => !empty($item['headline']) ? trim($item['headline']) : null,
                    'price_text' => !empty($item['price_text']) ? trim($item['price_text']) : null,
                    'display_seconds' => !empty($item['display_seconds']) ? (float) $item['display_seconds'] : 2.50,
                ]);
            }

            return $reel;
        });

        return redirect()->route('admin.social-commerce.reels.show', $reel)
            ->with('success', "Reel {$reel->reel_code} created as Draft. Review and approve before publishing.");
    }

    /**
     * View reel details, preview mockup, and approval actions.
     */
    public function show(SocialReel $reel): View
    {
        $reel->load(['items.mediaAsset', 'items.socialProduct', 'approver', 'creator', 'template']);

        return view('admin.social-commerce.reels.show', [
            'reel' => $reel,
        ]);
    }

    /**
     * Approve reel (Safe Publishing Approval requirement).
     */
    public function approve(Request $request, SocialReel $reel): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');
        $adminUserId = $adminUser?->id;

        $reel->update([
            'status' => 'APPROVED',
            'approved_by_admin_user_id' => $adminUserId,
        ]);

        return redirect()->back()->with('success', "Reel {$reel->reel_code} approved! You can now send it to the Publishing Queue.");
    }

    /**
     * Send approved reel to Publishing Queue.
     */
    public function pushToQueue(Request $request, SocialReel $reel): RedirectResponse
    {
        $request->validate([
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'in:INSTAGRAM,FACEBOOK',
            'scheduled_at' => 'nullable|date',
            'caption_instagram' => 'nullable|string',
            'caption_facebook' => 'nullable|string',
        ]);

        $adminUser = $request->attributes->get('admin_user');
        $adminUserId = $adminUser?->id;

        $queue = SocialPublishingQueue::create([
            'social_reel_id' => $reel->id,
            'title' => $reel->title,
            'content_type' => 'REEL',
            'caption_instagram' => $request->input('caption_instagram') ?: $reel->caption,
            'caption_facebook' => $request->input('caption_facebook') ?: $reel->caption,
            'platforms' => $request->input('platforms'),
            'status' => $request->filled('scheduled_at') ? 'SCHEDULED' : 'APPROVED',
            'scheduled_at' => $request->input('scheduled_at'),
            'created_by_admin_user_id' => $adminUserId,
        ]);

        return redirect()->route('admin.social-commerce.queue.index')
            ->with('success', "Reel queued for publishing across " . implode(', ', $request->input('platforms')));
    }

    /**
     * Trigger background video generation job.
     */
    public function render(SocialReel $reel): RedirectResponse
    {
        $reel->update([
            'status' => 'RENDERING',
            'progress' => 5,
            'error_message' => null,
        ]);

        \App\Jobs\RenderSocialReelJob::dispatch($reel->id);

        return redirect()->back()->with('success', "Video rendering initiated for {$reel->reel_code}. Process runs in background.");
    }

    /**
     * Delete reel.
     */
    public function destroy(SocialReel $reel): RedirectResponse
    {
        $reel->delete();
        return redirect()->route('admin.social-commerce.reels.index')
            ->with('success', 'Reel deleted.');
    }
}
