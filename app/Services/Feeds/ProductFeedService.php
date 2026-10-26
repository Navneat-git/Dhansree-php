<?php

declare(strict_types=1);

namespace App\Services\Feeds;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductFeedService
{
    public const META_FILENAME = 'meta_catalog.csv';
    public const GOOGLE_FILENAME = 'google_merchant.csv';

    /**
     * Column headers for Meta Product Catalog (Facebook & Instagram Shop).
     */
    public const META_COLUMNS = [
        'id',
        'title',
        'description',
        'availability',
        'condition',
        'link',
        'image_link',
        'brand',
        'google_product_category',
        'fb_product_category',
        'price',
        'sale_price',
        'sale_price_effective_date',
        'item_group_id',
        'gender',
        'color',
        'size',
        'age_group',
        'material',
        'pattern',
        'shipping',
        'shipping_weight',
        'offer_disclaimer',
        'offer_disclaimer_url',
        'video[0].url',
        'video[0].tag[0]',
        'gtin',
        'product_tags[0]',
        'product_tags[1]',
        'style[0]',
    ];

    /**
     * Column headers for Google Merchant Center Product Feed.
     */
    public const GOOGLE_COLUMNS = [
        'id',
        'title',
        'description',
        'link',
        'image_link',
        'additional_image_link',
        'availability',
        'price',
        'sale_price',
        'google_product_category',
        'brand',
        'gtin',
        'mpn',
        'identifier_exists',
        'condition',
        'adult',
        'gender',
        'age_group',
        'color',
        'size',
        'item_group_id',
        'shipping',
        'shipping_weight',
    ];

    /**
     * Generate both feeds and save them to the public folder.
     */
    public function generateAllFeeds(): array
    {
        $metaContent = $this->generateMetaCatalogCsv();
        $googleContent = $this->generateGoogleMerchantCsv();

        return [
            'meta' => $this->saveFeed(self::META_FILENAME, $metaContent, ['meta-catalog.csv']),
            'google' => $this->saveFeed(self::GOOGLE_FILENAME, $googleContent, ['google-merchant.csv']),
        ];
    }

    /**
     * Generate CSV content for Meta Business Catalog.
     */
    public function generateMetaCatalogCsv(): string
    {
        $products = $this->getActiveProducts();
        $stream = fopen('php://temp', 'r+');

        // Write CSV header
        fputcsv($stream, self::META_COLUMNS);

        $rowCount = 0;
        foreach ($products as $product) {
            $items = $this->extractProductFeedItems($product);
            foreach ($items as $item) {
                $row = [
                    $item['id'],
                    $item['title'],
                    $item['description'],
                    $item['meta_availability'],
                    $item['condition'],
                    $item['link'],
                    $item['image_link'],
                    $item['brand'],
                    $item['google_product_category'],
                    $item['fb_product_category'],
                    $item['price'],
                    $item['sale_price'],
                    $item['sale_price_effective_date'],
                    $item['item_group_id'],
                    $item['gender'],
                    $item['color'],
                    $item['size'],
                    $item['age_group'],
                    $item['material'],
                    $item['pattern'],
                    $item['shipping'],
                    $item['shipping_weight'],
                    $item['offer_disclaimer'],
                    $item['offer_disclaimer_url'],
                    $item['video_url'],
                    $item['video_tag'],
                    $item['gtin'],
                    $item['product_tags'][0] ?? '',
                    $item['product_tags'][1] ?? '',
                    $item['style'],
                ];
                fputcsv($stream, $row);
                $rowCount++;
            }
        }

        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        // Also persist to public feeds directory
        $this->saveFeed(self::META_FILENAME, $csvContent, ['meta-catalog.csv']);

        return $csvContent;
    }

