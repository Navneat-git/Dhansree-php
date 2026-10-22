<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use App\Services\Catalog\CategoryTreeService;
use App\Services\Catalog\ProductImageService;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('admin.catalog.products.edit', [
            'product' => $detail,
            'categoryOptions' => $categoryOptions,
            'store' => $store,
            'publishIssues' => $detail->publish_issues ?? [],
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
            return back()->with('success', 'Variant added successfully.');
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
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
            return back()->with('success', "Variant '{$variant->sku}' updated successfully.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
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
            return back()->with('success', "Variant '{$variant->sku}' archived.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
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
            return back()->with('success', "Variant '{$variant->sku}' restored.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
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
            return back()->with('success', 'Product options and values saved.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
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
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $created = $this->variantService->generateVariantMatrix(
                $product,
                (float) $validated['price_rupees'],
                (float) ($validated['mrp_rupees'] ?: $validated['price_rupees']),
                $validated['base_sku'] ?? null,
                $adminUser?->id,
                $request
            );

            $count = count($created);
            return back()->with('success', "{$count} variant(s) generated successfully.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Upload product image.
     */
    public function uploadImage(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'image' => 'required|file|max:10240', // 10 MB
            'alt_text' => 'nullable|string|max:160',
            'variant_ids' => 'nullable|array',
            'variant_ids.*' => 'integer|exists:product_variants,id',
            'option_value_ids' => 'nullable|array',
            'option_value_ids.*' => 'integer|exists:product_option_values,id',
        ]);

        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->imageService->addImage(
                $product,
                $request->file('image'),
                $request->input('alt_text', $product->name),
                $request->input('variant_ids', []),
                $request->input('option_value_ids', []),
                $adminUser?->id,
                $request
            );

            return back()->with('success', 'Product image uploaded successfully.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete product image.
     */
    public function deleteImage(Request $request, ProductImage $image): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->imageService->deleteImage($image, $adminUser?->id, $request);
            return back()->with('success', 'Product image deleted.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Set product image as primary.
     */
    public function setPrimaryImage(Request $request, ProductImage $image): RedirectResponse
    {
        $adminUser = $request->attributes->get('admin_user');

        try {
            $this->imageService->setPrimary($image, $adminUser?->id, $request);
            return back()->with('success', 'Primary image updated.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
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
            return back()->with('success', 'Product specifications saved successfully.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
