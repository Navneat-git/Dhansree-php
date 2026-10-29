<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\AuditActorType;
use App\Enums\BackorderPolicy;
use App\Enums\GstRegistrationType;
use App\Enums\ProductStatus;
use App\Models\InventoryItem;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use App\Support\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ProductService
{
    public function __construct(
        private readonly SlugService $slugService,
        private readonly SeoRedirectService $redirectService
    ) {}

    /**
     * List products with filter, search, sort and pagination.
     */
    public function list(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Product::query()->with([
            'category:id,name,slug,is_visible,archived_at',
            'variants' => fn($q) => $q->whereNull('archived_at'),
            'primaryImage',
            'images',
        ]);

        // Status filter (DRAFT, ACTIVE, ARCHIVED)
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        // Search in product name, slug, brand, or variant SKU
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('slug', 'like', $search)
                  ->orWhere('brand', 'like', $search)
                  ->orWhereHas('variants', fn(Builder $vq) => $vq->where('sku', 'like', $search));
            });
        }

        // Stock status filter (low_stock, out_of_stock, in_stock)
        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'out_of_stock') {
                $query->whereHas('variants.inventoryLevels', function (Builder $iq) {
                    $iq->select(DB::raw('SUM(on_hand - reserved)'))
                       ->groupBy('variant_id')
                       ->havingRaw('SUM(on_hand - reserved) <= 0');
                });
            } elseif ($filters['stock_status'] === 'low_stock') {
                $threshold = StoreSettings::current()->low_stock_threshold ?? 5;
                $query->whereHas('variants.inventoryLevels', function (Builder $iq) use ($threshold) {
                    $iq->whereRaw('(on_hand - reserved) > 0 AND (on_hand - reserved) <= ?', [$threshold]);
                });
            }
        }

        // Sort
        $sort = $filters['sort'] ?? 'updated';
        match ($sort) {
            'created' => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
            'name' => $query->orderBy('name', 'asc')->orderBy('id', 'asc'),
            'ranking' => \Illuminate\Support\Facades\Schema::hasColumn('products', 'ranking')
                ? $query->orderByRaw('CASE WHEN ranking IS NULL OR ranking = 0 THEN 1 ELSE 0 END, ranking ASC, id DESC')
                : $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc'),
            default => $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc'),
        };

        return $query->paginate($perPage);
    }

    /**
     * Get product detail with all relations and publish issues.
     */
    public function detail(int $id): Product
    {
        $product = Product::with([
            'category',
            'variants' => fn($q) => $q->orderBy('position')->with(['optionValues', 'inventoryItem', 'inventoryLevels']),
            'options' => fn($q) => $q->orderBy('position')->with(['values' => fn($vq) => $vq->orderBy('position')]),
            'images' => fn($q) => $q->orderBy('position')->with(['productImageVariants', 'productImageOptionValues', 'variants', 'optionValues']),
            'productAttributes' => fn($q) => $q->orderBy('position'),
        ])->findOrFail($id);

        $product->setAttribute('publish_issues', $this->publishIssues($product));

        return $product;
    }

    /**
     * Compute what issues stop this product from being published.
     *
     * @return array<int, array{code: string, message: string}>
     */
    public function publishIssues(Product $product): array
    {
        $issues = [];
        $store = StoreSettings::current();

        // 1. Category check
        if (!$product->category_id || !$product->category) {
            $issues[] = ['code' => 'CATEGORY_REQUIRED', 'message' => 'Choose a category for this product.'];
        } elseif ($product->category->archived_at !== null) {
            $issues[] = ['code' => 'CATEGORY_ARCHIVED', 'message' => 'The assigned category is archived. Choose an active category.'];
        }

        // 2. Variant check
        $liveVariantsCount = $product->variants->whereNull('archived_at')->count();
        if ($liveVariantsCount === 0) {
            $issues[] = ['code' => 'VARIANT_REQUIRED', 'message' => 'Add at least one active variant with a price.'];
        }

        // 3. Image check
        if ($product->images->isEmpty()) {
            $issues[] = ['code' => 'IMAGE_REQUIRED', 'message' => 'Add at least one product image.'];
        }

        // 4. HSN Code check
        if ($store->gst_registration_type !== GstRegistrationType::UNREGISTERED && empty($product->hsn_code)) {
            $issues[] = ['code' => 'HSN_REQUIRED', 'message' => 'Add the HSN code. It is required on GST tax invoices.'];
        }

        // 5. GST Rate check
        $resolvedGst = $this->resolveProductGstRate($product, $store);
        if ($resolvedGst['source'] === 'none') {
            $issues[] = ['code' => 'GST_RATE_REQUIRED', 'message' => 'Set a GST rate for this product or a default GST rate in Store Settings.'];
        }

        return $issues;
    }

    /**
     * Resolve effective GST rate and its origin.
     *
     * @return array{rate_bps: int|null, source: string}
     */
    public function resolveProductGstRate(Product $product, ?StoreSettings $store = null): array
    {
        $store = $store ?? StoreSettings::current();

        if ($store->gst_registration_type !== GstRegistrationType::REGULAR) {
            return ['rate_bps' => null, 'source' => 'not_applicable'];
        }

        if ($product->gst_rate_bps !== null) {
            return ['rate_bps' => (int) $product->gst_rate_bps, 'source' => 'product'];
        }

        if ($store->default_gst_rate_bps !== null) {
            return ['rate_bps' => (int) $store->default_gst_rate_bps, 'source' => 'store'];
        }

        return ['rate_bps' => null, 'source' => 'none'];
    }

    /**
     * Create product with default variant and initialize stock inventory.
     */
    public function create(array $data, ?int $adminUserId = null, ?Request $request = null): Product
    {
        return DB::transaction(function () use ($data, $adminUserId, $request) {
            $name = trim($data['name']);

            // Auto slug
            $slug = !empty($data['slug'])
                ? SlugService::slugify($data['slug'])
                : SlugService::generateUnique($name, fn(string $s) => Product::where('slug', $s)->exists());

            // SKU check
            $sku = strtoupper(trim($data['sku'] ?? ''));
            if (empty($sku)) {
                $sku = strtoupper(Str::slug($name, '')) . '-' . strtoupper(Str::random(4));
            }
            if (ProductVariant::where('sku', $sku)->exists()) {
                throw new InvalidArgumentException("SKU '{$sku}' is already taken by another product or variant.");
            }

            $pricePaise = (int) round(($data['price_rupees'] ?? 0) * 100);
            $mrpPaise = !empty($data['mrp_rupees'])
                ? (int) round($data['mrp_rupees'] * 100)
                : $pricePaise;
            $costPaise = !empty($data['cost_rupees'])
                ? (int) round($data['cost_rupees'] * 100)
                : null;

            if ($pricePaise < 0 || $mrpPaise < $pricePaise) {
                throw new InvalidArgumentException('MRP cannot be less than the selling price.');
            }

            $productData = [
                'name' => $name,
                'slug' => $slug,
                'status' => ProductStatus::DRAFT,
                'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
                'brand' => !empty($data['brand']) ? trim($data['brand']) : null,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'hsn_code' => !empty($data['hsn_code']) ? trim($data['hsn_code']) : null,
                'gst_rate_bps' => array_key_exists('gst_rate_bps', $data) && $data['gst_rate_bps'] !== null ? (int) $data['gst_rate_bps'] : null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'version' => 0,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'ranking')) {
                $productData['ranking'] = !empty($data['ranking']) ? (int) $data['ranking'] : null;
            }

            $product = Product::create($productData);

            // Release any redirects from this new path
            $this->redirectService->releaseRedirectSource('/product/' . $slug);

            // Create default variant
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'price_paise' => $pricePaise,
                'mrp_paise' => $mrpPaise,
                'cost_paise' => $costPaise,
                'weight_grams' => !empty($data['weight_grams']) ? (int) $data['weight_grams'] : 250,
                'option_signature' => '',
                'position' => 0,
                'version' => 0,
            ]);

            // Initialize inventory
            $defaultLocation = InventoryLocation::where('is_default', true)->first()
                ?? InventoryLocation::firstOrCreate(
                    ['code' => 'MAIN'],
                    ['name' => 'Main location', 'is_active' => true, 'is_default' => true]
                );

            InventoryItem::firstOrCreate(
                ['variant_id' => $variant->id],
                ['backorder_policy' => BackorderPolicy::STOP_SALE, 'low_stock_threshold' => null]
            );

            $initialStock = max(0, (int) ($data['initial_stock'] ?? 0));
            InventoryLevel::firstOrCreate(
                ['variant_id' => $variant->id, 'location_id' => $defaultLocation->id],
                ['on_hand' => $initialStock, 'reserved' => 0]
            );

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.product.created',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product',
                    entityId: $product->id,
                    metadata: ['name' => $product->name, 'slug' => $product->slug, 'sku' => $sku],
                    request: $request
                );
            }

            return $product;
        });
    }

    /**
     * Update product details & SEO.
     */
    public function update(Product $product, array $data, ?int $adminUserId = null, ?Request $request = null): Product
    {
        return DB::transaction(function () use ($product, $data, $adminUserId, $request) {
            $current = Product::lockForUpdate()->findOrFail($product->id);

            if (isset($data['version']) && (int) $data['version'] !== (int) $current->version) {
                throw new RuntimeException('This product was modified by another administrator. Please refresh.');
            }

            $newSlug = !empty($data['slug']) ? SlugService::slugify($data['slug']) : $current->slug;
            if ($newSlug !== $current->slug) {
                $exists = Product::where('slug', $newSlug)->where('id', '!=', $current->id)->exists();
                if ($exists) {
                    throw new InvalidArgumentException("The product slug '{$newSlug}' is already taken.");
                }

                // Record 301 redirect
                $this->redirectService->recordAddressChange(
                    '/product/' . $current->slug,
                    '/product/' . $newSlug,
                    $adminUserId,
                    $request
                );
            }

            $updateData = [
                'name' => trim($data['name'] ?? $current->name),
                'slug' => $newSlug,
                'category_id' => array_key_exists('category_id', $data) ? ($data['category_id'] ? (int) $data['category_id'] : null) : $current->category_id,
                'brand' => array_key_exists('brand', $data) ? ($data['brand'] ? trim($data['brand']) : null) : $current->brand,
                'short_description' => array_key_exists('short_description', $data) ? $data['short_description'] : $current->short_description,
                'description' => array_key_exists('description', $data) ? $data['description'] : $current->description,
                'hsn_code' => array_key_exists('hsn_code', $data) ? ($data['hsn_code'] ? trim($data['hsn_code']) : null) : $current->hsn_code,
                'gst_rate_bps' => array_key_exists('gst_rate_bps', $data) ? ($data['gst_rate_bps'] !== null ? (int) $data['gst_rate_bps'] : null) : $current->gst_rate_bps,
                'seo_title' => array_key_exists('seo_title', $data) ? $data['seo_title'] : $current->seo_title,
                'seo_description' => array_key_exists('seo_description', $data) ? $data['seo_description'] : $current->seo_description,
                'version' => $current->version + 1,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'ranking')) {
                $updateData['ranking'] = array_key_exists('ranking', $data) ? ($data['ranking'] !== null && $data['ranking'] !== '' ? (int) $data['ranking'] : null) : $current->ranking;
            }

            $current->update($updateData);

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.product.updated',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product',
                    entityId: $current->id,
                    metadata: ['updated_fields' => array_keys($data)],
                    request: $request
                );
            }

            return $current;
        });
    }

    /**
     * Publish product to storefront.
     */
    public function publish(Product $product, ?int $adminUserId = null, ?Request $request = null): Product
    {
        $product = Product::with(['category', 'variants', 'images'])->findOrFail($product->id);

        $issues = $this->publishIssues($product);
        if (!empty($issues)) {
            $messages = array_column($issues, 'message');
            throw new InvalidArgumentException('Cannot publish product: ' . implode(' ', $messages));
        }

        $product->update([
            'status' => ProductStatus::ACTIVE,
            'published_at' => $product->published_at ?? Carbon::now('UTC'),
            'version' => $product->version + 1,
        ]);

        if ($adminUserId) {
            AuditLogger::log(
                'catalog.product.published',
                AuditActorType::ADMIN,
                actorAdminUserId: $adminUserId,
                entityType: 'product',
                entityId: $product->id,
                request: $request
            );
        }

        return $product;
    }

    /**
     * Unpublish product (back to DRAFT).
     */
    public function unpublish(Product $product, ?int $adminUserId = null, ?Request $request = null): Product
    {
        $product->update([
            'status' => ProductStatus::DRAFT,
            'version' => $product->version + 1,
        ]);

        if ($adminUserId) {
            AuditLogger::log(
                'catalog.product.unpublished',
                AuditActorType::ADMIN,
                actorAdminUserId: $adminUserId,
                entityType: 'product',
                entityId: $product->id,
                request: $request
            );
        }

        return $product;
    }

    /**
     * Archive product.
     */
    public function archive(Product $product, ?int $adminUserId = null, ?Request $request = null): Product
    {
        $product->update([
            'status' => ProductStatus::ARCHIVED,
            'archived_at' => Carbon::now('UTC'),
            'version' => $product->version + 1,
        ]);

        if ($adminUserId) {
            AuditLogger::log(
                'catalog.product.archived',
                AuditActorType::ADMIN,
                actorAdminUserId: $adminUserId,
                entityType: 'product',
                entityId: $product->id,
                request: $request
            );
        }

        return $product;
    }

    /**
     * Restore archived product.
     */
    public function restore(Product $product, ?int $adminUserId = null, ?Request $request = null): Product
    {
        $product->update([
            'status' => ProductStatus::DRAFT,
            'archived_at' => null,
            'version' => $product->version + 1,
        ]);

        if ($adminUserId) {
            AuditLogger::log(
                'catalog.product.restored',
                AuditActorType::ADMIN,
                actorAdminUserId: $adminUserId,
                entityType: 'product',
                entityId: $product->id,
                request: $request
            );
        }

        return $product;
    }

    /**
     * Save product key-value specifications.
     *
     * @param array<int, array{label: string, value: string}> $attributes
     */
    public function setAttributes(Product $product, array $attributes, ?int $adminUserId = null, ?Request $request = null): void
    {
        DB::transaction(function () use ($product, $attributes, $adminUserId, $request) {
            ProductAttribute::where('product_id', $product->id)->delete();

            $insertData = [];
            $pos = 0;
            foreach ($attributes as $attr) {
                $label = trim($attr['label'] ?? '');
                $value = trim($attr['value'] ?? '');
                if ($label !== '' && $value !== '') {
                    $insertData[] = [
                        'product_id' => $product->id,
                        'label' => Str::limit($label, 40),
                        'value' => Str::limit($value, 120),
                        'position' => $pos++,
                    ];
                }
            }

            if (!empty($insertData)) {
                ProductAttribute::insert($insertData);
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.product.attributes_changed',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product',
                    entityId: $product->id,
                    metadata: ['attributes_count' => count($insertData)],
                    request: $request
                );
            }
        });
    }
}
