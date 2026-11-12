<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SocialCommerce;

use App\Http\Controllers\Controller;
use App\Models\SocialMediaAsset;
use App\Models\SocialProduct;
use App\Models\SocialSupplier;
use App\Services\SocialCommerce\CaptionGeneratorService;
use App\Services\SocialCommerce\SocialProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialProductLibraryController extends Controller
{
    public function __construct(
        protected SocialProductService $productService,
        protected CaptionGeneratorService $captionService
    ) {}

    /**
     * List supplier products with filtering.
     */
    public function index(Request $request): View
    {
        $products = $this->productService->listProducts($request->all(), 20);
        $suppliers = SocialSupplier::where('is_active', true)->orderBy('name')->get();
        $categories = SocialProductService::WOMEN_FASHION_CATEGORIES;

        return view('admin.social-commerce.products.index', [
            'products' => $products,
            'suppliers' => $suppliers,
            'categories' => $categories,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Show form to create a new supplier product manually.
     */
    public function create(): View
    {
        $suppliers = SocialSupplier::where('is_active', true)->orderBy('name')->get();
        $categories = SocialProductService::WOMEN_FASHION_CATEGORIES;
        $unassignedAssets = SocialMediaAsset::where('status', 'UNPROCESSED')
            ->latest('id')
            ->take(50)
            ->get();

        return view('admin.social-commerce.products.create', [
            'suppliers' => $suppliers,
            'categories' => $categories,
            'unassignedAssets' => $unassignedAssets,
            'nextProductCode' => $this->productService->generateProductCode(),
        ]);
    }

    /**
     * Store new product.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'category_name' => 'required|string|max:100',
            'product_code' => 'nullable|string|max:50|unique:social_products,product_code',
            'supplier_id' => 'nullable|integer|exists:social_suppliers,id',
            'supplier_product_code' => 'nullable|string|max:100',
            'fabric' => 'nullable|string|max:150',
            'available_colors' => 'nullable|string',
            'available_sizes' => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'suggested_selling_price' => 'nullable|numeric|min:0',
            'special_offer_price' => 'nullable|numeric|min:0',
            'availability_status' => 'required|in:NEW,AVAILABLE,TEMPORARILY_UNAVAILABLE,OUT_OF_STOCK,ARCHIVED',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'media_asset_ids' => 'nullable|array',
            'media_asset_ids.*' => 'integer|exists:social_media_assets,id',
            'primary_media_asset_id' => 'nullable|integer|exists:social_media_assets,id',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        $product = $this->productService->createProduct(
            $request->all(),
            $adminUser?->id
        );

        return redirect()->route('admin.social-commerce.products.show', $product)
            ->with('success', "Product {$product->product_code} created successfully.");
    }

    /**
     * View detailed product page (attributes, variants, media, caption generator, status).
     */
    public function show(SocialProduct $product): View
    {
        $product->load(['supplier', 'primaryMedia', 'mediaAssets', 'variants', 'captions', 'creator']);

        // Generate instant captions for display
        $instagramCaption = $this->captionService->generateCaption($product, 'new_arrival', 'INSTAGRAM');
        $whatsappCaption = $this->captionService->generateCaption($product, 'new_arrival', 'WHATSAPP');

        return view('admin.social-commerce.products.show', [
            'product' => $product,
            'instagramCaption' => $instagramCaption,
            'whatsappCaption' => $whatsappCaption,
            'tones' => CaptionGeneratorService::TONES,
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(SocialProduct $product): View
    {
        $product->load(['supplier', 'mediaAssets', 'variants']);
        $suppliers = SocialSupplier::where('is_active', true)->orderBy('name')->get();
        $categories = SocialProductService::WOMEN_FASHION_CATEGORIES;
        $allAssets = SocialMediaAsset::latest('id')->take(60)->get();

        return view('admin.social-commerce.products.edit', [
            'product' => $product,
            'suppliers' => $suppliers,
            'categories' => $categories,
            'allAssets' => $allAssets,
        ]);
    }

    /**
     * Update product.
     */
    public function update(Request $request, SocialProduct $product): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'category_name' => 'required|string|max:100',
            'supplier_id' => 'nullable|integer|exists:social_suppliers,id',
            'supplier_product_code' => 'nullable|string|max:100',
            'fabric' => 'nullable|string|max:150',
            'available_colors' => 'nullable|string',
            'available_sizes' => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'suggested_selling_price' => 'nullable|numeric|min:0',
            'special_offer_price' => 'nullable|numeric|min:0',
            'availability_status' => 'required|in:NEW,AVAILABLE,TEMPORARILY_UNAVAILABLE,OUT_OF_STOCK,ARCHIVED',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'media_asset_ids' => 'nullable|array',
            'media_asset_ids.*' => 'integer|exists:social_media_assets,id',
            'primary_media_asset_id' => 'nullable|integer|exists:social_media_assets,id',
            'variants' => 'nullable|array',
        ]);

        $this->productService->updateProduct($product, $request->all());

        return redirect()->route('admin.social-commerce.products.show', $product)
            ->with('success', "Product {$product->product_code} updated successfully.");
    }

    /**
     * Quick status update endpoint (via modal or AJAX).
     */
    public function updateAvailability(Request $request, SocialProduct $product): JsonResponse|RedirectResponse
    {
        $request->validate([
            'availability_status' => 'required|in:NEW,AVAILABLE,TEMPORARILY_UNAVAILABLE,OUT_OF_STOCK,ARCHIVED',
            'notes' => 'nullable|string|max:500',
        ]);

        $updated = $this->productService->updateAvailability(
            $product,
            $request->input('availability_status'),
            $request->input('notes')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $updated->availability_status,
                'message' => "Status changed to {$updated->availability_status}",
            ]);
        }

        return redirect()->back()->with('success', "Status changed to {$updated->availability_status}.");
    }

    /**
     * Generate fresh caption based on selected tone.
     */
    public function generateCaption(Request $request, SocialProduct $product): JsonResponse
    {
        $tone = $request->input('tone', 'new_arrival');
        $platform = $request->input('platform', 'INSTAGRAM');

        $result = $this->captionService->generateCaption($product, $tone, $platform);

        if ($request->boolean('save')) {
            $this->captionService->saveCaption(
                $product,
                $result['caption_text'],
                $result['hashtags'],
                $tone,
                $platform
            );
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Explicitly publish this supplier product to the public website store.
     */
    public function promoteToWeb(SocialProduct $product): RedirectResponse
    {
        try {
            $webProduct = $this->productService->promoteToWeb($product);
            return redirect()->back()->with('success', "Published to website store as Product #{$webProduct->id} ({$webProduct->name})!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to publish to website: ' . $e->getMessage());
        }
    }

    /**
     * Delete product.
     */
    public function destroy(SocialProduct $product): RedirectResponse
    {
        $this->productService->deleteProduct($product);
        return redirect()->route('admin.social-commerce.products.index')
            ->with('success', "Product {$product->product_code} deleted.");
    }
}
