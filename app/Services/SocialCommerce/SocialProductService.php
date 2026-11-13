<?php

declare(strict_types=1);

namespace App\Services\SocialCommerce;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SocialMediaAsset;
use App\Models\SocialProduct;
use App\Models\SocialProductVariant;
use App\Models\SocialSupplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SocialProductService
{
    /**
     * Categories tailored for Dhansree Women's Fashion.
     */
    public const WOMEN_FASHION_CATEGORIES = [
        "Women's Suits",
        'Two-Piece Suits',
        'Unstitched Suits',
        'Dupattas',
        'Anarkali Suits',
        'Co-ord Sets',
        'Kaftans',
        'Kurtis',
    ];

    /**
     * Generate next product code, e.g. DS-SP-0001.
     */
    public function generateProductCode(): string
    {
        $lastProduct = SocialProduct::withTrashed()->orderBy('id', 'desc')->first();
        $nextId = $lastProduct ? $lastProduct->id + 1 : 1;
        return sprintf('DS-SP-%04d', $nextId);
    }

    /**
     * List products with flexible filtering.
     */
    public function listProducts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SocialProduct::with(['supplier', 'primaryMedia', 'mediaAssets', 'variants'])
            ->latest('id');

        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('product_code', 'like', "%{$term}%")
                    ->orWhere('supplier_product_code', 'like', "%{$term}%")
                    ->orWhere('fabric', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['category_name'])) {
            $query->where('category_name', $filters['category_name']);
        }

        if (!empty($filters['availability_status'])) {
            $query->where('availability_status', $filters['availability_status']);
        }

        if (isset($filters['is_promoted_to_web']) && $filters['is_promoted_to_web'] !== '') {
            $query->where('is_promoted_to_web', (bool) $filters['is_promoted_to_web']);
        }

        if (!empty($filters['price_min'])) {
            $query->where('suggested_selling_price_paise', '>=', (int) ($filters['price_min'] * 100));
        }

        if (!empty($filters['price_max'])) {
            $query->where('suggested_selling_price_paise', '<=', (int) ($filters['price_max'] * 100));
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new supplier social product.
     */
    public function createProduct(array $data, ?int $adminUserId = null): SocialProduct
    {
        return DB::transaction(function () use ($data, $adminUserId) {
            $productCode = !empty($data['product_code']) ? trim($data['product_code']) : $this->generateProductCode();

            $colors = $this->parseArrayField($data['available_colors'] ?? []);
            $sizes = $this->parseArrayField($data['available_sizes'] ?? []);

            $product = SocialProduct::create([
                'product_code' => $productCode,
                'name' => trim($data['name']),
                'category_name' => trim($data['category_name'] ?? "Women's Suits"),
                'supplier_id' => !empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                'supplier_product_code' => !empty($data['supplier_product_code']) ? trim($data['supplier_product_code']) : null,
                'fabric' => !empty($data['fabric']) ? trim($data['fabric']) : null,
                'available_colors' => $colors,
                'available_sizes' => $sizes,
                'purchase_price_paise' => isset($data['purchase_price']) && $data['purchase_price'] !== '' ? (int) round((float) $data['purchase_price'] * 100) : null,
                'suggested_selling_price_paise' => isset($data['suggested_selling_price']) && $data['suggested_selling_price'] !== '' ? (int) round((float) $data['suggested_selling_price'] * 100) : null,
                'special_offer_price_paise' => isset($data['special_offer_price']) && $data['special_offer_price'] !== '' ? (int) round((float) $data['special_offer_price'] * 100) : null,
                'description' => !empty($data['description']) ? trim($data['description']) : null,
                'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
                'availability_status' => $data['availability_status'] ?? 'NEW',
                'last_availability_confirmed_at' => now(),
                'primary_media_asset_id' => !empty($data['primary_media_asset_id']) ? (int) $data['primary_media_asset_id'] : null,
                'is_promoted_to_web' => false,
                'created_by_admin_user_id' => $adminUserId,
            ]);

            // Attach media assets if provided
            if (!empty($data['media_asset_ids']) && is_array($data['media_asset_ids'])) {
                $this->syncProductMedia($product, $data['media_asset_ids'], $product->primary_media_asset_id);
            }

            // Create initial variants if specified
            if (!empty($data['variants']) && is_array($data['variants'])) {
                $this->syncVariants($product, $data['variants']);
            }

            return $product->fresh(['supplier', 'primaryMedia', 'mediaAssets', 'variants']);
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(SocialProduct $product, array $data): SocialProduct
    {
        return DB::transaction(function () use ($product, $data) {
            $colors = $this->parseArrayField($data['available_colors'] ?? $product->available_colors);
            $sizes = $this->parseArrayField($data['available_sizes'] ?? $product->available_sizes);

            $updateData = [
                'name' => trim($data['name'] ?? $product->name),
                'category_name' => trim($data['category_name'] ?? $product->category_name),
                'supplier_id' => array_key_exists('supplier_id', $data) ? ($data['supplier_id'] ? (int) $data['supplier_id'] : null) : $product->supplier_id,
                'supplier_product_code' => array_key_exists('supplier_product_code', $data) ? trim((string) $data['supplier_product_code']) : $product->supplier_product_code,
                'fabric' => array_key_exists('fabric', $data) ? trim((string) $data['fabric']) : $product->fabric,
                'available_colors' => $colors,
                'available_sizes' => $sizes,
                'purchase_price_paise' => isset($data['purchase_price']) && $data['purchase_price'] !== '' ? (int) round((float) $data['purchase_price'] * 100) : $product->purchase_price_paise,
                'suggested_selling_price_paise' => isset($data['suggested_selling_price']) && $data['suggested_selling_price'] !== '' ? (int) round((float) $data['suggested_selling_price'] * 100) : $product->suggested_selling_price_paise,
                'special_offer_price_paise' => isset($data['special_offer_price']) && $data['special_offer_price'] !== '' ? (int) round((float) $data['special_offer_price'] * 100) : $product->special_offer_price_paise,
                'description' => array_key_exists('description', $data) ? trim((string) $data['description']) : $product->description,
                'notes' => array_key_exists('notes', $data) ? trim((string) $data['notes']) : $product->notes,
            ];

            if (!empty($data['availability_status'])) {
                $updateData['availability_status'] = $data['availability_status'];
                $updateData['last_availability_confirmed_at'] = now();
            }

            if (!empty($data['primary_media_asset_id'])) {
                $updateData['primary_media_asset_id'] = (int) $data['primary_media_asset_id'];
            }

            $product->update($updateData);

            if (isset($data['media_asset_ids']) && is_array($data['media_asset_ids'])) {
                $this->syncProductMedia($product, $data['media_asset_ids'], $product->primary_media_asset_id);
            }

            if (isset($data['variants']) && is_array($data['variants'])) {
                $this->syncVariants($product, $data['variants']);
            }

            return $product->fresh(['supplier', 'primaryMedia', 'mediaAssets', 'variants']);
        });
    }

    /**
     * Update product availability status.
     */
    public function updateAvailability(SocialProduct $product, string $status, ?string $notes = null): SocialProduct
    {
        $validStatuses = ['NEW', 'AVAILABLE', 'TEMPORARILY_UNAVAILABLE', 'OUT_OF_STOCK', 'ARCHIVED'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException("Invalid availability status: {$status}");
        }

        $data = [
            'availability_status' => $status,
            'last_availability_confirmed_at' => now(),
        ];

        if ($notes !== null) {
            $data['notes'] = ($product->notes ? $product->notes . "\n" : '') . '[' . now()->toDateTimeString() . '] ' . $notes;
        }

        $product->update($data);
        return $product;
    }

    /**
     * Synchronize media assets attached to a product.
     */
    public function syncProductMedia(SocialProduct $product, array $assetIds, ?int $primaryAssetId = null): void
    {
        $syncData = [];
        $position = 0;

        foreach ($assetIds as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $syncData[$id] = [
                'position' => $position,
                'is_primary' => ($primaryAssetId !== null && $id === $primaryAssetId) || ($primaryAssetId === null && $position === 0),
            ];
            $position++;
        }

        $product->mediaAssets()->sync($syncData);

        // Update primary_media_asset_id on product
        if (!empty($syncData)) {
            $firstId = array_key_first($syncData);
            $newPrimary = $primaryAssetId && isset($syncData[$primaryAssetId]) ? $primaryAssetId : $firstId;
            $product->update(['primary_media_asset_id' => $newPrimary]);
        }
    }

    /**
     * Synchronize product variants (designs / colors / sizes).
     */
    public function syncVariants(SocialProduct $product, array $variants): void
    {
        // Delete existing variants and re-insert
        $product->variants()->delete();

        foreach ($variants as $idx => $v) {
            if (empty($v['color']) && empty($v['design_name']) && empty($v['size'])) {
                continue;
            }

            $purchasePaise = isset($v['purchase_price']) && $v['purchase_price'] !== '' ? (int) round((float) $v['purchase_price'] * 100) : $product->purchase_price_paise;
            $sellingPaise = isset($v['selling_price']) && $v['selling_price'] !== '' ? (int) round((float) $v['selling_price'] * 100) : $product->suggested_selling_price_paise;

            SocialProductVariant::create([
                'social_product_id' => $product->id,
                'variant_code' => $v['variant_code'] ?? ($product->product_code . '-V' . ($idx + 1)),
                'color' => !empty($v['color']) ? trim($v['color']) : null,
                'size' => !empty($v['size']) ? trim($v['size']) : null,
                'design_name' => !empty($v['design_name']) ? trim($v['design_name']) : null,
                'purchase_price_paise' => $purchasePaise,
                'selling_price_paise' => $sellingPaise,
                'stock_status' => $v['stock_status'] ?? 'AVAILABLE',
                'media_asset_id' => !empty($v['media_asset_id']) ? (int) $v['media_asset_id'] : null,
            ]);
        }
    }

    /**
     * Explicitly promote a SocialProduct to the public ecommerce store.
     * Only happens when the admin explicitly clicks "Publish to Website".
     */
    public function promoteToWeb(SocialProduct $socialProduct, array $overrides = []): Product
    {
        return DB::transaction(function () use ($socialProduct, $overrides) {
            // Check if already promoted
            if ($socialProduct->is_promoted_to_web && $socialProduct->web_product_id) {
                $existing = Product::find($socialProduct->web_product_id);
                if ($existing) {
                    return $existing;
                }
            }

            // Match or create Category
            $categoryName = $overrides['category_name'] ?? $socialProduct->category_name;
            $category = Category::where('name', $categoryName)->first();

            $slugBase = Str::slug($socialProduct->name);
            $slug = $slugBase;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . $counter;
                $counter++;
            }

            $brand = $socialProduct->supplier ? $socialProduct->supplier->name : 'Dhansree';

            // Build rich multi-line description including fabric, colors, sizes, and styling advice
            $descLines = [];
            if (!empty($socialProduct->description)) {
                $descLines[] = trim($socialProduct->description);
                $descLines[] = "";
            }
            $descLines[] = "• Product: {$socialProduct->name}";
            $descLines[] = "• Collection: {$socialProduct->category_name}";
            if (!empty($socialProduct->fabric)) {
                $descLines[] = "• Fabric: {$socialProduct->fabric}";
            }
            if (!empty($socialProduct->available_colors) && count($socialProduct->available_colors) > 0) {
                $descLines[] = "• Available Colors: " . implode(', ', $socialProduct->available_colors);
            }
            if (!empty($socialProduct->available_sizes) && count($socialProduct->available_sizes) > 0) {
                $descLines[] = "• Available Sizes: " . implode(', ', $socialProduct->available_sizes);
            }
            $descLines[] = "• Dispatch: Ready to dispatch within 24–48 hours";
            $descLines[] = "• Delivery: Fast Pan-India express shipping with tracking";

            $fullDescription = implode("\n", $descLines);
            $shortDescription = Str::limit(
                $socialProduct->description 
                    ? $socialProduct->description 
                    : "Exclusive {$socialProduct->name} from Dhansree. Handcrafted design in premium {$socialProduct->fabric}.",
                250
            );

            $webProduct = Product::create([
                'name' => $socialProduct->name,
                'slug' => $slug,
                'status' => \App\Enums\ProductStatus::ACTIVE,
                'category_id' => $category ? $category->id : null,
                'brand' => $brand,
                'short_description' => $shortDescription,
                'description' => $fullDescription,
                'published_at' => now(),
            ]);

            // Create main Product Variant with pricing
            $pricePaise = $socialProduct->special_offer_price_paise ?? $socialProduct->suggested_selling_price_paise ?? 99900;
            $mrpPaise = $socialProduct->suggested_selling_price_paise ?? (int) ($pricePaise * 1.3);
            $costPaise = $socialProduct->purchase_price_paise ?? (int) ($pricePaise * 0.6);

            ProductVariant::create([
                'product_id' => $webProduct->id,
                'sku' => $socialProduct->product_code,
                'price_paise' => $pricePaise,
                'mrp_paise' => $mrpPaise,
                'cost_paise' => $costPaise,
                'option_signature' => '',
                'position' => 0,
            ]);

            // Add Product Attributes for "Fabric, Specifications & Care" tab
            $attrPos = 0;
            if (!empty($socialProduct->fabric)) {
                ProductAttribute::create([
                    'product_id' => $webProduct->id,
                    'label' => 'Fabric',
                    'value' => $socialProduct->fabric,
                    'position' => $attrPos++,
                ]);
            }
            if (!empty($socialProduct->category_name)) {
                ProductAttribute::create([
                    'product_id' => $webProduct->id,
                    'label' => 'Collection',
                    'value' => $socialProduct->category_name,
                    'position' => $attrPos++,
                ]);
            }
            if (!empty($socialProduct->available_colors) && count($socialProduct->available_colors) > 0) {
                ProductAttribute::create([
                    'product_id' => $webProduct->id,
                    'label' => 'Available Colors',
                    'value' => implode(', ', $socialProduct->available_colors),
                    'position' => $attrPos++,
                ]);
            }
            if (!empty($socialProduct->available_sizes) && count($socialProduct->available_sizes) > 0) {
                ProductAttribute::create([
                    'product_id' => $webProduct->id,
                    'label' => 'Available Sizes',
                    'value' => implode(', ', $socialProduct->available_sizes),
                    'position' => $attrPos++,
                ]);
            }
            ProductAttribute::create([
                'product_id' => $webProduct->id,
                'label' => 'Care Instructions',
                'value' => 'Gentle hand wash or dry clean. Dry in shade.',
                'position' => $attrPos++,
            ]);
            if (!empty($socialProduct->supplier_product_code)) {
                ProductAttribute::create([
                    'product_id' => $webProduct->id,
                    'label' => 'Style Code',
                    'value' => $socialProduct->supplier_product_code,
                    'position' => $attrPos++,
                ]);
            }

            // Copy ALL media asset images to live ProductImage
            $mediaList = $socialProduct->mediaAssets()->where('media_type', 'IMAGE')->get();
            if ($socialProduct->primaryMedia && !$mediaList->contains('id', $socialProduct->primaryMedia->id)) {
                $mediaList->prepend($socialProduct->primaryMedia);
            }

            $imgPos = 0;
            foreach ($mediaList as $media) {
                ProductImage::create([
                    'product_id' => $webProduct->id,
                    'storage_key' => $media->file_path,
                    'alt_text' => $webProduct->name . ' - Photo ' . ($imgPos + 1),
                    'is_actual_product_image' => true,
                    'position' => $imgPos,
                    'width' => $media->width,
                    'height' => $media->height,
                    'byte_size' => $media->file_size_bytes,
                ]);
                $imgPos++;
            }

            // Mark SocialProduct as promoted
            $socialProduct->update([
                'is_promoted_to_web' => true,
                'web_product_id' => $webProduct->id,
            ]);

            Log::info("SocialProduct {$socialProduct->product_code} successfully promoted to web Product #{$webProduct->id} with {$imgPos} images and {$attrPos} attributes.");

            return $webProduct;
        });
    }

    /**
     * Delete a social product.
     */
    public function deleteProduct(SocialProduct $product): bool
    {
        return (bool) $product->delete();
    }

    /**
     * Parse array / comma-separated input.
     */
    protected function parseArrayField(mixed $input): array
    {
        if (is_array($input)) {
            return array_values(array_filter(array_map('trim', $input)));
        }

        if (is_string($input)) {
            $parts = explode(',', $input);
            return array_values(array_filter(array_map('trim', $parts)));
        }

        return [];
    }
}