    /**
     * Generate CSV content for Google Merchant Center.
     */
    public function generateGoogleMerchantCsv(): string
    {
        $products = $this->getActiveProducts();
        $stream = fopen('php://temp', 'r+');

        // Write CSV header
        fputcsv($stream, self::GOOGLE_COLUMNS);

        $rowCount = 0;
        foreach ($products as $product) {
            $items = $this->extractProductFeedItems($product);
            foreach ($items as $item) {
                $row = [
                    $item['id'],
                    $item['title'],
                    $item['description'],
                    $item['link'],
                    $item['image_link'],
                    $item['additional_image_link'],
                    $item['google_availability'],
                    $item['price'],
                    $item['sale_price'],
                    $item['google_product_category'],
                    $item['brand'],
                    $item['gtin'],
                    $item['mpn'],
                    $item['identifier_exists'],
                    $item['condition'],
                    $item['adult'],
                    $item['gender'],
                    $item['age_group'],
                    $item['color'],
                    $item['size'],
                    $item['item_group_id'],
                    $item['shipping_google'],
                    $item['shipping_weight_google'],
                ];
                fputcsv($stream, $row);
                $rowCount++;
            }
        }

        rewind($stream);
        $csvContent = stream_get_contents($stream);
        fclose($stream);

        // Also persist to public feeds directory
        $this->saveFeed(self::GOOGLE_FILENAME, $csvContent, ['google-merchant.csv']);

        return $csvContent;
    }

    /**
     * Get feed status, URLs, file sizes, and sample rows for the admin view.
     */
    public function getFeedStatus(): array
    {
        $metaPath = public_path('feeds/' . self::META_FILENAME);
        $googlePath = public_path('feeds/' . self::GOOGLE_FILENAME);

        return [
            'meta' => $this->inspectFeedFile(self::META_FILENAME, $metaPath, self::META_COLUMNS),
            'google' => $this->inspectFeedFile(self::GOOGLE_FILENAME, $googlePath, self::GOOGLE_COLUMNS),
        ];
    }

    /**
     * Inspect a feed file for metadata and preview rows.
     */
    private function inspectFeedFile(string $filename, string $path, array $expectedColumns): array
    {
        $exists = file_exists($path);
        $meta = [
            'filename' => $filename,
            'path' => $path,
            'url' => url('feeds/' . $filename),
            'exists' => $exists,
            'size_bytes' => $exists ? (int) filesize($path) : 0,
            'size_formatted' => $exists ? $this->formatFileSize((int) filesize($path)) : '0 B',
            'last_modified' => $exists ? Carbon::createFromTimestamp(filemtime($path)) : null,
            'last_modified_human' => $exists ? Carbon::createFromTimestamp(filemtime($path))->diffForHumans() : 'Never generated',
            'rows_count' => 0,
            'preview_rows' => [],
            'columns' => $expectedColumns,
        ];

        if ($exists) {
            $handle = fopen($path, 'r');
            if ($handle !== false) {
                $header = fgetcsv($handle);
                $rows = [];
                $count = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    $count++;
                    if (count($rows) < 5) {
                        $rows[] = array_combine($expectedColumns, array_pad($row, count($expectedColumns), ''));
                    }
                }
                fclose($handle);
                $meta['rows_count'] = $count;
                $meta['preview_rows'] = $rows;
            }
        }

