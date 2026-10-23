<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\AuditActorType;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductImageOptionValue;
use App\Models\ProductImageVariant;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductImageService
{
    public function __construct(
        private readonly ImageProcessor $processor
    ) {}

    /**
     * Upload and attach an image to a product.
     *
     * @param int[] $variantIds
     * @param int[] $optionValueIds
     */
    public function addImage(
        Product $product,
        UploadedFile $file,
        string $altText,
        array $variantIds = [],
        array $optionValueIds = [],
        ?int $adminUserId = null,
        ?Request $request = null
    ): ProductImage {
        return DB::transaction(function () use ($product, $file, $altText, $variantIds, $optionValueIds, $adminUserId, $request) {
            $processed = $this->processor->process($file);
            $storageKey = sprintf('products/%d/%s.webp', $product->id, (string) Str::uuid());

            // Save to storage
            Storage::disk('public')->put('media/' . $storageKey, $processed['data']);
            @chmod(storage_path('app/public/media/' . $storageKey), 0664);

            // Also mirror directly into public/media for direct static serving
            $publicFile = public_path('media/' . $storageKey);
            $publicDir = dirname($publicFile);
            if (!is_dir($publicDir)) {
                @mkdir($publicDir, 0775, true);
            }
            if (!file_exists($publicFile)) {
                @file_put_contents($publicFile, $processed['data']);
                @chmod($publicFile, 0664);
            }

            $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
            if ($extraDir) {
                $targetFile = rtrim($extraDir, '/') . '/' . ltrim($storageKey, '/');
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                @file_put_contents($targetFile, $processed['data']);
                @chmod($targetFile, 0664);
            }

            $maxPos = ProductImage::where('product_id', $product->id)->max('position');
            $position = $maxPos !== null ? $maxPos + 1 : 0;

            $image = ProductImage::create([
                'product_id' => $product->id,
                'storage_key' => $storageKey,
                'alt_text' => Str::limit(trim($altText ?: $product->name), 160),
                'position' => $position,
                'width' => $processed['width'],
                'height' => $processed['height'],
                'byte_size' => $processed['byteSize'],
            ]);

            // Link to variants
            foreach ($variantIds as $vId) {
                ProductImageVariant::create([
                    'image_id' => $image->id,
                    'variant_id' => (int) $vId,
                ]);
            }

            // Link to option values
            foreach ($optionValueIds as $ovId) {
                ProductImageOptionValue::create([
                    'image_id' => $image->id,
                    'option_value_id' => (int) $ovId,
                ]);
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.image.uploaded',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product_image',
                    entityId: $image->id,
                    metadata: [
                        'product_id' => $product->id,
                        'width' => $processed['width'],
                        'height' => $processed['height'],
                        'byte_size' => $processed['byteSize'],
                    ],
                    request: $request
                );
            }

            return $image;
        });
    }

    /**
     * Delete product image.
     */
    public function deleteImage(ProductImage $image, ?int $adminUserId = null, ?Request $request = null): void
    {
        DB::transaction(function () use ($image, $adminUserId, $request) {
            $product = Product::findOrFail($image->product_id);

            // If product is ACTIVE, verify it has at least one other image
            if ($product->status === ProductStatus::ACTIVE) {
                $count = ProductImage::where('product_id', $product->id)->count();
                if ($count <= 1) {
                    throw new InvalidArgumentException('Cannot delete the last image of an active product. Upload a replacement image or unpublish the product first.');
                }
            }

            $storageKey = $image->storage_key;
            $productId = $image->product_id;
            $deletedPos = $image->position;

            // Delete links
            ProductImageVariant::where('image_id', $image->id)->delete();
            ProductImageOptionValue::where('image_id', $image->id)->delete();

            $image->delete();

            // Re-sequence remaining images
            $remaining = ProductImage::where('product_id', $productId)
                ->orderBy('position')
                ->get();
            foreach ($remaining as $idx => $remImg) {
                if ($remImg->position !== $idx) {
                    $remImg->update(['position' => $idx]);
                }
            }

            // Delete physical file
            Storage::disk('public')->delete('media/' . $storageKey);

            $extraDir = config('store.media_local_directory');
            if ($extraDir) {
                $targetFile = rtrim($extraDir, '/') . '/' . ltrim($storageKey, '/');
                if (file_exists($targetFile)) {
                    @unlink($targetFile);
                }
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.image.deleted',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product_image',
                    entityId: $image->id,
                    metadata: ['product_id' => $productId],
                    request: $request
                );
            }
        });
    }

    /**
     * Set an image as primary (moves it to position 0).
     */
    public function setPrimary(ProductImage $image, ?int $adminUserId = null, ?Request $request = null): void
    {
        DB::transaction(function () use ($image, $adminUserId, $request) {
            $all = ProductImage::where('product_id', $image->product_id)
                ->orderBy('position')
                ->get();

            $reordered = [$image->id];
            foreach ($all as $item) {
                if ($item->id !== $image->id) {
                    $reordered[] = $item->id;
                }
            }

            foreach ($reordered as $index => $id) {
                ProductImage::where('id', $id)->update(['position' => $index]);
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.image.reordered',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product_image',
                    entityId: $image->id,
                    metadata: ['is_primary' => true],
                    request: $request
                );
            }
        });
    }

    /**
     * Reorder list of images for a product.
     *
     * @param int[] $orderedIds
     */
    public function reorder(Product $product, array $orderedIds, ?int $adminUserId = null, ?Request $request = null): void
    {
        DB::transaction(function () use ($product, $orderedIds, $adminUserId, $request) {
            foreach ($orderedIds as $index => $id) {
                ProductImage::where('id', $id)
                    ->where('product_id', $product->id)
                    ->update(['position' => $index]);
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.image.reordered',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product',
                    entityId: $product->id,
                    metadata: ['order' => $orderedIds],
                    request: $request
                );
            }
        });
    }

    /**
     * Update image alt text and variant links.
     *
     * @param int[] $variantIds
     * @param int[] $optionValueIds
     */
    public function updateImage(
        ProductImage $image,
        string $altText,
        array $variantIds = [],
        array $optionValueIds = [],
        ?int $adminUserId = null,
        ?Request $request = null
    ): ProductImage {
        return DB::transaction(function () use ($image, $altText, $variantIds, $optionValueIds, $adminUserId, $request) {
            $image->update([
                'alt_text' => Str::limit(trim($altText), 160),
            ]);

            ProductImageVariant::where('image_id', $image->id)->delete();
            foreach ($variantIds as $vId) {
                ProductImageVariant::create([
                    'image_id' => $image->id,
                    'variant_id' => (int) $vId,
                ]);
            }

            ProductImageOptionValue::where('image_id', $image->id)->delete();
            foreach ($optionValueIds as $ovId) {
                ProductImageOptionValue::create([
                    'image_id' => $image->id,
                    'option_value_id' => (int) $ovId,
                ]);
            }

            if ($adminUserId) {
                AuditLogger::log(
                    'catalog.image.assigned',
                    AuditActorType::ADMIN,
                    actorAdminUserId: $adminUserId,
                    entityType: 'product_image',
                    entityId: $image->id,
                    metadata: ['variants' => $variantIds, 'option_values' => $optionValueIds],
                    request: $request
                );
            }

            return $image;
        });
    }
}
