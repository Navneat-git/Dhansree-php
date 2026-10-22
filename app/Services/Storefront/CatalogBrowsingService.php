<?php

declare(strict_types=1);

namespace App\Services\Storefront;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SizeChart;
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

        $current = $category;
        while ($current) {
            if (in_array($current->slug, self::WOMEN_FASHION_SLUGS, true)) {
                return false;
            }
            if (in_array($current->slug, self::SMART_LIVING_SLUGS, true)) {
                return true;
            }
            $current = $current->parent_id ? Category::find($current->parent_id) : null;
        }

        return true;
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

        $featuredProducts = Product::with(['category', 'variants.inventoryLevels', 'variants.optionValues.option', 'primaryImage', 'images'])
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

        $featuredWomenProduct = Product::with(['primaryImage', 'images'])
            ->where('status', ProductStatus::ACTIVE)
            ->whereHas('category', function (Builder $cq) {
                $cq->whereIn('slug', self::WOMEN_FASHION_SLUGS)
                   ->orWhereHas('parent', fn(Builder $pq) => $pq->whereIn('slug', self::WOMEN_FASHION_SLUGS));
            })
            ->latest('published_at')
            ->first();

        $store = StoreSettings::current();

        return [
            'categories' => $smartCategories,
            'products' => $products,
            'womenCategories' => $womenCategories,
            'featuredWomenProduct' => $featuredWomenProduct,
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
                $iq->whereRaw('(on_hand - reserved) > 0');
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
        $sizeChart = $isSmartLiving ? null : $this->resolveSizeChartForCategory($product->category);
        $store = StoreSettings::current();

        return [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'sizeChart' => $sizeChart,
            'isSmartLiving' => $isSmartLiving,
            'store' => $store,
        ];
    }

    /**
     * Resolve size chart for a category, walking up parent category hierarchy.
     */
    public function resolveSizeChartForCategory(?Category $category): ?array
    {
        if (!$category) {
            return null;
        }

        // Build chain of category IDs: leaf first, then parents up to root
        $chainIds = [];
        $currentId = $category->id;
        while ($currentId) {
            $chainIds[] = $currentId;
            $parent = Category::where('id', $currentId)->value('parent_id');
            $currentId = $parent ? (int) $parent : null;
        }

        $found = null;
        if (!empty($chainIds)) {
            $charts = SizeChart::whereIn('category_id', $chainIds)
                ->with(['rows' => fn($q) => $q->orderBy('position')->orderBy('id')])
                ->get()
                ->keyBy('category_id');

            // Pick nearest category in chain (leaf first)
            foreach ($chainIds as $catId) {
                if ($charts->has($catId)) {
                    $found = $charts->get($catId);
                    break;
                }
            }
        }

        // If no direct chart was found on this category/parent, but it is a Women's Fashion category,
        // fall back to the default Women's ethnic wear size chart.
        if (!$found && !$this->isSmartLivingCategory($category)) {
            $found = SizeChart::with(['rows' => fn($q) => $q->orderBy('position')->orderBy('id')])->first();
        }

        if (!$found) {
            return null;
        }

        $columns = is_array($found->columns) ? $found->columns : (json_decode((string) ($found->columns ?? '[]'), true) ?: []);
        $rawRows = $found->rows ?? collect();

        $rows = [];
        foreach ($rawRows as $row) {
            $rowVals = is_array($row->values) ? $row->values : (json_decode((string) ($row->values ?? '[]'), true) ?: []);
            $aligned = [];
            foreach ($columns as $idx => $col) {
                $val = isset($rowVals[$idx]) ? trim((string) $rowVals[$idx]) : '';
                $aligned[] = $val;
            }
            $rows[] = [
                'label' => $row->label,
                'values' => $aligned,
            ];
        }

        $image = null;
        if ($found->image_url) {
            $image = [
                'url' => $found->image_url,
                'alt_text' => $found->image_alt_text ?? $found->title,
                'width' => $found->image_width,
                'height' => $found->image_height,
            ];
        }

        $hasTable = count($columns) > 0 && count($rows) > 0;
        if (!$hasTable && !$image) {
            return null;
        }

        return [
            'id' => $found->id,
            'title' => $found->title ?? 'Size guide',
            'note' => $found->note,
            'columns' => $columns,
            'rows' => $rows,
            'image' => $image,
            'picture_is_the_chart' => (count($rows) === 0 && !empty($image)),
        ];
    }

    /**
     * Search products.
     */
    public function search(string $keyword, array $filters = []): LengthAwarePaginator
    {
        $clean = trim($keyword);
        if ($clean === '') {
            return new LengthAwarePaginator([], 0, 16);
        }

        $term = '%' . $clean . '%';
        $words = array_values(array_filter(explode(' ', $clean), fn($w) => mb_strlen($w) > 1));

        $query = Product::with(['category', 'variants.inventoryLevels', 'primaryImage', 'images'])
            ->where('status', ProductStatus::ACTIVE)
            ->where(function (Builder $q) use ($clean, $term, $words) {
                // Exact phrase matching across product fields, category, or SKU
                $q->where('name', 'like', $term)
                  ->orWhere('brand', 'like', $term)
                  ->orWhere('short_description', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhereHas('category', fn(Builder $cq) => $cq->where('name', 'like', $term)->orWhere('slug', 'like', $term))
                  ->orWhereHas('variants', fn(Builder $vq) => $vq->where('sku', 'like', $term))
                  ->orWhereHas('productAttributes', fn(Builder $aq) => $aq->where('value', 'like', $term));

                // Multi-word matching: match all words in title/description/category
                if (count($words) > 1) {
                    $q->orWhere(function (Builder $sub) use ($words) {
                        foreach ($words as $word) {
                            $stem = preg_replace('/(ies|es|s)$/i', '', $word);
                            $wTerm = '%' . (mb_strlen($stem) >= 3 ? $stem : $word) . '%';
                            $sub->where(function (Builder $wq) use ($wTerm) {
                                $wq->where('name', 'like', $wTerm)
                                   ->orWhere('short_description', 'like', $wTerm)
                                   ->orWhere('description', 'like', $wTerm)
                                   ->orWhereHas('category', fn(Builder $cq) => $cq->where('name', 'like', $wTerm)->orWhere('slug', 'like', $wTerm));
                            });
                        }
                    });
                } else {
                    // Single word plural/stem fallback (e.g. kurties -> kurt, suits -> suit)
                    $single = $words[0] ?? $clean;
                    $stem = preg_replace('/(ies|es|s)$/i', '', $single);
                    if (mb_strlen($stem) >= 3 && strtolower($stem) !== strtolower($single)) {
                        $sTerm = '%' . $stem . '%';
                        $q->orWhere('name', 'like', $sTerm)
                          ->orWhere('short_description', 'like', $sTerm)
                          ->orWhere('description', 'like', $sTerm)
                          ->orWhereHas('category', fn(Builder $cq) => $cq->where('name', 'like', $sTerm)->orWhere('slug', 'like', $sTerm));
                    }
                }
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
