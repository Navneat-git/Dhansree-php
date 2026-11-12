<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SocialCommerce;

use App\Http\Controllers\Controller;
use App\Models\SocialMediaAsset;
use App\Models\SocialProduct;
use App\Models\SocialSetting;
use App\Services\SocialCommerce\WhatsAppStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WhatsAppStatusStudioController extends Controller
{
    public function __construct(
        protected WhatsAppStatusService $statusService
    ) {}

    /**
     * Studio interface: select product or media, customize design, live preview, export.
     */
    public function index(Request $request): View
    {
        $selectedProductId = $request->input('product_id');
        $selectedAssetId = $request->input('asset_id');

        $selectedProduct = $selectedProductId ? SocialProduct::with(['mediaAssets', 'supplier'])->find($selectedProductId) : null;
        $selectedAsset = $selectedAssetId ? SocialMediaAsset::find($selectedAssetId) : null;

        if (!$selectedAsset && $selectedProduct) {
            $selectedAsset = $selectedProduct->primaryMedia ?: $selectedProduct->mediaAssets()->first();
        }

        // Available products and media for picker
        $products = SocialProduct::with(['primaryMedia', 'mediaAssets'])
            ->whereIn('availability_status', ['NEW', 'AVAILABLE'])
            ->latest('id')
            ->take(50)
            ->get();

        $standaloneAssets = SocialMediaAsset::where('media_type', 'IMAGE')
            ->latest('id')
            ->take(40)
            ->get();

        // Default settings
        $brandName = SocialSetting::get('brand_name', 'Dhansree');
        $tagline = SocialSetting::get('tagline', 'STYLE. TECH. EVERYDAY.');
        $whatsappNumber = SocialSetting::get('whatsapp_number', '+919887776777');
        $whatsappCta = SocialSetting::get('whatsapp_order_cta', 'WhatsApp to Order');

        // Initial preview card path if asset selected
        $previewUrl = null;
        if ($selectedAsset) {
            try {
                $cardPath = $this->statusService->renderStatusCard($selectedAsset, $selectedProduct, [
                    'brand_name' => $brandName,
                    'tagline' => $tagline,
                    'whatsapp_number' => $whatsappNumber,
                    'cta_text' => $whatsappCta,
                ]);
                $previewUrl = Storage::disk('public')->url($cardPath);
            } catch (\Throwable $e) {
                // Keep preview null if error
            }
        }

        $whatsappWebUrl = $this->statusService->generateWhatsAppWebUrl($selectedProduct);

        return view('admin.social-commerce.whatsapp-status.index', [
            'products' => $products,
            'standaloneAssets' => $standaloneAssets,
            'selectedProduct' => $selectedProduct,
            'selectedAsset' => $selectedAsset,
            'templates' => WhatsAppStatusService::TEMPLATES,
            'brandName' => $brandName,
            'tagline' => $tagline,
            'whatsappNumber' => $whatsappNumber,
            'whatsappCta' => $whatsappCta,
            'previewUrl' => $previewUrl,
            'whatsappWebUrl' => $whatsappWebUrl,
        ]);
    }

    /**
     * AJAX generate / preview a 9:16 WhatsApp status card.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'asset_id' => 'required|integer|exists:social_media_assets,id',
            'product_id' => 'nullable|integer|exists:social_products,id',
            'template' => 'nullable|string',
            'brand_name' => 'nullable|string',
            'tagline' => 'nullable|string',
            'whatsapp_number' => 'nullable|string',
            'cta_text' => 'nullable|string',
            'price_text' => 'nullable|string',
            'custom_title' => 'nullable|string',
        ]);

        $asset = SocialMediaAsset::findOrFail($request->input('asset_id'));
        $product = $request->filled('product_id') ? SocialProduct::find($request->input('product_id')) : null;

        try {
            $options = [
                'template' => $request->input('template', 'classic_maroon'),
                'brand_name' => $request->input('brand_name') ?: SocialSetting::get('brand_name', 'Dhansree'),
                'tagline' => $request->input('tagline') ?: SocialSetting::get('tagline', 'STYLE. TECH. EVERYDAY.'),
                'whatsapp_number' => $request->input('whatsapp_number') ?: SocialSetting::get('whatsapp_number', '+919887776777'),
                'cta_text' => $request->input('cta_text') ?: SocialSetting::get('whatsapp_order_cta', 'WhatsApp to Order'),
                'price_text' => $request->input('price_text'),
                'product_name' => $request->input('custom_title'),
            ];

            $relPath = $this->statusService->renderStatusCard($asset, $product, $options);
            $fullUrl = Storage::disk('public')->url($relPath);

            $whatsappWebUrl = $this->statusService->generateWhatsAppWebUrl($product);

            return response()->json([
                'success' => true,
                'card_path' => $relPath,
                'card_url' => $fullUrl,
                'whatsapp_web_url' => $whatsappWebUrl,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to render WhatsApp Status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a rendered status card.
     */
    public function downloadSingle(Request $request): BinaryFileResponse
    {
        $path = $request->input('path');
        $absPath = storage_path('app/public/' . $path);

        if (!file_exists($absPath)) {
            abort(404, 'Status image not found.');
        }

        return response()->download($absPath, 'dhansree_whatsapp_status.jpg', [
            'Content-Type' => 'image/jpeg',
        ]);
    }

    /**
     * Bulk generate all images for a product and download as a ZIP file.
     */
    public function downloadProductZip(SocialProduct $product): BinaryFileResponse
    {
        $product->load('mediaAssets');
        $assets = $product->mediaAssets;

        if ($assets->isEmpty() && $product->primaryMedia) {
            $assets = collect([$product->primaryMedia]);
        }

        if ($assets->isEmpty()) {
            abort(404, 'No media assets found for this product.');
        }

        $renderedPaths = [];
        foreach ($assets as $asset) {
            if ($asset->media_type === 'IMAGE') {
                $renderedPaths[] = $this->statusService->renderStatusCard($asset, $product);
            }
        }

        if (empty($renderedPaths)) {
            abort(400, 'No valid image assets available to generate cards.');
        }

        $zipName = "dhansree_status_{$product->product_code}.zip";
        $zipPath = $this->statusService->createZipPackage($renderedPaths, $zipName);

        return response()->download($zipPath, $zipName, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