        return $meta;
    }

    /**
     * Save feed content to public directory and optional aliases.
     */
    private function saveFeed(string $filename, string $content, array $aliases = []): array
    {
        $dir = public_path('feeds');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $target = $dir . '/' . $filename;
        file_put_contents($target, $content);
        @chmod($target, 0664);

        foreach ($aliases as $alias) {
            $aliasTarget = $dir . '/' . $alias;
            file_put_contents($aliasTarget, $content);
            @chmod($aliasTarget, 0664);
        }

        return [
            'filename' => $filename,
            'path' => $target,
            'url' => url('feeds/' . $filename),
            'size' => filesize($target),
            'size_formatted' => $this->formatFileSize(filesize($target)),
            'updated_at' => now(),
        ];
    }

    /**
     * Fetch active products with all needed relationships.
     *
     * @return Collection<int, Product>
     */
    public function getActiveProducts(): Collection
    {
        return Product::with([
            'category.parent',
            'variants.optionValues.option',
            'variants.inventoryLevels',
            'images.optionValues',
            'images.variants',
            'productAttributes',
        ])
        ->where('status', ProductStatus::ACTIVE)
        ->orderBy('id')
        ->get();
    }

    /**
     * Transform a Product into normalized feed items (one item per variant).
     */
    public function extractProductFeedItems(Product $product): array
    {
        $categoryInfo = $this->resolveCategories($product);
        $cleanDesc = $this->sanitizeDescription($product);
        $mainProductImage = $product->primary_image_url ?: '';
        $store = StoreSettings::current();
        $brand = !empty($product->brand) ? $product->brand : ($store->store_name ?: 'Dhansree');

        $activeVariants = $product->variants->whereNull('archived_at');

        // If product has no variants, treat the product as a single standalone item
        if ($activeVariants->isEmpty()) {
            return [
                $this->buildSingleFeedItem(
                    $product,
                    null,
                    $categoryInfo,
                    $cleanDesc,
                    $mainProductImage,
                    $brand
                ),
            ];
        }

        $items = [];
        foreach ($activeVariants as $variant) {
            $items[] = $this->buildSingleFeedItem(
                $product,
                $variant,
                $categoryInfo,
                $cleanDesc,
                $mainProductImage,
                $brand
            );
        }

        return $items;
    }

    /**
     * Build normalized data for a single product/variant item.
     */
    private function buildSingleFeedItem(
        Product $product,
        ?ProductVariant $variant,
        array $catInfo,
        string $cleanDesc,
        string $fallbackImage,
        string $brand
    ): array {
        $sku = $variant?->sku ?: ('DS-' . $product->id . ($variant ? '-' . $variant->id : ''));
        $color = $variant ? $this->resolveVariantColor($variant, $product) : '';
        $size = $variant ? $this->resolveVariantSize($variant) : '';

        // Title: Include color/size if available for distinct variant ads
        $titleSuffix = array_filter([$color, $size]);
        $title = !empty($titleSuffix)
            ? ($product->name . ' - ' . implode(' / ', $titleSuffix))
            : $product->name;
        $title = Str::limit(trim($title), 150, '');

        // Pricing
        $priceRupees = $variant ? $variant->price_rupees : ($product->primaryVariant?->price_rupees ?? 0);
        $mrpRupees = $variant ? $variant->mrp_rupees : ($product->primaryVariant?->mrp_rupees ?? null);

        if ($mrpRupees && $mrpRupees > $priceRupees) {
            $priceStr = number_format((float) $mrpRupees, 2, '.', '') . ' INR';
            $salePriceStr = number_format((float) $priceRupees, 2, '.', '') . ' INR';
        } else {
            $priceStr = number_format((float) $priceRupees, 2, '.', '') . ' INR';
            $salePriceStr = '';
        }

        // Stock & Availability
        $stock = $variant ? $variant->stock_on_hand : 10;
        $inStock = $stock > 0;
        $metaAvailability = $inStock ? 'in stock' : 'out of stock';
        $googleAvailability = $inStock ? 'in_stock' : 'out_of_stock';

        // Canonical Product Link (with sku tracking parameter)
        $link = url('/product/' . $product->slug . ($sku ? '?sku=' . urlencode($sku) : ''));

        // Image Resolution
        $imageLink = $variant ? $this->resolveVariantImageUrl($variant, $product) : $fallbackImage;
        if (empty($imageLink)) {
            $imageLink = $fallbackImage;
        }

        // Additional secondary images for Google (max 10)
        $additionalImages = $product->images
            ->map(fn($img) => $img->url)
            ->filter(fn($url) => !empty($url) && $url !== $imageLink)
            ->take(10)
            ->implode(',');

        // Material & Pattern from attributes or defaults
        $material = $this->resolveAttributeValue($product, ['fabric', 'material']) ?: $catInfo['material'];
        $pattern = $this->resolveAttributeValue($product, ['pattern', 'print', 'design']) ?: '';

        // Weight
        $weightGrams = $variant?->weight_grams ?: 350;
        $weightKg = round($weightGrams / 1000, 2);

        $barcode = $variant?->barcode ?? '';

        return [
            'id' => $sku,
            'title' => $title,
            'description' => $cleanDesc,
            'link' => $link,
            'image_link' => $imageLink,
            'additional_image_link' => $additionalImages,
            'meta_availability' => $metaAvailability,
            'google_availability' => $googleAvailability,
            'condition' => 'new',
            'brand' => $brand,
            'google_product_category' => $catInfo['google'],
            'fb_product_category' => $catInfo['fb'],
            'price' => $priceStr,
            'sale_price' => $salePriceStr,
            'sale_price_effective_date' => '',
            'item_group_id' => 'DS-PROD-' . $product->id,
            'adult' => 'no',
            'gender' => $catInfo['gender'],
            'age_group' => $catInfo['age_group'],
            'color' => $color,
            'size' => $size,
            'material' => $material,
            'pattern' => $pattern,
            'shipping' => 'IN::Standard:0.00 INR',
            'shipping_google' => 'IN:::0.00 INR',
            'shipping_weight' => $weightGrams . ' g',
            'shipping_weight_google' => $weightKg . ' kg',
            'offer_disclaimer' => '',
            'offer_disclaimer_url' => '',
            'video_url' => '',
            'video_tag' => '',
            'gtin' => $barcode,
            'mpn' => $sku,
            'identifier_exists' => !empty($barcode) ? 'yes' : 'no',
            'product_tags' => [
                $product->category?->name ?? 'Ethnic Wear',
                'Dhansree Trends',
            ],
            'style' => $catInfo['style'],
        ];
    }

    /**
     * Resolve category mapping for Google and Meta.
     */
    private function resolveCategories(Product $product): array
    {
        $catName = strtolower($product->category?->name ?? '');
        $catSlug = strtolower($product->category?->slug ?? '');
        $parentName = strtolower($product->category?->parent?->name ?? '');
        $all = $catName . ' ' . $catSlug . ' ' . $parentName . ' ' . strtolower($product->name);

        $isWomen = str_contains($all, 'women') || str_contains($all, 'kurti') || str_contains($all, 'anarkali')
            || str_contains($all, 'suit') || str_contains($all, 'co-ord') || str_contains($all, 'ethnic')
            || str_contains($all, 'dupatta') || str_contains($all, 'saree') || str_contains($all, 'dress');

        $isTech = str_contains($all, 'gadget') || str_contains($all, 'mobile') || str_contains($all, 'tech')
            || str_contains($all, 'cable') || str_contains($all, 'charger') || str_contains($all, 'trimmer')
            || str_contains($all, 'smart') || str_contains($all, 'phone') || str_contains($all, 'watch');

        $isHome = str_contains($all, 'home') || str_contains($all, 'kitchen') || str_contains($all, 'lifestyle')
            || str_contains($all, 'candle') || str_contains($all, 'dispenser') || str_contains($all, 'bottle');

        if ($isWomen) {
            return [
                'google' => 'Apparel & Accessories > Clothing > Traditional & Ceremonial Clothing',
                'fb' => 'Clothing & Accessories > Clothing',
                'gender' => 'female',
                'age_group' => 'adult',
                'material' => 'Pure Cotton',
                'style' => 'Ethnic Wear',
            ];
        }

        if ($isTech) {
            return [
                'google' => 'Electronics > Electronics Accessories',
                'fb' => 'Electronics',
                'gender' => 'unisex',
                'age_group' => 'adult',
                'material' => '',
                'style' => 'Smart Tech',
            ];
        }

        if ($isHome) {
            return [
                'google' => 'Home & Garden > Kitchen & Dining',
                'fb' => 'Home & Garden',
                'gender' => 'unisex',
                'age_group' => 'adult',
                'material' => '',
                'style' => 'Home Essentials',
            ];
        }

        return [
            'google' => 'Apparel & Accessories > Clothing',
            'fb' => 'Clothing & Accessories > Clothing',
            'gender' => 'unisex',
            'age_group' => 'adult',
            'material' => '',
            'style' => 'Lifestyle',
        ];
    }

    /**
     * Resolve variant image URL (pinned variant image, pinned color image, or primary image).
     */
    private function resolveVariantImageUrl(ProductVariant $variant, Product $product): string
    {
        // 1. Direct pinned variant image
        $variantImage = $product->images->first(function (ProductImage $img) use ($variant) {
            return $img->variants->contains('id', $variant->id);
        });

        if ($variantImage && $variantImage->url) {
            return $variantImage->url;
        }

        // 2. Image pinned to this variant's option values (e.g. Color)
        $optValIds = $variant->optionValues->pluck('id')->all();
        if (!empty($optValIds)) {
            $colorImage = $product->images->first(function (ProductImage $img) use ($optValIds) {
                return $img->optionValues->pluck('id')->intersect($optValIds)->isNotEmpty();
            });

            if ($colorImage && $colorImage->url) {
                return $colorImage->url;
            }
        }

        // 3. Fallback to product primary image
        return $product->primary_image_url ?: '';
    }

    /**
     * Resolve color from options, SKU, or product title.
     */
    private function resolveVariantColor(ProductVariant $variant, Product $product): string
    {
        // 1. Option values
        $colorOpt = $variant->optionValues->first(function ($ov) {
            return in_array(strtolower($ov->option?->name ?? ''), ['color', 'colour', 'shade']);
        });

        if ($colorOpt && !empty($colorOpt->value)) {
            return $colorOpt->value;
        }

        // 2. From SKU
        $sku = strtoupper($variant->sku ?? '');
        $colorCodes = [
            'BLK' => 'Black', 'WHT' => 'White', 'RED' => 'Red', 'BLU' => 'Blue',
            'GRN' => 'Green', 'YLW' => 'Yellow', 'PNK' => 'Pink', 'MRN' => 'Maroon',
            'PPL' => 'Purple', 'PCH' => 'Peach', 'ORG' => 'Orange', 'BEG' => 'Beige',
            'OFWHT' => 'Off White', 'GRE' => 'Grey', 'BRW' => 'Brown', 'PRGRN' => 'Parrot Green',
            'MGT' => 'Magenta',
        ];

        foreach ($colorCodes as $code => $name) {
            if (str_contains($sku, '-' . $code) || str_contains($sku, '_' . $code)) {
                return $name;
            }
        }

        // 3. From parentheses in product title e.g. "(Red)"
        if (preg_match('/\(([^)]+)\)/', $product->name, $matches)) {
            $inside = trim($matches[1]);
            foreach ($colorCodes as $name) {
                if (stripos($inside, $name) !== false) {
                    return $name;
                }
            }
        }

        return '';
    }

    /**
     * Resolve size from size_option attribute, option values, or SKU.
     */
    private function resolveVariantSize(ProductVariant $variant): string
    {
        if (!empty($variant->size_option)) {
            return $variant->size_option;
        }

        $sizeOpt = $variant->optionValues->first(function ($ov) {
            return in_array(strtolower($ov->option?->name ?? ''), ['size', 'sizes', 'fit']);
        });

        if ($sizeOpt && !empty($sizeOpt->value)) {
            return $sizeOpt->value;
        }

        $sku = strtoupper($variant->sku ?? '');
        if (preg_match('/-(XS|S|M|L|XL|2XL|3XL|4XL|5XL|FREE)$/i', $sku, $matches)) {
            return strtoupper($matches[1]);
        }

        return '';
    }

    /**
     * Resolve product attribute value by candidate keys.
     */
    private function resolveAttributeValue(Product $product, array $candidateKeys): ?string
    {
        $attr = $product->productAttributes->first(function ($a) use ($candidateKeys) {
            return in_array(strtolower($a->name ?? ''), $candidateKeys, true);
        });

        return $attr?->value;
    }

    /**
     * Clean plain text description.
     */
    private function sanitizeDescription(Product $product): string
    {
        $raw = $product->description ?: ($product->short_description ?: ($product->seo_description ?: $product->name));
        $plain = strip_tags($raw);
        $decoded = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/\s+/', ' ', $decoded);

        return Str::limit(trim((string) $normalized), 4900, '...');
    }

    /**
     * Format byte sizes into readable units.
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
