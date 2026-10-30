<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVideo;
use App\Models\StoreSettings;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\ProductImageService;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductVariantService;
use App\Services\VideoStudio\FfmpegService;
use App\Services\VideoStudio\VideoStudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class AdminProductWebController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ProductVariantService $variantService,
        private readonly ProductImageService $imageService,
        private readonly CategoryTreeService $treeService
    ) {}

    /**
     * Product catalog table list.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        $stockStatus = $request->query('stock_status');
        $sort = $request->query('sort', 'updated');

        $products = $this->productService->list([
            'status' => $status,
            'category_id' => $categoryId ? (int) $categoryId : null,
            'search' => $search,
            'stockStatus' => $stockStatus,
            'sort' => $sort,
        ], 20);

        $categoryOptions = $this->treeService->toCategoryOptions();

        $counts = [
            'all' => Product::count(),
            'active' => Product::where('status', 'ACTIVE')->count(),
            'draft' => Product::where('status', 'DRAFT')->count(),
            'archived' => Product::where('status', 'ARCHIVED')->count(),
        ];

        return view('admin.catalog.products.index', [
            'products' => $products,
            'categoryOptions' => $categoryOptions,
            'status' => $status,
            'categoryId' => $categoryId,
            'search' => $search,
            'stockStatus' => $stockStatus,
            'sort' => $sort,
            'counts' => $counts,
        ]);
    }

    /**
     * Fast product create form.
     */
    public function create(): View
    {
        $categoryOptions = $this->treeService->toCategoryOptions();
        $store = StoreSettings::current();

        return view('admin.catalog.products.create', [
            'categoryOptions' => $categoryOptions,
            'defaultGstRateBps' => $store->default_gst_rate_bps ?? 1800,
        ]);
    }

    /**
     * Fast product store.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => 'nullable|string|max:100',
            'category_id' => 'required|integer|exists:categories,id',
            'sku' => 'nullable|string|max:64',
            'price_rupees' => 'required|numeric|min:0',
            'mrp_rupees' => 'nullable|numeric|min:0',
            'cost_rupees' => 'nullable|numeric|min:0',
            'initial_stock' => 'nullable|integer|min:0',
            'hsn_code' => 'nullable|string|max:8',
            'gst_rate_bps' => 'nullable|integer',
            'brand' => 'nullable|string|max:80',
            'ranking' => 'nullable|integer|min:1|max:999999',
            'short_description' => 'nullable|string|max:300',
            'description' => 'nullable|string',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $product = $this->productService->create(
                $validated,
                $adminUser?->id,
                $request
            );

            return redirect()->route('admin.products.edit', $product)
                ->with('success', "Product '{$product->name}' created successfully as draft.");
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Comprehensive product edit dashboard.
     */
    public function edit(Product $product): View
    {
        $detail = $this->productService->detail($product->id);
        $categoryOptions = $this->treeService->toCategoryOptions();
        $store = StoreSettings::current();
        $productContent = \App\Models\ProductContent::where('product_id', $product->id)->first();
        $customFaqs = \App\Models\FaqEntry::where('product_id', $product->id)->orderBy('position')->get();
        $quickLinkGroups = \App\Models\QuickLinkGroup::where('is_visible', true)->orderBy('position')->get();

        return view('admin.catalog.products.edit', [
            'product' => $detail,
            'categoryOptions' => $categoryOptions,
            'store' => $store,
            'publishIssues' => $detail->publish_issues ?? [],
            'productContent' => $productContent,
            'customFaqs' => $customFaqs,
            'quickLinkGroups' => $quickLinkGroups,
        ]);
    }

    /**
     * Update product details & SEO.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand' => 'nullable|string|max:80',
            'ranking' => 'nullable|integer|min:1|max:999999',
            'short_description' => 'nullable|string|max:300',
            'description' => 'nullable|string',
            'hsn_code' => 'nullable|string|max:8',
            'gst_rate_bps' => 'nullable|integer',
            'seo_title' => 'nullable|string|max:120',
            'seo_description' => 'nullable|string|max:320',
            'version' => 'nullable|integer',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->productService->update(
                $product,
                $validated,
                $adminUser?->id,
                $request
            );

            return redirect()->route('admin.products.edit', $product)
                ->with('success', 'Product details updated successfully.');
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Quick update product ranking.
     */
    public function updateRanking(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ranking' => 'nullable|integer|min:1|max:999999',
        ]);

        $ranking = !empty($validated['ranking']) ? (int) $validated['ranking'] : null;
        $product->update(['ranking' => $ranking]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'ranking' => $ranking,
                'message' => 'Ranking updated successfully.',
            ]);
        }

        return back()->with('success', "Ranking updated for '{$product->name}'.");
    }

    /**
     * Publish product.
     */
    public function publish(Request $request, Product $product): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->productService->publish($product, $adminUser?->id, $request);
            return back()->with('success', "Product '{$product->name}' is now ACTIVE and visible on the storefront.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unpublish product.
     */
    public function unpublish(Request $request, Product $product): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->productService->unpublish($product, $adminUser?->id, $request);
            return back()->with('success', "Product '{$product->name}' is now in DRAFT status.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Archive product.
     */
    public function archive(Request $request, Product $product): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->productService->archive($product, $adminUser?->id, $request);
            return redirect()->route('admin.products.index')
                ->with('success', "Product '{$product->name}' was archived.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Restore product.
     */
    public function restore(Request $request, Product $product): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->productService->restore($product, $adminUser?->id, $request);
            return back()->with('success', "Product '{$product->name}' was restored to DRAFT.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Add single variant.
     */
    public function storeVariant(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => 'nullable|string|max:64',
            'price_rupees' => 'required|numeric|min:0',
            'mrp_rupees' => 'nullable|numeric|min:0',
            'cost_rupees' => 'nullable|numeric|min:0',
            'weight_grams' => 'nullable|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'option_value_ids' => 'nullable|array',
            'option_value_ids.*' => 'integer|exists:product_option_values,id',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->variantService->createVariant($product, $validated, $adminUser?->id, $request);
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('success', 'Variant added successfully.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->withInput()
                ->with('active_tab', 'variants')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update single variant.
     */
    public function updateVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:64',
            'price_rupees' => 'required|numeric|min:0',
            'mrp_rupees' => 'nullable|numeric|min:0',
            'cost_rupees' => 'nullable|numeric|min:0',
            'weight_grams' => 'nullable|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->variantService->updateVariant($variant, $validated, $adminUser?->id, $request);
            $fresh = $variant->fresh();
            return redirect()->route('admin.products.edit', ['product' => $variant->product_id, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('success', "Variant '{$fresh->sku}' updated successfully (Stock on-hand: {$fresh->stock_on_hand}).");
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $variant->product_id, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete single variant.
     */
    public function destroyVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');
        $productId = $variant->product_id;
        $sku = $variant->sku;

        try {
            $this->variantService->deleteVariant($variant, $adminUser?->id, $request);
            return redirect()->route('admin.products.edit', ['product' => $productId, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('success', "Variant '{$sku}' deleted successfully.");
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $productId, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete all variants of a product.
     */
    public function destroyAllVariants(Request $request, Product $product): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $count = $this->variantService->deleteAllVariants($product, $adminUser?->id, $request);
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('success', "All {$count} variant(s) deleted successfully. You can now generate the variant matrix with clean SKUs.");
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk update stock quantity across all variants of a product.
     */
    public function bulkStock(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'bulk_stock_quantity' => 'required|integer|min:0',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $count = $this->variantService->bulkUpdateStock($product, (int) $validated['bulk_stock_quantity'], $adminUser?->id, $request);
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('success', "Stock set to {$validated['bulk_stock_quantity']} for all {$count} variant(s).");
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Archive variant.
     */
    public function archiveVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->variantService->archiveVariant($variant, $adminUser?->id, $request);
            return redirect()->route('admin.products.edit', ['product' => $variant->product_id, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('success', "Variant '{$variant->sku}' archived.");
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $variant->product_id, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Restore variant.
     */
    public function restoreVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->variantService->restoreVariant($variant, $adminUser?->id, $request);
            return redirect()->route('admin.products.edit', ['product' => $variant->product_id, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('success', "Variant '{$variant->sku}' restored.");
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $variant->product_id, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Sync product options & values.
     */
    public function syncOptions(Request $request, Product $product): RedirectResponse
    {
        $rawOptions = $request->input('options', []);
        $formatted = [];

        foreach ($rawOptions as $opt) {
            $name = trim($opt['name'] ?? '');
            $valuesStr = $opt['values'] ?? '';
            $values = array_filter(array_map('trim', explode(',', (string) $valuesStr)));

            if ($name !== '' && !empty($values)) {
                $formatted[] = [
                    'name' => $name,
                    'values' => array_values($values),
                ];
            }
        }

        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->variantService->syncOptions($product, $formatted, $adminUser?->id, $request);
            $message = empty($formatted)
                ? 'Product options removed. Product configured without variations.'
                : 'Product options and values saved.';
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('success', $message);
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Auto-generate variant matrix.
     */
    public function generateVariants(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'price_rupees' => 'required|numeric|min:0',
            'mrp_rupees' => 'nullable|numeric|min:0',
            'base_sku' => 'nullable|string|max:40',
            'default_stock' => 'nullable|integer|min:0',
        ]);

        $adminUser = $request->attributes->get('admin_user');
        $defaultStock = isset($validated['default_stock']) ? (int) $validated['default_stock'] : 0;

        try {
            $created = $this->variantService->generateVariantMatrix(
                $product,
                (float) $validated['price_rupees'],
                (float) ($validated['mrp_rupees'] ?: $validated['price_rupees']),
                $validated['base_sku'] ?? null,
                $adminUser?->id,
                $request,
                $defaultStock
            );

            $count = count($created);
            $stockMsg = $defaultStock > 0 ? " with stock initialized to {$defaultStock}" : "";
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('success', "{$count} variant(s) generated successfully{$stockMsg}.");
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'variants'])
                ->with('active_tab', 'variants')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Upload product image.
     */
    public function uploadImage(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'images' => 'nullable|array',
            'images.*' => 'file|max:10240', // 10 MB per image
            'image' => 'nullable|file|max:10240', // Backwards compatibility
            'alt_text' => 'nullable|string|max:160',
            'variant_ids' => 'nullable|array',
            'variant_ids.*' => 'integer|exists:product_variants,id',
            'option_value_ids' => 'nullable|array',
            'option_value_ids.*' => 'integer|exists:product_option_values,id',
            'is_actual_product_image' => 'nullable|boolean',
        ]);

        $files = [];
        if ($request->hasFile('images')) {
            $files = array_filter((array) $request->file('images'));
        } elseif ($request->hasFile('image')) {
            $single = $request->file('image');
            if ($single) {
                $files = [$single];
            }
        }

        if (empty($files)) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', 'Please select at least one valid image file to upload.');
        }

        $adminUser = $request->attributes->get('admin_user');
        $altText = $request->input('alt_text', $product->name);
        $variantIds = $request->input('variant_ids', []);
        $optionValueIds = $request->input('option_value_ids', []);
        $isActualProductImage = $request->boolean('is_actual_product_image');

        try {
            $uploadedCount = 0;
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $this->imageService->addImage(
                        $product,
                        $file,
                        $altText,
                        $variantIds,
                        $optionValueIds,
                        $adminUser?->id,
                        $request,
                        $isActualProductImage
                    );
                    $uploadedCount++;
                }
            }

            $message = $uploadedCount === 1
                ? 'Product image uploaded successfully.'
                : "{$uploadedCount} product images uploaded successfully.";

            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('success', $message);
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update product image assignments (alt text, variants, and option values).
     */
    public function updateImage(Request $request, ProductImage $image): RedirectResponse
    {
        $request->validate([
            'alt_text' => 'nullable|string|max:160',
            'variant_ids' => 'nullable|array',
            'variant_ids.*' => 'integer|exists:product_variants,id',
            'option_value_ids' => 'nullable|array',
            'option_value_ids.*' => 'integer|exists:product_option_values,id',
            'is_actual_product_image' => 'nullable|boolean',
        ]);

        $adminUser = $request->attributes->get('admin_user');
        $product = Product::findOrFail($image->product_id);

        try {
            $variantIds = array_map('intval', $request->input('variant_ids', []));
            $optionValueIds = array_map('intval', $request->input('option_value_ids', []));
            $isActualProductImage = $request->boolean('is_actual_product_image');

            $this->imageService->updateImage(
                $image,
                (string) $request->input('alt_text', $image->alt_text ?? ''),
                $variantIds,
                $optionValueIds,
                $adminUser?->id,
                $request,
                $isActualProductImage
            );

            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('success', 'Image variant and color assignments saved successfully.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete product image.
     */
    public function deleteImage(Request $request, ProductImage $image): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');
        $productId = $image->product_id;

        try {
            $this->imageService->deleteImage($image, $adminUser?->id, $request);
            return redirect()->route('admin.products.edit', ['product' => $productId, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('success', 'Product image deleted.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $productId, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Set product image as primary.
     */
    public function setPrimaryImage(Request $request, ProductImage $image): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');
        $productId = $image->product_id;

        try {
            $this->imageService->setPrimary($image, $adminUser?->id, $request);
            return redirect()->route('admin.products.edit', ['product' => $productId, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('success', 'Primary image updated.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $productId, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update product attributes / specifications.
     */
    public function updateAttributes(Request $request, Product $product): RedirectResponse
    {
        $rawAttrs = $request->input('attributes', []);
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->productService->setAttributes($product, $rawAttrs, $adminUser?->id, $request);
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'attributes'])
                ->with('active_tab', 'attributes')
                ->with('success', 'Product specifications saved successfully.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'attributes'])
                ->with('active_tab', 'attributes')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update product FAQs (mode and custom question-answer pairs).
     */
    public function updateFaqs(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'faq_mode' => 'required|string|in:INHERIT,CUSTOM,CUSTOM_PLUS_GLOBAL,HIDE,NONE',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'nullable|string|max:200',
            'faqs.*.answer' => 'nullable|string',
            'faqs.*.position' => 'nullable|integer|min:0',
        ]);

        $mode = $validated['faq_mode'] === 'NONE' ? 'HIDE' : $validated['faq_mode'];

        try {
            \App\Models\ProductContent::updateOrCreate(
                ['product_id' => $product->id],
                ['faq_mode' => $mode]
            );

            if ($mode === 'CUSTOM') {
                \App\Models\FaqEntry::where('product_id', $product->id)->delete();

                $faqs = $validated['faqs'] ?? [];
                foreach ($faqs as $idx => $faq) {
                    $q = trim($faq['question'] ?? '');
                    $a = trim($faq['answer'] ?? '');
                    if ($q !== '' && $a !== '') {
                        \App\Models\FaqEntry::create([
                            'product_id' => $product->id,
                            'group_id' => null,
                            'question' => $q,
                            'answer' => $a,
                            'position' => (int) ($faq['position'] ?? $idx),
                            'is_visible' => true,
                        ]);
                    }
                }
            }

            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'faqs'])
                ->with('active_tab', 'faqs')
                ->with('success', 'Product FAQs updated successfully.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'faqs'])
                ->with('active_tab', 'faqs')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update product Quick Links configuration.
     */
    public function updateQuickLinks(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quick_links_mode' => 'required|string|in:INHERIT,CUSTOM,CUSTOM_PLUS_GLOBAL,HIDE,NONE',
            'quick_link_group_id' => 'nullable|integer|exists:quick_link_groups,id',
        ]);

        $mode = $validated['quick_links_mode'] === 'NONE' ? 'HIDE' : $validated['quick_links_mode'];

        try {
            \App\Models\ProductContent::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'quick_links_mode' => $mode,
                    'quick_link_group_id' => $mode === 'CUSTOM' ? ($validated['quick_link_group_id'] ?? null) : null,
                ]
            );

            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'quick-links'])
                ->with('active_tab', 'quick-links')
                ->with('success', 'Product Quick Links updated successfully.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'quick-links'])
                ->with('active_tab', 'quick-links')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Upload product video (MP4/WebM) and optionally pin to color/variant.
     */
    public function uploadVideo(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        if (empty($_POST) && empty($_FILES) && (int) ($request->server('CONTENT_LENGTH') ?? 0) > 0) {
            $postMax = ini_get('post_max_size') ?: '8M';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "The uploaded video exceeds server's post_max_size ({$postMax}).",
                ], 422);
            }
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', "The uploaded video exceeds server's post_max_size ({$postMax}).");
        }

        if (isset($_FILES['video']['error']) && $_FILES['video']['error'] === UPLOAD_ERR_INI_SIZE) {
            $uploadMax = ini_get('upload_max_filesize') ?: '2M';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "The video exceeds upload_max_filesize ({$uploadMax}).",
                ], 422);
            }
            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', "The video exceeds upload_max_filesize ({$uploadMax}).");
        }

        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,webm,quicktime|max:61440',
            'title' => 'nullable|string|max:150',
            'is_primary' => 'nullable|boolean',
            'is_ai_generated' => 'nullable|boolean',
            'option_value_ids' => 'nullable|array',
            'option_value_ids.*' => 'integer|exists:product_option_values,id',
            'variant_ids' => 'nullable|array',
            'variant_ids.*' => 'integer|exists:product_variants,id',
        ], [
            'video.required' => 'Please select a video file (MP4 or WebM).',
            'video.mimes' => 'The video must be a file of type: mp4, mov, webm, quicktime.',
            'video.max' => 'The video file size cannot exceed 60MB.',
        ]);

        try {
            $file = $request->file('video');
            $adminUser = $request->attributes->get('admin_user');
            $optionValueIds = array_map('intval', (array) $request->input('option_value_ids', []));
            $variantIds = array_map('intval', (array) $request->input('variant_ids', []));

            $storageDir = config('video_studio.storage_dir', 'video-studio');
            $disk = config('video_studio.disk', 'public');

            $ext = strtolower($file->getClientOriginalExtension() ?: 'mp4');
            $filename = 'videos/prod_' . $product->id . '_' . Str::random(12) . '.' . $ext;
            $videoPath = "{$storageDir}/{$filename}";

            Storage::disk($disk)->put($videoPath, File::get($file->getRealPath()));

            $thumbRelativePath = null;
            $webmRelativePath = null;
            $videoDimensions = ['width' => 1080, 'height' => 1920, 'duration' => 10];
            $ffmpeg = app(FfmpegService::class);

            if ($ffmpeg->isAvailable()) {
                $localVideo = file_exists(Storage::disk($disk)->path($videoPath))
                    ? Storage::disk($disk)->path($videoPath)
                    : $file->getRealPath();

                $videoDimensions = $ffmpeg->getVideoDimensions($localVideo);

                // Extract lightweight WebP poster thumbnail to save storage & speed up initial page load
                $tempThumb = storage_path('app/temp/thumb_' . Str::uuid() . '.webp');
                File::ensureDirectoryExists(dirname($tempThumb));
                try {
                    $ffmpeg->extractThumbnail($localVideo, $tempThumb);
                    if (file_exists($tempThumb) && filesize($tempThumb) > 0) {
                        $thumbRelativePath = "{$storageDir}/thumbnails/thumb_" . Str::uuid() . '.webp';
                        Storage::disk($disk)->put($thumbRelativePath, File::get($tempThumb));
                        @unlink($tempThumb);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Thumbnail extraction failed for {$videoPath}: " . $e->getMessage());
                }

                // Automatically transcode uploaded MP4/MOV into high-efficiency WebM (VP9)
                if (in_array($ext, ['mp4', 'mov', 'quicktime', 'm4v'])) {
                    $tempWebm = storage_path('app/temp/webm_' . Str::uuid() . '.webm');
                    try {
                        $w = $videoDimensions['width'] ?: 1080;
                        $h = $videoDimensions['height'] ?: 1920;
                        $ffmpeg->renderCleanWebm($localVideo, $tempWebm, $w, $h);
                        if (file_exists($tempWebm) && filesize($tempWebm) > 0) {
                            $webmRelativePath = "{$storageDir}/videos/clean_" . Str::uuid() . '.webm';
                            Storage::disk($disk)->put($webmRelativePath, File::get($tempWebm));
                            @unlink($tempWebm);
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("WebM transcoding failed for {$videoPath}: " . $e->getMessage());
                    }
                } elseif ($ext === 'webm') {
                    $webmRelativePath = $videoPath;
                }
            }

            if (!$thumbRelativePath) {
                $thumbRelativePath = $product->primaryImage?->storage_key;
            }

            $isPrimary = $request->boolean('is_primary') || $product->videos()->count() === 0;

            if ($isPrimary) {
                ProductVideo::where('product_id', $product->id)->update(['is_primary' => false]);
            }

            $isAiGenerated = $request->has('is_ai_generated') ? $request->boolean('is_ai_generated') : true;

            $renderSettings = [
                'export_preset' => 'clean_storefront',
                'is_ai_generated' => $isAiGenerated,
            ];
            if ($webmRelativePath) {
                $renderSettings['webm_path'] = $webmRelativePath;
            }

            ProductVideo::create([
                'product_id' => $product->id,
                'title' => $request->input('title') ?: ($product->name . ' Showcase Video'),
                'video_type' => 'imported_branded',
                'template' => 'clean_showcase',
                'aspect_ratio' => ($videoDimensions['width'] > $videoDimensions['height']) ? '16:9' : (($videoDimensions['width'] === $videoDimensions['height']) ? '1:1' : '9:16'),
                'width' => $videoDimensions['width'] ?: 1080,
                'height' => $videoDimensions['height'] ?: 1920,
                'duration_seconds' => $videoDimensions['duration'] ?: 10,
                'status' => 'completed',
                'approval_status' => 'approved',
                'progress' => 100,
                'video_path' => $videoPath,
                'thumbnail_path' => $thumbRelativePath,
                'file_size_bytes' => $file->getSize(),
                'is_primary' => $isPrimary,
                'pinned_option_value_ids' => $optionValueIds,
                'pinned_variant_ids' => $variantIds,
                'render_settings' => $renderSettings,
                'created_by' => $adminUser?->id,
                'completed_at' => now(),
            ]);

            session()->flash('success', 'Product video uploaded and pinned successfully.');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product video uploaded and pinned successfully.',
                    'redirect' => route('admin.products.edit', ['product' => $product, 'tab' => 'media']),
                ]);
            }

            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('success', 'Product video uploaded and pinned successfully.');
        } catch (Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video upload failed: ' . $e->getMessage(),
                ], 422);
            }

            return redirect()->route('admin.products.edit', ['product' => $product, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', 'Video upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Update product video pins (color assignments, variant SKUs, primary status).
     */
    public function updateVideo(Request $request, ProductVideo $video): RedirectResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:150',
            'is_primary' => 'nullable|boolean',
            'is_ai_generated' => 'nullable|boolean',
            'option_value_ids' => 'nullable|array',
            'option_value_ids.*' => 'integer|exists:product_option_values,id',
            'variant_ids' => 'nullable|array',
            'variant_ids.*' => 'integer|exists:product_variants,id',
        ]);

        try {
            $isPrimary = $request->boolean('is_primary');
            if ($isPrimary) {
                ProductVideo::where('product_id', $video->product_id)
                    ->where('id', '!=', $video->id)
                    ->update(['is_primary' => false]);
            }

            $optionValueIds = array_map('intval', (array) $request->input('option_value_ids', []));
            $variantIds = array_map('intval', (array) $request->input('variant_ids', []));

            $renderSettings = $video->render_settings ?? [];
            if ($request->has('is_ai_generated')) {
                $renderSettings['is_ai_generated'] = $request->boolean('is_ai_generated');
            }

            $video->update([
                'title' => $request->input('title', $video->title),
                'is_primary' => $isPrimary,
                'pinned_option_value_ids' => $optionValueIds,
                'pinned_variant_ids' => $variantIds,
                'render_settings' => $renderSettings,
            ]);

            return redirect()->route('admin.products.edit', ['product' => $video->product_id, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('success', 'Video color and variation pins saved successfully.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $video->product_id, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Set a video as the primary storefront video for this product.
     */
    public function setPrimaryVideo(Request $request, ProductVideo $video): RedirectResponse
    {
        try {
            ProductVideo::where('product_id', $video->product_id)->update(['is_primary' => false]);
            $video->update(['is_primary' => true]);

            return redirect()->route('admin.products.edit', ['product' => $video->product_id, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('success', 'Primary video updated successfully.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $video->product_id, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete product video.
     */
    public function deleteVideo(Request $request, ProductVideo $video): RedirectResponse
    {
        $productId = $video->product_id;
        $wasPrimary = (bool) $video->is_primary;

        try {
            app(VideoStudioService::class)->delete($video);

            if ($wasPrimary && $productId) {
                $next = ProductVideo::where('product_id', $productId)->latest('id')->first();
                $next?->update(['is_primary' => true]);
            }

            return redirect()->route('admin.products.edit', ['product' => $productId, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('success', 'Product video deleted successfully.');
        } catch (Throwable $e) {
            return redirect()->route('admin.products.edit', ['product' => $productId, 'tab' => 'media'])
                ->with('active_tab', 'media')
                ->with('error', $e->getMessage());
        }
    }
}
