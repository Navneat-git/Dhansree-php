<?php

declare(strict_types=1);

namespace App\Services\Storefront;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CatalogBrowsingService
{
    /** Slugs or names identifying Women Fashion categories */
    public const WOMEN_FASHION_SLUGS = [
        'women-fashion',
        'kurta-set',
        'women-two-piece-sets-co-ord',
        'women-two-piece-sets-co-ords',
        'women-suit-set',
    ];

    /** Slugs or names identifying Smart Living / Essentials categories */
    public const SMART_LIVING_SLUGS = [
        'smart-gadgets',
        'mobile-accessories',
        'home-and-lifestyle',
        'car-accessories',
    ];

    /**
     * Determines whether a given category belongs to the Smart Living / Essentials hub.
     */
    public function isSmartLivingCategory(?Category $category): bool
    {
        if (!$category) {
            return false;
        }

        if (in_array($category->slug, self::SMART_LIVING_SLUGS, true)) {
            return true;
        }

        if ($category->parent && in_array($category->parent->slug, self::SMART_LIVING_SLUGS, true)) {
            return true;
        }

        return false;
    }

    /**
     * Data for Women's Fashion Homepage (Hub 1).
     */
    public function getWomenFashionHomeData(): array
    {
        $womenCategories = Category::whereNull('archived_at')
            ->where('is_visible', true)
            ->where(function (Builder $q) {
                $q->whereIn('slug', self::WOMEN_FASHION_SLUGS)
                  ->orWhereHas('parent', fn(Builder $pq) => $pq->whereIn('slug', self::WOMEN_FASHION_SLUGS));
            })
            ->orderBy('position')
            ->get();

        $featuredProducts = Product::with(['category', 'variants.inventoryLevels', 'primaryImage', 'images'])
            ->where('status', ProductStatus::ACTIVE)
            ->whereHas('category', function (Builder $cq) {
                $cq->whereIn('slug', self::WOMEN_FASHION_SLUGS)
                   ->orWhereHas('parent', fn(Builder $pq) => $pq->whereIn('slug', self::WOMEN_FASHION_SLUGS));
            })
            ->latest('published_at')
            ->take(8)
            ->get();

        $smartLivingCategories = Category::whereNull('archived_at')
            ->where('is_visible', true)
            ->whereIn('slug', self::SMART_LIVING_SLUGS)
            ->orderBy('position')
            ->get();

        $now = now();
        $banners = \App\Models\HomepageBanner::where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $store = StoreSettings::current();

        return [
            'categories' => $womenCategories,
            'featuredProducts' => $featuredProducts,
            'smartLivingCategories' => $smartLivingCategories,
            'banners' => $banners,
            'store' => $store,
        ];
    }

    /**
     * Data for Smart Living & Essentials Hub (Hub 2).
     */
    public function getSmartLivingHubData(): array
    {
        $smartCategories = Category::whereNull('archived_at')
            ->where('is_visible', true)
            ->where(function (Builder $q) {
                $q->whereIn('slug', self::SMART_LIVING_SLUGS)
                  ->orWhereHas('parent', fn(Builder $pq) => $pq->whereIn('slug', self::SMART_LIVING_SLUGS));
            })
            ->orderBy('position')
            ->get();

        $products = Product::with(['category', 'variants.inventoryLevels', 'primaryImage', 'images'])
            ->where('status', ProductStatus::ACTIVE)
            ->whereHas('category', function (Builder $cq) {
                $cq->whereIn('slug', self::SMART_LIVING_SLUGS)
                   ->orWhereHas('parent', fn(Builder $pq) => $pq->whereIn('slug', self::SMART_LIVING_SLUGS));
            })
            ->latest('published_at')
            ->get();

        $womenCategories = Category::whereNull('archived_at')
            ->where('is_visible', true)
            ->whereIn('slug', self::WOMEN_FASHION_SLUGS)
            ->orderBy('position')
            ->get();

        $store = StoreSettings::current();

        return [
            'categories' => $smartCategories,
            'products' => $products,
            'womenCategories' => $womenCategories,
            'store' => $store,
        ];
    }

