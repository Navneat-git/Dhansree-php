<?php

declare(strict_types=1);

namespace App\Services\Storefront;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use App\Models\SizeChart;
use App\Models\StoreSettings;
use App\Models\WholesaleColorCode;
use App\Support\Catalog\ProductListingCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CatalogBrowsingService
{
    /** Slugs or names identifying Women Fashion categories */
    public const WOMEN_FASHION_SLUGS = [
        'women-fashion',
        'kurta-set',
        'women-two-piece-sets-co-ord',
        'women-two-piece-sets-co-ords',
        'women-suit-set',
        'anarkali-kurta-sets',
    ];

    /** Slugs or names identifying Smart Living / Essentials categories */
    public const SMART_LIVING_SLUGS = [
        'smart-gadgets',
        'mobile-accessories',
        'home-and-lifestyle',
        'car-accessories',
        'home-essentials',
        'kitchen-essentials',
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
     * Expands products into color-specific listing cards.
     * If a product has multiple colors, each color is yielded as a separate listing card.
     * If a product has no color options (e.g. single color, or smart-living), yields a standard listing card.
     *
     * @param iterable<Product> $products
     * @param string[]|null $colorFilter Active color filters to restrict to
     * @return Collection<int, ProductListingCard>
     */
    public function expandProductsForListing(iterable $products, ?array $colorFilter = null): Collection
    {
        $cards = collect();
        $normalizedColorFilter = !empty($colorFilter)
            ? array_map(fn($c) => strtolower(trim((string) $c)), $colorFilter)
            : null;

        foreach ($products as $product) {
            $liveVariants = $product->variants->whereNull('archived_at');
            $colorOption = $product->options->first(fn($o) => in_array(strtolower(trim($o->name)), ['color', 'colour', 'shade', 'rang'], true));

            $colorCardsGenerated = 0;

            if ($colorOption && $colorOption->values->isNotEmpty()) {
                foreach ($colorOption->values as $cVal) {
                    $colorName = trim((string) $cVal->value);
                    $normColor = strtolower($colorName);

                    // If color filter is active, skip non-matching colors
                    if ($normalizedColorFilter !== null && !in_array($normColor, $normalizedColorFilter, true)) {
                        continue;
                    }

                    // Variants matching this color
                    $varsForColor = $liveVariants->filter(function ($v) use ($cVal) {
                        return $v->optionValues->contains('id', $cVal->id);
                    });

                    // If option values aren't linked on legacy variants, fallback to SKU matching
                    if ($varsForColor->isEmpty()) {
                        $colorCode = WholesaleColorCode::resolveCode($colorName);
                        $varsForColor = $liveVariants->filter(function ($v) use ($colorCode, $colorName) {
                            $sku = strtoupper($v->sku);
                            return str_contains($sku, '-' . $colorCode) || str_contains($sku, strtoupper($colorName));
                        });
                    }

                    if ($varsForColor->isEmpty()) {
                        continue;
                    }

                    // Find image specifically assigned to this color
                    $varIds = $varsForColor->pluck('id')->all();
                    $colorImage = $product->images->first(function ($img) use ($cVal, $varIds) {
                        return $img->productImageOptionValues->contains('option_value_id', $cVal->id)
                            || $img->productImageVariants->whereIn('variant_id', $varIds)->isNotEmpty();
                    }) ?? $product->primaryImage ?? $product->images->first();

                    $imageUrl = $colorImage?->url ?? $product->primary_image_url;

                    // Pricing calculation for this color's variants
                    $minPriceRupees = (float) ($varsForColor->where('price_rupees', '>', 0)->min('price_rupees') ?? $product->min_price_rupees);
                    $maxMrpRupees = (float) ($varsForColor->where('mrp_rupees', '>', 0)->max('mrp_rupees') ?? $product->mrp_rupees);

                    $formattedPrice = $minPriceRupees > 0 ? '₹' . number_format($minPriceRupees, 0) : $product->formatted_price;
                    $formattedMrp = ($maxMrpRupees > $minPriceRupees && $minPriceRupees > 0) ? '₹' . number_format($maxMrpRupees, 0) : null;
                    $discountPct = ($maxMrpRupees > $minPriceRupees && $maxMrpRupees > 0)
                        ? (int) round((($maxMrpRupees - $minPriceRupees) / $maxMrpRupees) * 100)
                        : null;

                    // In-stock variant
                    $firstVar = $varsForColor->first(function ($v) {
                        $stock = $v->inventoryLevels->sum('on_hand') - $v->inventoryLevels->sum('reserved');
                        return $stock > 0;
                    }) ?? $varsForColor->first();

                    $inStock = $varsForColor->some(function ($v) {
                        return ($v->inventoryLevels->sum('on_hand') - $v->inventoryLevels->sum('reserved')) > 0;
                    });

                    $colorCode = WholesaleColorCode::resolveCode($colorName);
                    $colorHex = WholesaleColorCode::resolveHex($colorName);
                    $url = url('/product/' . $product->slug . '?color=' . urlencode($colorName));

                    $cards->push(new ProductListingCard(
                        product: $product,
                        name: $product->name,
                        slug: $product->slug,
                        brand: $product->brand ?? 'DHANSREE ETHNIC',
                        colorName: $colorName,
                        colorCode: $colorCode,
                        colorHex: $colorHex,
                        primaryImageUrl: $imageUrl,
                        url: $url,
                        formattedPrice: $formattedPrice,
                        formattedMrp: $formattedMrp,
                        discountPercentage: $discountPct,
                        firstVariant: $firstVar,
                        variants: $varsForColor->values(),
                        hasMultipleVariants: $varsForColor->count() > 1,
                        inStock: $inStock,
                    ));

                    $colorCardsGenerated++;
                }
            }

            // Fallback: If no color cards generated (e.g. product without color option),
            // and no color filter was explicitly restricting it, yield standard product card
            if ($colorCardsGenerated === 0 && empty($normalizedColorFilter)) {
                $firstVar = $liveVariants->first(function ($v) {
                    $stock = $v->inventoryLevels->sum('on_hand') - $v->inventoryLevels->sum('reserved');
                    return $stock > 0;
                }) ?? $liveVariants->first();

                $inStock = $liveVariants->some(function ($v) {
                    return ($v->inventoryLevels->sum('on_hand') - $v->inventoryLevels->sum('reserved')) > 0;
                });

                $cards->push(new ProductListingCard(
                    product: $product,
                    name: $product->name,
                    slug: $product->slug,
                    brand: $product->brand ?? 'DHANSREE',
                    colorName: null,
                    colorCode: null,
                    colorHex: null,
                    primaryImageUrl: $product->primary_image_url ?? $product->images->first()?->url,
                    url: url('/product/' . $product->slug),
                    formattedPrice: $product->formatted_price,
                    formattedMrp: $product->formatted_mrp,
                    discountPercentage: $product->discount_percentage,
                    firstVariant: $firstVar,
                    variants: $liveVariants->values(),
                    hasMultipleVariants: $liveVariants->count() > 1,
                    inStock: $inStock,
                ));
            }
        }

        return $cards;
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

        $featuredProducts = Product::with([
            'category',
            'variants.inventoryLevels',
            'variants.optionValues.option',
            'primaryImage',
            'images.productImageOptionValues',
            'images.productImageVariants',
            'options.values',
        ])
            ->where('status', ProductStatus::ACTIVE)
            ->whereHas('category', function (Builder $cq) {
                $cq->whereIn('slug', self::WOMEN_FASHION_SLUGS)
                   ->orWhereHas('parent', fn(Builder $pq) => $pq->whereIn('slug', self::WOMEN_FASHION_SLUGS));
            })
            ->latest('published_at')
            ->take(8)
            ->get();

        $featuredCards = $this->expandProductsForListing($featuredProducts);

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
        $heroBlock = \App\Models\HomeBlock::where('type', \App\Enums\HomeBlockType::HERO)->where('is_visible', true)->first();

        return [
            'categories' => $womenCategories,
            'featuredProducts' => $featuredCards,
            'smartLivingCategories' => $smartLivingCategories,
            'banners' => $banners,
            'store' => $store,
            'heroBlock' => $heroBlock,
            'quickLinksData' => $this->resolveQuickLinksForHome(),
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
    /**
     * Resolves all category IDs for a given category (including all descendants and women's fashion umbrella).
     */
    public function getAllCategoryIds(Category $category): array
    {
        if ($category->slug === 'women-fashion') {
            return Category::whereNull('archived_at')
                ->where(function (Builder $q) use ($category) {
                    $q->whereIn('slug', self::WOMEN_FASHION_SLUGS)
                      ->orWhere('id', $category->id)
                      ->orWhere('parent_id', $category->id)
                      ->orWhereHas('parent', fn(Builder $pq) => $pq->whereIn('slug', self::WOMEN_FASHION_SLUGS));
                })
                ->pluck('id')
                ->all();
        }

        $ids = [$category->id];
        $currentParentIds = [$category->id];

        while (!empty($currentParentIds)) {
            $childIds = Category::whereIn('parent_id', $currentParentIds)
                ->whereNull('archived_at')
                ->pluck('id')
                ->all();

            if (empty($childIds)) {
                break;
            }

            $ids = array_merge($ids, $childIds);
            $currentParentIds = $childIds;
        }

        return array_values(array_unique($ids));
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

        $categoryIds = $this->getAllCategoryIds($category);
        $listing = $this->executeListingQuery($categoryIds, $filters);

        return [
            'category' => $category,
            'products' => $listing['products'],
            'productCards' => $listing['productCards'],
            'isSmartLiving' => $this->isSmartLivingCategory($category),
            'filters' => $filters,
            'availableOptions' => $listing['availableOptions'],
            'selectedOptions' => $listing['selectedOptions'],
            'quickLinksData' => $this->resolveQuickLinksForCategory($category),
        ];
    }

    /**
     * Data for collection page with filters (gathers multiple categories).
     */
    public function getCollectionPageData(string $slug, array $filters = []): array
    {
        $collection = \App\Models\Collection::with('categories')
            ->where('slug', $slug)
            ->first();

        if (!$collection) {
            if ($slug === 'women-fashion') {
                $collection = new \App\Models\Collection([
                    'name' => 'Women Fashion',
                    'slug' => 'women-fashion',
                    'heading' => "Women's Ethnic Wear & Festive Edit",
                    'intro' => 'Explore handcrafted pure cotton kurta sets, contemporary two-piece co-ords, regal anarkalis, and celebratory suit sets.',
                    'is_visible' => true,
                    'seo_title' => "Women's Ethnic Wear, Kurtis, Co-ords & Suits Online | Dhansree",
                    'seo_description' => "Shop women's ethnic fashion online at Dhansree. Handcrafted cotton suits with dupattas, co-ords, anarkalis and kurta sets with free express shipping.",
                ]);
            } elseif ($slug === 'everyday-essentials') {
                $collection = new \App\Models\Collection([
                    'name' => 'Everyday Essentials',
                    'slug' => 'everyday-essentials',
                    'heading' => 'Everyday Essentials & Smart Living',
                    'intro' => 'Discover quality-tested smart gadgets, fast chargers, multi-cables, and home essentials at factory-direct prices.',
                    'is_visible' => true,
                    'seo_title' => 'Everyday Essentials, Tech Accessories & Smart Gadgets | Dhansree',
                    'seo_description' => 'Explore fast charging cables, compact steamers, lifestyle accessories, and smart home essentials with direct customer value.',
                ]);
            } else {
                abort(404, 'Collection not found.');
            }
        }

        $categoryIds = [];
        if ($collection->relationLoaded('categories') && $collection->categories->isNotEmpty()) {
            foreach ($collection->categories as $cat) {
                $categoryIds = array_merge($categoryIds, $this->getAllCategoryIds($cat));
            }
        } elseif ($slug === 'women-fashion') {
            $categoryIds = Category::whereNull('archived_at')
                ->where(function (Builder $q) {
                    $q->whereIn('slug', self::WOMEN_FASHION_SLUGS)
                      ->orWhere('parent_id', 3)
                      ->orWhereHas('parent', fn(Builder $pq) => $pq->whereIn('slug', self::WOMEN_FASHION_SLUGS));
                })
                ->pluck('id')
                ->all();
        } elseif ($slug === 'everyday-essentials') {
            $categoryIds = Category::whereNull('archived_at')
                ->where(function (Builder $q) {
                    $q->whereIn('slug', self::SMART_LIVING_SLUGS)
                      ->orWhere('parent_id', 4)
                      ->orWhereHas('parent', fn(Builder $pq) => $pq->whereIn('slug', self::SMART_LIVING_SLUGS));
                })
                ->pluck('id')
                ->all();
        }

        $categoryIds = array_values(array_unique($categoryIds));
        $listing = $this->executeListingQuery($categoryIds, $filters);

        $isSmartLiving = ($slug === 'everyday-essentials') || (
            $collection->relationLoaded('categories') &&
            $collection->categories->isNotEmpty() &&
            $this->isSmartLivingCategory($collection->categories->first())
        );

        return [
            'collection' => $collection,
            'products' => $listing['products'],
            'productCards' => $listing['productCards'],
            'isSmartLiving' => $isSmartLiving,
            'filters' => $filters,
            'availableOptions' => $listing['availableOptions'],
            'selectedOptions' => $listing['selectedOptions'],
            'quickLinksData' => $this->resolveQuickLinksForCollection($collection),
        ];
    }

    /**
     * Executes the filtered, sorted, paginated product query and dynamic option aggregation for given category IDs.
     */
    protected function executeListingQuery(array $categoryIds, array $filters = []): array
    {
        // Fetch all active product IDs in these categories
        $baseActiveProductIds = Product::where('status', ProductStatus::ACTIVE)
            ->whereIn('category_id', $categoryIds)
            ->pluck('id');

        // Dynamically discover all option dimensions and their distinct values
        $options = ProductOption::whereIn('product_id', $baseActiveProductIds)
            ->with(['values' => fn($q) => $q->orderBy('position')->orderBy('id')])
            ->orderBy('position')
            ->get();

        $availableOptions = [];
        foreach ($options as $opt) {
            $rawName = trim((string) $opt->name);
            if ($rawName === '') {
                continue;
            }
            $dimName = ucfirst($rawName);
            if (!isset($availableOptions[$dimName])) {
                $availableOptions[$dimName] = collect();
            }
            foreach ($opt->values as $valObj) {
                $vStr = trim((string) $valObj->value);
                if ($vStr !== '') {
                    $availableOptions[$dimName]->push($vStr);
                }
            }
        }

        // De-duplicate and order values per option dimension
        foreach ($availableOptions as $dimName => $vals) {
            $uniqueVals = $vals->unique()->values()->all();
            if (in_array(strtolower($dimName), ['size', 'sizes', 'fit'])) {
                $sizeOrder = ['XXS' => 1, 'XS' => 2, 'S' => 3, 'M' => 4, 'L' => 5, 'XL' => 6, 'XXL' => 7, '2XL' => 7, '3XL' => 8, '4XL' => 9, '5XL' => 10, 'Free Size' => 11];
                usort($uniqueVals, function ($a, $b) use ($sizeOrder) {
                    $aOrder = $sizeOrder[strtoupper(trim($a))] ?? 99;
                    $bOrder = $sizeOrder[strtoupper(trim($b))] ?? 99;
                    if ($aOrder !== $bOrder) {
                        return $aOrder <=> $bOrder;
                    }
                    return strnatcasecmp($a, $b);
                });
            } else {
                natcasesort($uniqueVals);
                $uniqueVals = array_values($uniqueVals);
            }
            $availableOptions[$dimName] = $uniqueVals;
        }
        $availableOptions = array_filter($availableOptions, fn($vals) => !empty($vals));

        // Parse and validate selected option filters from request
        $selectedOptions = [];
        foreach ($availableOptions as $dimName => $availVals) {
            $paramVal = null;
            $lowerDim = strtolower($dimName);
            $slugDim = Str::slug($dimName, '_');

            if (isset($filters['options'][$dimName])) {
                $paramVal = $filters['options'][$dimName];
            } elseif (isset($filters['options'][$lowerDim])) {
                $paramVal = $filters['options'][$lowerDim];
            } elseif (isset($filters[$lowerDim])) {
                $paramVal = $filters[$lowerDim];
            } elseif (isset($filters[$slugDim])) {
                $paramVal = $filters[$slugDim];
            } elseif (isset($filters[$dimName])) {
                $paramVal = $filters[$dimName];
            }

            if ($paramVal !== null && $paramVal !== '') {
                $rawVals = is_array($paramVal) ? $paramVal : explode(',', (string) $paramVal);
                $cleanVals = array_filter(array_map('trim', $rawVals), fn($v) => $v !== '');
                if (!empty($cleanVals)) {
                    $validSelected = [];
                    foreach ($availVals as $av) {
                        foreach ($cleanVals as $cv) {
                            if (strcasecmp($av, $cv) === 0) {
                                $validSelected[] = $av;
                                break;
                            }
                        }
                    }
                    if (!empty($validSelected)) {
                        $selectedOptions[$dimName] = array_values(array_unique($validSelected));
                    }
                }
            }
        }

        $query = Product::with([
            'category',
            'variants.inventoryLevels',
            'variants.optionValues.option',
            'primaryImage',
            'images.productImageOptionValues',
            'images.productImageVariants',
            'options.values',
        ])
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

        // Apply dynamic option filters (e.g. Color, Size, etc.)
        foreach ($selectedOptions as $dimName => $vals) {
            $query->whereHas('options', function (Builder $oq) use ($dimName, $vals) {
                $oq->whereRaw('LOWER(name) = ?', [strtolower($dimName)])
                   ->whereHas('values', function (Builder $vq) use ($vals) {
                       $vq->whereIn('value', $vals);
                   });
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

        $products = $query->paginate(16)->withQueryString();
        $productCards = $this->expandProductsForListing($products->items(), $selectedOptions['Color'] ?? null);

        return [
            'products' => $products,
            'productCards' => $productCards,
            'availableOptions' => $availableOptions,
            'selectedOptions' => $selectedOptions,
        ];
    }

    /**
     * Data for product detail page.
     */
    public function getProductDetailPageData(string $slug): array
    {
        $product = Product::with([
            'category.parent',
            'productContent',
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
            'quickLinksData' => $this->resolveQuickLinksForProduct($product),
            'faqs' => $this->resolveFaqsForProduct($product),
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

        $query = Product::with([
            'category',
            'variants.inventoryLevels',
            'variants.optionValues.option',
            'primaryImage',
            'images.productImageOptionValues',
            'images.productImageVariants',
            'options.values',
        ])
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
        match ($filters['sort'] ?? 'relevance') {
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

        return $query->paginate(16)->withQueryString();
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

    /**
     * Resolve Quick Links for Homepage.
     */
    public function resolveQuickLinksForHome(): ?array
    {
        $group = \App\Models\QuickLinkGroup::with(['quickLinks' => fn($q) => $q->orderBy('position')->orderBy('id')])
            ->where('is_visible', true)
            ->where('show_on_home', true)
            ->orderBy('position')
            ->orderByDesc('id')
            ->first();

        if (!$group || $group->quickLinks->isEmpty()) {
            return null;
        }

        return [
            'title' => $group->title,
            'links' => $group->quickLinks,
        ];
    }

    /**
     * Resolve Quick Links for Category listing page.
     */
    public function resolveQuickLinksForCategory(?Category $category): ?array
    {
        if (!$category) {
            return null;
        }

        $categoryContent = \App\Models\CategoryContent::where('category_id', $category->id)->first();
        $mode = $categoryContent?->quick_links_mode ?? 'INHERIT';

        if (in_array($mode, ['NONE', 'HIDE'], true)) {
            return null;
        }

        if ($mode === 'CUSTOM' && $categoryContent?->quick_link_group_id) {
            $group = \App\Models\QuickLinkGroup::with(['quickLinks' => fn($q) => $q->orderBy('position')->orderBy('id')])
                ->where('is_visible', true)
                ->where('id', $categoryContent->quick_link_group_id)
                ->first();

            if ($group && $group->quickLinks->isNotEmpty()) {
                return ['title' => $group->title, 'links' => $group->quickLinks];
            }
            return null;
        }

        // INHERIT: find first visible group with show_on_listing
        $group = \App\Models\QuickLinkGroup::with(['quickLinks' => fn($q) => $q->orderBy('position')->orderBy('id')])
            ->where('is_visible', true)
            ->where('show_on_listing', true)
            ->orderBy('position')
            ->orderByDesc('id')
            ->first();

        if ($group && $group->quickLinks->isNotEmpty()) {
            return ['title' => $group->title, 'links' => $group->quickLinks];
        }

        return null;
    }

    /**
     * Resolve Quick Links for Curated Collection page.
     */
    public function resolveQuickLinksForCollection(?\App\Models\Collection $collection): ?array
    {
        if ($collection && $collection->quick_link_group_id) {
            $group = \App\Models\QuickLinkGroup::with(['quickLinks' => fn($q) => $q->orderBy('position')->orderBy('id')])
                ->where('is_visible', true)
                ->where('id', $collection->quick_link_group_id)
                ->first();

            if ($group && $group->quickLinks->isNotEmpty()) {
                return ['title' => $group->title, 'links' => $group->quickLinks];
            }
        }

        // Default listing group
        $group = \App\Models\QuickLinkGroup::with(['quickLinks' => fn($q) => $q->orderBy('position')->orderBy('id')])
            ->where('is_visible', true)
            ->where('show_on_listing', true)
            ->orderBy('position')
            ->orderByDesc('id')
            ->first();

        if ($group && $group->quickLinks->isNotEmpty()) {
            return ['title' => $group->title, 'links' => $group->quickLinks];
        }

        return null;
    }

    /**
     * Resolve Quick Links for Product Detail page.
     */
    public function resolveQuickLinksForProduct(?Product $product): ?array
    {
        if (!$product) {
            return null;
        }

        $productContent = $product->relationLoaded('productContent')
            ? $product->productContent
            : \App\Models\ProductContent::where('product_id', $product->id)->first();

        $mode = $productContent?->quick_links_mode ?? 'INHERIT';

        if (in_array($mode, ['NONE', 'HIDE'], true)) {
            return null;
        }

        if ($mode === 'CUSTOM' && $productContent?->quick_link_group_id) {
            $group = \App\Models\QuickLinkGroup::with(['quickLinks' => fn($q) => $q->orderBy('position')->orderBy('id')])
                ->where('is_visible', true)
                ->where('id', $productContent->quick_link_group_id)
                ->first();

            if ($group && $group->quickLinks->isNotEmpty()) {
                return ['title' => $group->title, 'links' => $group->quickLinks];
            }
            return null;
        }

        // INHERIT: check category quick links first, then check show_on_product
        if ($product->category) {
            $catLinks = $this->resolveQuickLinksForCategory($product->category);
            if ($catLinks) {
                return $catLinks;
            }
        }

        $group = \App\Models\QuickLinkGroup::with(['quickLinks' => fn($q) => $q->orderBy('position')->orderBy('id')])
            ->where('is_visible', true)
            ->where('show_on_product', true)
            ->orderBy('position')
            ->orderByDesc('id')
            ->first();

        if ($group && $group->quickLinks->isNotEmpty()) {
            return ['title' => $group->title, 'links' => $group->quickLinks];
        }

        return null;
    }

    /**
     * Resolve FAQs for Product Detail page.
     */
    public function resolveFaqsForProduct(Product $product): \Illuminate\Support\Collection
    {
        $productContent = $product->relationLoaded('productContent')
            ? $product->productContent
            : \App\Models\ProductContent::where('product_id', $product->id)->first();

        $mode = $productContent?->faq_mode ?? 'INHERIT';

        if (in_array($mode, ['NONE', 'HIDE'], true)) {
            return collect();
        }

        if ($mode === 'CUSTOM') {
            $customFaqs = \App\Models\FaqEntry::where('product_id', $product->id)
                ->where('is_visible', true)
                ->orderBy('position')
                ->get();

            if ($customFaqs->isNotEmpty()) {
                return $customFaqs;
            }
            // Fallback to default FAQs if custom was chosen but none added yet
        }

        // Default Store FAQs
        return \App\Models\FaqEntry::whereNull('product_id')
            ->whereNull('category_id')
            ->where('is_visible', true)
            ->orderBy('position')
            ->get();
    }
}
