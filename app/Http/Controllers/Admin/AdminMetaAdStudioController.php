<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetaAdCreative;
use App\Models\Product;
use App\Models\SocialSetting;
use App\Services\Marketing\MetaAdCopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMetaAdStudioController extends Controller
{
    public function __construct(
        protected MetaAdCopyService $copyService
    ) {}

    /**
     * Display the Meta Ad Studio interface.
     */
    public function index(Request $request): View
    {
        // 1. Get all active products for the selector
        $products = Product::active()
            ->whereNull('archived_at')
            ->with(['category', 'images', 'productAttributes', 'options.values'])
            ->orderBy('name')
            ->get();

        // 2. Select product from query or fallback to first
        $selectedProductId = $request->query('product_id');
        $selectedProduct = $products->firstWhere('id', (int) $selectedProductId) ?: $products->first();

        // 3. Pre-generate ad suite for selected product if present
        $initialAdSuite = null;
        if ($selectedProduct) {
            $defaultProvider = SocialSetting::get('default_ai_provider', 'smart_ai');
            $initialAdSuite = $this->copyService->generateAdSuite($selectedProduct, [
                'provider' => $defaultProvider,
                'angle' => 'all',
                'utm_campaign' => SocialSetting::get('default_utm_campaign', 'dhansree_meta_ads'),
            ]);
        }

        // 4. Saved ad creatives
        $savedCreatives = MetaAdCreative::with('product')
            ->latest()
            ->paginate(15);

        // 5. Configured settings
        $settings = [
            'gemini_api_key' => SocialSetting::get('gemini_api_key', ''),
            'openai_api_key' => SocialSetting::get('openai_api_key', ''),
            'default_ai_provider' => SocialSetting::get('default_ai_provider', 'smart_ai'),
            'default_utm_campaign' => SocialSetting::get('default_utm_campaign', 'dhansree_meta_ads'),
        ];

        return view('admin.marketing.meta-ads.index', [
            'products' => $products,
            'selectedProduct' => $selectedProduct,
            'initialAdSuite' => $initialAdSuite,
            'savedCreatives' => $savedCreatives,
            'settings' => $settings,
            'angles' => MetaAdCopyService::ANGLES,
        ]);
    }

    /**
     * AJAX endpoint to fetch product metadata when selection changes.
     */
    public function productData(Product $product): JsonResponse
    {
        $metadata = $this->copyService->extractProductMetadata($product);
        return response()->json([
            'success' => true,
            'product' => $metadata,
        ]);
    }

    /**
     * AJAX endpoint to generate Meta ad copy using Smart AI, Gemini, or ChatGPT.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'provider' => 'nullable|string|in:smart_ai,gemini,chatgpt',
            'angle' => 'nullable|string',
            'custom_instructions' => 'nullable|string|max:500',
            'utm_campaign' => 'nullable|string|max:100',
            'image_url' => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $suite = $this->copyService->generateAdSuite($product, [
            'provider' => $validated['provider'] ?? 'smart_ai',
            'angle' => $validated['angle'] ?? 'all',
            'custom_instructions' => $validated['custom_instructions'] ?? '',
            'utm_campaign' => $validated['utm_campaign'] ?? '',
            'image_url' => $validated['image_url'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $suite,
        ]);
    }

    /**
     * Save an ad creative to the library for future campaigns.
     */
    public function save(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'campaign_name' => 'nullable|string|max:191',
                'ad_name' => 'nullable|string|max:191',
                'angle' => 'nullable|string|max:50',
                'primary_text' => 'nullable|string',
                'headline' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:255',
                'cta' => 'nullable|string|max:50',
                'destination_url' => 'nullable|string',
                'utm_campaign' => 'nullable|string|max:100',
                'creative_image_url' => 'nullable|string',
                'ai_provider' => 'nullable|string|max:50',
            ]);

            $product = Product::findOrFail($validated['product_id']);
            $angle = $validated['angle'] ?? 'festive';
            $utmCampaign = !empty($validated['utm_campaign']) ? $validated['utm_campaign'] : 'dhansree_meta_ads';

            // Destination URL fallback
            $productUrl = url('/product/' . ($product->slug ?: $product->id));
            $destinationUrl = !empty($validated['destination_url'])
                ? $validated['destination_url']
                : $this->copyService->buildDestinationUrl($productUrl, $product->slug ?: (string) $product->id, $utmCampaign, $angle);

            // Copy fallbacks if missing
            $primaryText = !empty($validated['primary_text'])
                ? $validated['primary_text']
                : $this->copyService->generateSmartCopy($product, $angle)['primary_text'];

            $headline = !empty($validated['headline'])
                ? $validated['headline']
                : $product->name;

            $creative = MetaAdCreative::create([
                'product_id' => $product->id,
                'campaign_name' => !empty($validated['campaign_name']) ? $validated['campaign_name'] : 'Dhansree Meta Campaign',
                'ad_name' => !empty($validated['ad_name']) ? $validated['ad_name'] : (ucfirst($angle) . ' - ' . date('d M Y')),
                'angle' => $angle,
                'primary_text' => $primaryText,
                'headline' => $headline,
                'description' => $validated['description'] ?? 'Cash on Delivery | Free Shipping',
                'cta' => $validated['cta'] ?? 'Shop Now',
                'destination_url' => $destinationUrl,
                'utm_source' => 'meta',
                'utm_medium' => 'paid_social',
                'utm_campaign' => $utmCampaign,
                'utm_content' => $angle,
                'creative_image_url' => !empty($validated['creative_image_url']) ? $validated['creative_image_url'] : $product->primary_image_url,
                'ai_provider' => $validated['ai_provider'] ?? 'smart_ai',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ad creative saved successfully to your library!',
                'creative' => $creative->load('product'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Meta Ad Creative save failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save creative: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a saved creative from the library.
     */
    public function destroy(MetaAdCreative $creative, Request $request): JsonResponse|RedirectResponse
    {
        $creative->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Ad creative removed from library.',
            ]);
        }

        return redirect()->back()->with('success', 'Ad creative removed from library.');
    }

    /**
     * Update AI and UTM settings.
     */
    public function updateSettings(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'gemini_api_key' => 'nullable|string|max:255',
            'openai_api_key' => 'nullable|string|max:255',
            'default_ai_provider' => 'required|string|in:smart_ai,gemini,chatgpt',
            'default_utm_campaign' => 'nullable|string|max:100',
        ]);

        SocialSetting::set('gemini_api_key', trim((string) ($validated['gemini_api_key'] ?? '')));
        SocialSetting::set('openai_api_key', trim((string) ($validated['openai_api_key'] ?? '')));
        SocialSetting::set('default_ai_provider', $validated['default_ai_provider']);
        SocialSetting::set('default_utm_campaign', trim((string) ($validated['default_utm_campaign'] ?? 'dhansree_meta_ads')));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Meta Ad Studio settings updated successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Meta Ad Studio settings updated successfully.');
    }
}
