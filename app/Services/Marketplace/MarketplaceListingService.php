<?php

declare(strict_types=1);

namespace App\Services\Marketplace;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\MarketplaceTemplate;
use App\Models\MarketplaceTemplateColumn;
use App\Models\Product;
use App\Models\ProductMarketplaceValue;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use App\Models\VariantMarketplaceValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketplaceListingService
{
    /**
     * Ensure default built-in templates exist.
     */
    public function ensureStandardTemplates(): void
    {
        if (MarketplaceTemplate::count() > 0) {
            return;
        }

        DB::transaction(function () {
            // 1. Meesho Template
            $meesho = MarketplaceTemplate::create([
                'platform' => 'MEESHO',
                'name' => 'Meesho Kurtis & Ethnic Sets',
                'file_name' => 'meesho_kurtis_template.xlsx',
                'sheet_name' => 'Listing Data',
                'version' => 1,
            ]);

            $meeshoColumns = [
                ['position' => 0, 'ref' => 'A', 'label' => 'Product ID / SKU', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SKU'],
                ['position' => 1, 'ref' => 'B', 'label' => 'Product Name / Title', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'TITLE'],
                ['position' => 2, 'ref' => 'C', 'label' => 'Style Code / Parent SKU', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'STYLE_CODE'],
                ['position' => 3, 'ref' => 'D', 'label' => 'Color', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'COLOR'],
                ['position' => 4, 'ref' => 'E', 'label' => 'Size', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SIZE'],
                ['position' => 5, 'ref' => 'F', 'label' => 'Selling Price (₹)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'PRICE_RUPEES'],
                ['position' => 6, 'ref' => 'G', 'label' => 'MRP (₹)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'MRP_RUPEES'],
                ['position' => 7, 'ref' => 'H', 'label' => 'Inventory / Stock Quantity', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'STOCK'],
                ['position' => 8, 'ref' => 'I', 'label' => 'GST (%)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'PRODUCT', 'source' => 'GST_PERCENT'],
                ['position' => 9, 'ref' => 'J', 'label' => 'HSN Code', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'HSN'],
                ['position' => 10, 'ref' => 'K', 'label' => 'Weight (Grams)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'WEIGHT_GRAMS'],
                ['position' => 11, 'ref' => 'L', 'label' => 'Front Image URL', 'need' => 'REQUIRED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_1'],
                ['position' => 12, 'ref' => 'M', 'label' => 'Back Image URL', 'need' => 'RECOMMENDED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_2'],
                ['position' => 13, 'ref' => 'N', 'label' => 'Side / Angle Image URL', 'need' => 'OPTIONAL', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_3'],
                ['position' => 14, 'ref' => 'O', 'label' => 'Detail Image URL', 'need' => 'OPTIONAL', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_4'],
                ['position' => 15, 'ref' => 'P', 'label' => 'Fabric', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'FABRIC'],
                ['position' => 16, 'ref' => 'Q', 'label' => 'Wash Care', 'need' => 'RECOMMENDED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'WASH_CARE'],
                ['position' => 17, 'ref' => 'R', 'label' => 'Country of Origin', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'COUNTRY_OF_ORIGIN', 'fixed_value' => 'India'],
                ['position' => 18, 'ref' => 'S', 'label' => 'Package Contents', 'need' => 'RECOMMENDED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'PACKAGE_CONTENTS'],
                ['position' => 19, 'ref' => 'T', 'label' => 'Brand', 'need' => 'RECOMMENDED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'BRAND', 'fixed_value' => 'Dhansree'],
            ];

            foreach ($meeshoColumns as $col) {
                MarketplaceTemplateColumn::create(array_merge($col, ['template_id' => $meesho->id]));
            }

            // 2. Flipkart Template
            $flipkart = MarketplaceTemplate::create([
                'platform' => 'FLIPKART',
                'name' => 'Flipkart Kurtas & Ethnic Sets',
                'file_name' => 'flipkart_apparel_template.xlsx',
                'sheet_name' => 'Price_and_Stock',
                'version' => 1,
            ]);

            $flipkartColumns = [
                ['position' => 0, 'ref' => 'A', 'label' => 'Seller SKU ID', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SKU'],
                ['position' => 1, 'ref' => 'B', 'label' => 'Brand', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'BRAND', 'fixed_value' => 'Dhansree'],
                ['position' => 2, 'ref' => 'C', 'label' => 'Title', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'TITLE'],
                ['position' => 3, 'ref' => 'D', 'label' => 'Style Code', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'STYLE_CODE'],
                ['position' => 4, 'ref' => 'E', 'label' => 'Color', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'COLOR'],
                ['position' => 5, 'ref' => 'F', 'label' => 'Size', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SIZE'],
                ['position' => 6, 'ref' => 'G', 'label' => 'Listing Price (₹)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'PRICE_RUPEES'],
                ['position' => 7, 'ref' => 'H', 'label' => 'MRP (₹)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'MRP_RUPEES'],
                ['position' => 8, 'ref' => 'I', 'label' => 'Stock Quantity', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'STOCK'],
                ['position' => 9, 'ref' => 'J', 'label' => 'HSN Code', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'HSN'],
                ['position' => 10, 'ref' => 'K', 'label' => 'Weight (KG)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'WEIGHT_KG'],
                ['position' => 11, 'ref' => 'L', 'label' => 'Main Image URL', 'need' => 'REQUIRED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_1'],
                ['position' => 12, 'ref' => 'M', 'label' => 'Other Image URL 1', 'need' => 'RECOMMENDED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_2'],
                ['position' => 13, 'ref' => 'N', 'label' => 'Other Image URL 2', 'need' => 'OPTIONAL', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_3'],
                ['position' => 14, 'ref' => 'O', 'label' => 'Fabric', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'FABRIC'],
                ['position' => 15, 'ref' => 'P', 'label' => 'Occasion', 'need' => 'RECOMMENDED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'OCCASION', 'fixed_value' => 'Festive & Party'],
                ['position' => 16, 'ref' => 'Q', 'label' => 'Ideal For', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'IDEAL_FOR', 'fixed_value' => 'Women'],
                ['position' => 17, 'ref' => 'R', 'label' => 'Pack Of', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'PACK_OF', 'fixed_value' => '1'],
            ];

            foreach ($flipkartColumns as $col) {
                MarketplaceTemplateColumn::create(array_merge($col, ['template_id' => $flipkart->id]));
            }

            // 3. Amazon Template
            $amazon = MarketplaceTemplate::create([
                'platform' => 'AMAZON',
                'name' => 'Amazon India Apparel & Kurtas',
                'file_name' => 'amazon_in_clothing_template.xlsx',
                'sheet_name' => 'Template',
                'version' => 1,
            ]);

            $amazonColumns = [
                ['position' => 0, 'ref' => 'A', 'label' => 'SKU', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SKU'],
                ['position' => 1, 'ref' => 'B', 'label' => 'Product Name / Title', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'TITLE'],
                ['position' => 2, 'ref' => 'C', 'label' => 'Parent SKU (Style Code)', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'STYLE_CODE'],
                ['position' => 3, 'ref' => 'D', 'label' => 'Variation Theme', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'VARIATION_THEME', 'fixed_value' => 'SizeColor'],
                ['position' => 4, 'ref' => 'E', 'label' => 'Color', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'COLOR'],
                ['position' => 5, 'ref' => 'F', 'label' => 'Size', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SIZE'],
                ['position' => 6, 'ref' => 'G', 'label' => 'Standard Price (₹)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'PRICE_RUPEES'],
                ['position' => 7, 'ref' => 'H', 'label' => 'List Price / MRP (₹)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'MRP_RUPEES'],
                ['position' => 8, 'ref' => 'I', 'label' => 'Quantity / Stock', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'STOCK'],
                ['position' => 9, 'ref' => 'J', 'label' => 'Main Image URL', 'need' => 'REQUIRED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_1'],
                ['position' => 10, 'ref' => 'K', 'label' => 'Other Image URL 1', 'need' => 'RECOMMENDED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_2'],
                ['position' => 11, 'ref' => 'L', 'label' => 'Other Image URL 2', 'need' => 'OPTIONAL', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_3'],
                ['position' => 12, 'ref' => 'M', 'label' => 'Material Type', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'FABRIC'],
                ['position' => 13, 'ref' => 'N', 'label' => 'Country / Region of Origin', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'COUNTRY_OF_ORIGIN', 'fixed_value' => 'India'],
                ['position' => 14, 'ref' => 'O', 'label' => 'HSN Code', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'HSN'],
                ['position' => 15, 'ref' => 'P', 'label' => 'Department', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'DEPARTMENT', 'fixed_value' => 'Women'],
                ['position' => 16, 'ref' => 'Q', 'label' => 'Item Type Keyword', 'need' => 'RECOMMENDED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'KEYWORD', 'fixed_value' => 'kurtas'],
            ];

            foreach ($amazonColumns as $col) {
                MarketplaceTemplateColumn::create(array_merge($col, ['template_id' => $amazon->id]));
            }

            // 4. Myntra Template
            $myntra = MarketplaceTemplate::create([
                'platform' => 'MYNTRA',
                'name' => 'Myntra Ethnic Wear Catalog',
                'file_name' => 'myntra_catalog_template.xlsx',
                'sheet_name' => 'Catalog',
                'version' => 1,
            ]);

            $myntraColumns = [
                ['position' => 0, 'ref' => 'A', 'label' => 'Seller SKU', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SKU'],
                ['position' => 1, 'ref' => 'B', 'label' => 'Brand', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'BRAND', 'fixed_value' => 'Dhansree'],
                ['position' => 2, 'ref' => 'C', 'label' => 'Product Name', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'TITLE'],
                ['position' => 3, 'ref' => 'D', 'label' => 'Style Code', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'STYLE_CODE'],
                ['position' => 4, 'ref' => 'E', 'label' => 'Primary Color', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'COLOR'],
                ['position' => 5, 'ref' => 'F', 'label' => 'Size', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SIZE'],
                ['position' => 6, 'ref' => 'G', 'label' => 'Selling Price (₹)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'PRICE_RUPEES'],
                ['position' => 7, 'ref' => 'H', 'label' => 'MRP (₹)', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'MRP_RUPEES'],
                ['position' => 8, 'ref' => 'I', 'label' => 'Inventory', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'STOCK'],
                ['position' => 9, 'ref' => 'J', 'label' => 'Front Image', 'need' => 'REQUIRED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_1'],
                ['position' => 10, 'ref' => 'K', 'label' => 'Back Image', 'need' => 'RECOMMENDED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_2'],
                ['position' => 11, 'ref' => 'L', 'label' => 'Fabric', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'FABRIC'],
                ['position' => 12, 'ref' => 'M', 'label' => 'Country of Origin', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'COUNTRY_OF_ORIGIN', 'fixed_value' => 'India'],
            ];

            foreach ($myntraColumns as $col) {
                MarketplaceTemplateColumn::create(array_merge($col, ['template_id' => $myntra->id]));
            }

            // 5. Ajio Template
            $ajio = MarketplaceTemplate::create([
                'platform' => 'AJIO',
                'name' => 'Ajio B2C Catalog Template',
                'file_name' => 'ajio_b2c_template.xlsx',
                'sheet_name' => 'Sheet1',
                'version' => 1,
            ]);

            $ajioColumns = [
                ['position' => 0, 'ref' => 'A', 'label' => 'Merchant SKU', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SKU'],
                ['position' => 1, 'ref' => 'B', 'label' => 'Brand', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'BRAND', 'fixed_value' => 'Dhansree'],
                ['position' => 2, 'ref' => 'C', 'label' => 'Product Name', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'TITLE'],
                ['position' => 3, 'ref' => 'D', 'label' => 'Style Code', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'STYLE_CODE'],
                ['position' => 4, 'ref' => 'E', 'label' => 'Color', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'COLOR'],
                ['position' => 5, 'ref' => 'F', 'label' => 'Size', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'VARIANT', 'source' => 'SIZE'],
                ['position' => 6, 'ref' => 'G', 'label' => 'Offer Price', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'PRICE_RUPEES'],
                ['position' => 7, 'ref' => 'H', 'label' => 'MRP', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'MRP_RUPEES'],
                ['position' => 8, 'ref' => 'I', 'label' => 'Inventory Count', 'need' => 'REQUIRED', 'kind' => 'NUMBER', 'scope' => 'VARIANT', 'source' => 'STOCK'],
                ['position' => 9, 'ref' => 'J', 'label' => 'Image 1 URL', 'need' => 'REQUIRED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_1'],
                ['position' => 10, 'ref' => 'K', 'label' => 'Image 2 URL', 'need' => 'RECOMMENDED', 'kind' => 'URL', 'scope' => 'PRODUCT', 'source' => 'IMAGE_2'],
                ['position' => 11, 'ref' => 'L', 'label' => 'HSN', 'need' => 'REQUIRED', 'kind' => 'TEXT', 'scope' => 'PRODUCT', 'source' => 'HSN'],
            ];

            foreach ($ajioColumns as $col) {
                MarketplaceTemplateColumn::create(array_merge($col, ['template_id' => $ajio->id]));
            }
        });
    }

    /**
     * Get all active marketplace templates.
     */
    public function getTemplates(): Collection
    {
        $this->ensureStandardTemplates();
        return MarketplaceTemplate::withCount('marketplaceTemplateColumns')
            ->orderBy('id')
            ->get();
    }

    /**
     * Resolve listing rows for a template and filter.
     */
    public function resolveListing(int $templateId, array $filters = []): array
    {
        $this->ensureStandardTemplates();

        $template = MarketplaceTemplate::with('columns')->findOrFail($templateId);
        $columns = $template->columns;

        $storeSettings = StoreSettings::current();
        $siteUrl = rtrim((string) config('app.url', 'https://dhansree.com'), '/');

        // Query active products matching filters
        $query = Product::with([
            'category',
            'productAttributes',
            'variants' => fn($q) => $q->whereNull('archived_at')->orderBy('position')->with(['optionValues', 'inventoryLevels']),
            'options' => fn($q) => $q->orderBy('position')->with(['values']),
            'images' => fn($q) => $q->orderBy('position')->with(['productImageVariants', 'productImageOptionValues']),
        ])->where('status', '!=', ProductStatus::ARCHIVED);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('slug', 'like', "%{$term}%")
                  ->orWhereHas('variants', fn($vq) => $vq->where('sku', 'like', "%{$term}%"));
            });
        }

        $products = $query->orderBy('name')->get();

        // Eager load custom marketplace values
        $productIds = $products->pluck('id')->all();
        $variantIds = $products->flatMap->variants->pluck('id')->all();
        $colIds = $columns->pluck('id')->all();

        $customProductValues = ProductMarketplaceValue::whereIn('product_id', $productIds)
            ->whereIn('column_id', $colIds)
            ->get()
            ->groupBy('product_id');

        $customVariantValues = VariantMarketplaceValue::whereIn('variant_id', $variantIds)
            ->whereIn('column_id', $colIds)
            ->get()
            ->groupBy('variant_id');

        $rows = [];
        $totalVariantsCount = 0;
        $readyVariantsCount = 0;

        foreach ($products as $product) {
            $prodCustoms = $customProductValues->get($product->id)?->keyBy('column_id') ?? collect();

            // Extract attributes like fabric, care, country
            $specs = $product->productAttributes->keyBy(fn($a) => strtolower(trim((string) $a->label)));
            $fabric = $specs->get('fabric')?->value ?? 'Pure Cotton';
            $care = $specs->get('wash care')?->value ?? 'Hand Wash Separately';
            $origin = $specs->get('country of origin')?->value ?? 'India';
            $packageContents = $specs->get('package contents')?->value ?? ($product->name . ' Set');

            $colorOption = $product->options->first(fn($o) => in_array(strtolower($o->name), ['color', 'colour']));
            $sizeOption = $product->options->first(fn($o) => in_array(strtolower($o->name), ['size']));

            $styleCode = $product->variants->first()?->sku
                ? preg_replace('/-[A-Z0-9]+$/', '', $product->variants->first()->sku)
                : strtoupper(Str::slug($product->name, ''));

            foreach ($product->variants as $variant) {
                $totalVariantsCount++;
                $varCustoms = $customVariantValues->get($variant->id)?->keyBy('column_id') ?? collect();

                // Color & size
                $color = $colorOption ? ($variant->optionValues->firstWhere('option_id', $colorOption->id)?->value ?? '') : '';
                $size = $sizeOption ? ($variant->optionValues->firstWhere('option_id', $sizeOption->id)?->value ?? '') : $variant->display_option_label;
                if (!$color && $product->variants->count() === 1) {
                    $color = 'Standard';
                }

                // Images: prioritize images pinned to this variant or color
                $matchedImages = $product->images->filter(function ($img) use ($variant, $colorOption) {
                    if ($img->productImageVariants->contains('variant_id', $variant->id)) {
                        return true;
                    }
                    if ($colorOption) {
                        $cVal = $variant->optionValues->firstWhere('option_id', $colorOption->id);
                        if ($cVal && $img->productImageOptionValues->contains('option_value_id', $cVal->id)) {
                            return true;
                        }
                    }
                    return false;
                })->values();

                if ($matchedImages->isEmpty()) {
                    $matchedImages = $product->images;
                }

                $cells = [];
                $isReady = true;
                $missingFields = [];

                foreach ($columns as $column) {
                    // Check manual override
                    $val = '';
                    if ($column->scope === 'VARIANT') {
                        $val = $varCustoms->get($column->id)?->value ?? '';
                    } else {
                        $val = $prodCustoms->get($column->id)?->value ?? '';
                    }

                    if ($val === '') {
                        $val = match ($column->source) {
                            'SKU' => $variant->sku,
                            'TITLE' => $product->name,
                            'STYLE_CODE' => $styleCode,
                            'BRAND' => $product->brand ?: ($column->fixed_value ?: 'Dhansree'),
                            'COLOR' => $color,
                            'SIZE' => $size,
                            'PRICE_RUPEES' => (string) round($variant->price_rupees, 2),
                            'MRP_RUPEES' => (string) round($variant->mrp_rupees ?: $variant->price_rupees, 2),
                            'STOCK' => (string) max(0, $variant->inventoryLevels->sum('on_hand') - $variant->inventoryLevels->sum('reserved')),
                            'GST_PERCENT' => (string) (($product->gst_rate_bps ?? $storeSettings->default_gst_rate_bps ?? 500) / 100),
                            'HSN' => $product->hsn_code ?? '6204',
                            'WEIGHT_GRAMS' => (string) ($variant->weight_grams ?? 350),
                            'WEIGHT_KG' => number_format(($variant->weight_grams ?? 350) / 1000, 2),
                            'IMAGE_1' => $this->absoluteImageUrl($matchedImages->get(0)?->url ?? $product->primary_image_url, $siteUrl),
                            'IMAGE_2' => $this->absoluteImageUrl($matchedImages->get(1)?->url ?? $product->images->get(1)?->url, $siteUrl),
                            'IMAGE_3' => $this->absoluteImageUrl($matchedImages->get(2)?->url ?? $product->images->get(2)?->url, $siteUrl),
                            'IMAGE_4' => $this->absoluteImageUrl($matchedImages->get(3)?->url ?? $product->images->get(3)?->url, $siteUrl),
                            'FABRIC' => $fabric,
                            'WASH_CARE' => $care,
                            'COUNTRY_OF_ORIGIN' => $origin,
                            'PACKAGE_CONTENTS' => $packageContents,
                            'VARIATION_THEME' => 'SizeColor',
                            'OCCASION' => $column->fixed_value ?: 'Festive & Daily Casual',
                            'IDEAL_FOR' => $column->fixed_value ?: 'Women',
                            'PACK_OF' => $column->fixed_value ?: '1',
                            'DEPARTMENT' => $column->fixed_value ?: 'Women',
                            'KEYWORD' => $column->fixed_value ?: 'kurtas',
                            default => $column->fixed_value ?? '',
                        };
                    }

                    if ($column->need === 'REQUIRED' && trim((string) $val) === '') {
                        $isReady = false;
                        $missingFields[] = $column->label;
                    }

                    $cells[] = $val;
                }

                if ($isReady) {
                    $readyVariantsCount++;
                }

                $rows[] = [
                    'variant_id' => $variant->id,
                    'product_id' => $product->id,
                    'sku' => $variant->sku,
                    'product_name' => $product->name,
                    'color' => $color,
                    'size' => $size,
                    'cells' => $cells,
                    'is_ready' => $isReady,
                    'missing' => $missingFields,
                ];
            }
        }

        return [
            'template' => $template,
            'columns' => $columns,
            'rows' => $rows,
            'total_rows' => count($rows),
            'ready_count' => $readyVariantsCount,
            'total_count' => $totalVariantsCount,
        ];
    }

    /**
     * Generate CSV content for download.
     */
    public function generateCsv(int $templateId, array $filters = []): string
    {
        $listing = $this->resolveListing($templateId, $filters);
        $columns = $listing['columns'];
        $rows = $listing['rows'];

        $fp = fopen('php://memory', 'r+');
        // UTF-8 BOM so Excel opens Hindi/English characters cleanly
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Header row
        $headers = $columns->pluck('label')->all();
        fputcsv($fp, $headers);

        // Data rows
        foreach ($rows as $row) {
            fputcsv($fp, $row['cells']);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return (string) $csv;
    }

    /**
     * Generate Tab-Separated Text for one-click copy to Excel/Sheets.
     */
    public function generateTsv(int $templateId, array $filters = []): string
    {
        $listing = $this->resolveListing($templateId, $filters);
        $columns = $listing['columns'];
        $rows = $listing['rows'];

        $lines = [];
        $lines[] = implode("\t", $columns->pluck('label')->map(fn($l) => str_replace(["\t", "\n", "\r"], ' ', $l))->all());

        foreach ($rows as $row) {
            $lines[] = implode("\t", array_map(fn($c) => str_replace(["\t", "\n", "\r"], ' ', (string) $c), $row['cells']));
        }

        return implode("\n", $lines);
    }

    /**
     * Resolve absolute image URL for marketplaces to fetch.
     */
    private function absoluteImageUrl(?string $url, string $siteUrl): string
    {
        if (empty($url)) {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            // Replace localhost with live site domain for external sellers
            if (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
                $path = parse_url($url, PHP_URL_PATH);
                return $siteUrl . ($path ?: '');
            }
            return $url;
        }

        return $siteUrl . '/' . ltrim($url, '/');
    }
}
