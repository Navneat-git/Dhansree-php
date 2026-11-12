<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SocialCommerce;

use App\Http\Controllers\Controller;
use App\Models\SocialMediaAsset;
use App\Models\SocialSupplier;
use App\Services\SocialCommerce\SocialProductService;
use App\Services\SocialCommerce\SupplierMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierMediaInboxController extends Controller
{
    public function __construct(
        protected SupplierMediaService $supplierMediaService,
        protected SocialProductService $productService
    ) {}

    /**
     * Display gallery of uploaded supplier media with filters.
     */
    public function index(Request $request): View
    {
        $query = SocialMediaAsset::with(['supplier', 'products'])
            ->latest('id');

        // Filters
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->filled('supplier_group_name')) {
            $query->where('supplier_group_name', 'like', '%' . $request->input('supplier_group_name') . '%');
        }

        if ($request->filled('category_name')) {
            $query->where('category_name', $request->input('category_name'));
        }

        if ($request->filled('media_type')) {
            $query->where('media_type', $request->input('media_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $assets = $query->paginate(24)->withQueryString();

        $suppliers = SocialSupplier::where('is_active', true)->orderBy('name')->get();
        $categories = SocialProductService::WOMEN_FASHION_CATEGORIES;

        // Group names list for autocomplete / filter
        $groupNames = SocialMediaAsset::whereNotNull('supplier_group_name')
            ->distinct()
            ->pluck('supplier_group_name')
            ->filter()
            ->values();

        return view('admin.social-commerce.inbox.index', [
            'assets' => $assets,
            'suppliers' => $suppliers,
            'categories' => $categories,
            'groupNames' => $groupNames,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Handle multi-file image & video uploads (manual / drag-and-drop).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:102400', // max 100MB per file
            'supplier_id' => 'nullable|integer|exists:social_suppliers,id',
            'supplier_name' => 'nullable|string|max:150',
            'supplier_group_name' => 'nullable|string|max:150',
            'category_name' => 'nullable|string|max:100',
        ]);

        $adminUser = $request->attributes->get('admin_user');
        $adminUserId = $adminUser?->id;

        $supplierId = $request->input('supplier_id');
        if (empty($supplierId) && $request->filled('supplier_name')) {
            $supplier = SocialSupplier::firstOrCreate(
                ['name' => trim($request->input('supplier_name'))],
                ['whatsapp_group_name' => $request->input('supplier_group_name')]
            );
            $supplierId = $supplier->id;
        }

        $results = [];
        $files = $request->file('files');

        foreach ($files as $file) {
            $result = $this->supplierMediaService->processUpload(
                file: $file,
                supplierId: $supplierId ? (int) $supplierId : null,
                supplierGroupName: $request->input('supplier_group_name'),
                categoryName: $request->input('category_name'),
                adminUserId: $adminUserId
            );
            $results[] = $result;
        }

        $createdCount = count(array_filter($results, fn($r) => $r['status'] === 'created'));
        $duplicateCount = count(array_filter($results, fn($r) => $r['status'] === 'duplicate'));
        $errorCount = count(array_filter($results, fn($r) => $r['status'] === 'error'));

        $message = "Upload complete: {$createdCount} new media saved.";
        if ($duplicateCount > 0) {
            $message .= " {$duplicateCount} duplicates skipped.";
        }
        if ($errorCount > 0) {
            $message .= " {$errorCount} failed.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results,
                'summary' => [
                    'created' => $createdCount,
                    'duplicate' => $duplicateCount,
                    'error' => $errorCount,
                ],
            ]);
        }

        return redirect()->route('admin.social-commerce.inbox.index')->with('success', $message);
    }

    /**
     * Import media directly from a ZIP file archive.
     */
    public function storeZip(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:zip|max:524288', // 512MB max ZIP
            'supplier_id' => 'nullable|integer|exists:social_suppliers,id',
            'supplier_name' => 'nullable|string|max:150',
            'supplier_group_name' => 'nullable|string|max:150',
            'category_name' => 'nullable|string|max:100',
        ]);

        $adminUser = $request->attributes->get('admin_user');
        $adminUserId = $adminUser?->id;

        $supplierId = $request->input('supplier_id');
        if (empty($supplierId) && $request->filled('supplier_name')) {
            $supplier = SocialSupplier::firstOrCreate(
                ['name' => trim($request->input('supplier_name'))],
                ['whatsapp_group_name' => $request->input('supplier_group_name')]
            );
            $supplierId = $supplier->id;
        }

        $summary = $this->supplierMediaService->processZipArchive(
            zipFile: $request->file('zip_file'),
            supplierId: $supplierId ? (int) $supplierId : null,
            supplierGroupName: $request->input('supplier_group_name'),
            categoryName: $request->input('category_name'),
            adminUserId: $adminUserId
        );

        $message = "ZIP import finished: {$summary['created']} files imported, {$summary['skipped_duplicate']} duplicates skipped, {$summary['errors']} failed.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'summary' => $summary,
            ]);
        }

        return redirect()->route('admin.social-commerce.inbox.index')->with('success', $message);
    }

    /**
     * Delete a single media asset.
     */
    public function destroy(SocialMediaAsset $asset): RedirectResponse|JsonResponse
    {
        $this->supplierMediaService->deleteAsset($asset);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Media deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Media asset deleted.');
    }

    /**
     * Perform bulk operations on selected media assets (delete, status, categorize).
     */
    public function bulkAction(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'action' => 'required|in:delete,mark_processed,mark_unprocessed,set_category',
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'integer|exists:social_media_assets,id',
            'category_name' => 'required_if:action,set_category|string|max:100',
        ]);

        $assetIds = array_map('intval', $request->input('asset_ids'));
        $action = $request->input('action');

        switch ($action) {
            case 'delete':
                $count = $this->supplierMediaService->bulkDelete($assetIds);
                $message = "{$count} media assets deleted.";
                break;
            case 'mark_processed':
                SocialMediaAsset::whereIn('id', $assetIds)->update(['status' => 'PROCESSED']);
                $message = count($assetIds) . " assets marked as Processed.";
                break;
            case 'mark_unprocessed':
                SocialMediaAsset::whereIn('id', $assetIds)->update(['status' => 'UNPROCESSED']);
                $message = count($assetIds) . " assets marked as Unprocessed.";
                break;
            case 'set_category':
                $count = $this->supplierMediaService->bulkCategorize($assetIds, $request->input('category_name'));
                $message = "{$count} assets updated to category: " . $request->input('category_name');
                break;
            default:
                $message = "Action executed.";
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Move selected media assets into a new Product in the Product Library.
     */
    public function moveToProduct(Request $request): RedirectResponse
    {
        $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'integer|exists:social_media_assets,id',
            'product_name' => 'required|string|max:200',
            'category_name' => 'required|string|max:100',
            'supplier_id' => 'nullable|integer|exists:social_suppliers,id',
            'supplier_product_code' => 'nullable|string|max:100',
            'fabric' => 'nullable|string|max:150',
            'purchase_price' => 'nullable|numeric|min:0',
            'suggested_selling_price' => 'nullable|numeric|min:0',
            'special_offer_price' => 'nullable|numeric|min:0',
            'available_colors' => 'nullable|string',
            'available_sizes' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $adminUser = $request->attributes->get('admin_user');
        $adminUserId = $adminUser?->id;

        $assetIds = array_map('intval', $request->input('asset_ids'));

        $product = $this->supplierMediaService->convertAssetsToProduct(
            assetIds: $assetIds,
            data: [
                'name' => $request->input('product_name'),
                'category_name' => $request->input('category_name'),
                'supplier_id' => $request->input('supplier_id'),
                'supplier_product_code' => $request->input('supplier_product_code'),
                'fabric' => $request->input('fabric'),
                'purchase_price' => $request->input('purchase_price'),
                'suggested_selling_price' => $request->input('suggested_selling_price'),
                'special_offer_price' => $request->input('special_offer_price'),
                'available_colors' => $request->input('available_colors'),
                'available_sizes' => $request->input('available_sizes'),
                'description' => $request->input('description'),
            ],
            admin: $adminUserId
        );

        return redirect()->route('admin.social-commerce.products.show', $product)
            ->with('success', "Product {$product->product_code} created from " . count($assetIds) . " selected media assets.");
    }
}