    /**
     * Data for category collection page with filters.
     */
    public function getCategoryPageData(string $slug, array $filters = []): array
    {
        $category = Category::with('parent')
            ->where('slug', $slug)
            ->whereNull('archived_at')
            ->firstOrFail();

        // Include subcategory IDs
        $subIds = Category::where('parent_id', $category->id)
            ->whereNull('archived_at')
            ->pluck('id')
            ->all();

        $categoryIds = array_merge([$category->id], $subIds);

        $query = Product::with(['category', 'variants.inventoryLevels', 'primaryImage', 'images'])
            ->where('status', ProductStatus::ACTIVE)
            ->whereIn('category_id', $categoryIds);

        // Price range filter
        if (!empty($filters['min_price'])) {
            $minPaise = (int) round(((float) $filters['min_price']) * 100);
            $query->whereHas('variants', fn($vq) => $vq->where('price_paise', '>=', $minPaise));
        }
        if (!empty($filters['max_price'])) {
            $maxPaise = (int) round(((float) $filters['max_price']) * 100);
            $query->whereHas('variants', fn($vq) => $vq->where('price_paise', '<=', $maxPaise));
        }

        // In-stock only filter
        if (!empty($filters['in_stock'])) {
            $query->whereHas('variants.inventoryLevels', function (Builder $iq) {
                $iq->selectRaw('SUM(on_hand - reserved)')->havingRaw('SUM(on_hand - reserved) > 0');
            });
        }

        // Sort
        match ($filters['sort'] ?? 'popular') {
            'price_asc' => $query->orderBy(
                ProductVariant::select('price_paise')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->orderBy('price_paise', 'asc')
                    ->limit(1)
            ),
            'price_desc' => $query->orderByDesc(
                ProductVariant::select('price_paise')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->orderBy('price_paise', 'desc')
                    ->limit(1)
            ),
            'newest' => $query->orderByDesc('published_at'),
            default => $query->orderByDesc('id'),
        };

        $products = $query->paginate(16);

        $isSmartLiving = $this->isSmartLivingCategory($category);

        return [
            'category' => $category,
            'products' => $products,
            'isSmartLiving' => $isSmartLiving,
            'filters' => $filters,
        ];
    }

    /**
     * Data for product detail page.
     */
    public function getProductDetailPageData(string $slug): array
    {
        $product = Product::with([
            'category.parent',
            'variants' => fn($q) => $q->whereNull('archived_at')->orderBy('position')->with(['optionValues', 'inventoryLevels']),
            'options' => fn($q) => $q->orderBy('position')->with(['values' => fn($vq) => $vq->orderBy('position')]),
            'images' => fn($q) => $q->orderBy('position')->with(['productImageVariants', 'productImageOptionValues']),
            'productAttributes' => fn($q) => $q->orderBy('position'),
        ])
        ->where('slug', $slug)
        ->where('status', ProductStatus::ACTIVE)
        ->firstOrFail();

        // Related products in the same category
        $relatedProducts = Product::with(['primaryImage', 'variants.inventoryLevels'])
            ->where('status', ProductStatus::ACTIVE)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        $isSmartLiving = $this->isSmartLivingCategory($product->category);
        $store = StoreSettings::current();

        return [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'isSmartLiving' => $isSmartLiving,
            'store' => $store,
        ];
    }

    /**
     * Search products.
     */
    public function search(string $keyword, array $filters = []): LengthAwarePaginator
    {
        $clean = trim($keyword);
        $term = '%' . $clean . '%';

        $query = Product::with(['category', 'variants.inventoryLevels', 'primaryImage', 'images'])
            ->where('status', ProductStatus::ACTIVE)
            ->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('brand', 'like', $term)
                  ->orWhere('short_description', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhereHas('variants', fn(Builder $vq) => $vq->where('sku', 'like', $term));
            });

        return $query->paginate(16);
    }

    /**
     * Megamenu categories list categorized by Hub.
     */
    public function getMegamenuData(): array
    {
        $all = Category::whereNull('archived_at')
            ->where('is_visible', true)
            ->orderBy('position')
            ->get();

        $women = [];
        $smart = [];

        foreach ($all as $cat) {
            if ($this->isSmartLivingCategory($cat)) {
                $smart[] = $cat;
            } else {
                $women[] = $cat;
            }
        }

        return [
            'womenFashionCategories' => $women,
            'smartLivingCategories' => $smart,
        ];
    }
}
