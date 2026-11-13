<?php

declare(strict_types=1);

namespace App\Services\SocialCommerce;

use App\Models\AdminUser;
use App\Models\SocialMediaAsset;
use App\Models\SocialProduct;
use App\Models\SocialSupplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class SupplierMediaService
{
    /**
     * Supported media extensions.
     */
    protected array $supportedImageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
    protected array $supportedVideoExtensions = ['mp4', 'mov', 'webm'];

    /**
     * Compute SHA-256 hash for exact file matching.
     */
    public function computeFileHash(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Compute 64-bit difference hash (dHash) for perceptual image duplicate detection using PHP GD.
     */
    public function computePerceptualHash(string $filePath): ?string
    {
        if (!extension_loaded('gd') || !file_exists($filePath)) {
            return null;
        }

        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return null;
        }

        $mime = $imageInfo['mime'] ?? '';
        $srcImage = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($filePath),
            'image/png' => @imagecreatefrompng($filePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : null,
            default => null,
        };

        if (!$srcImage) {
            return null;
        }

        // Resize to 9 columns x 8 rows in grayscale for difference hashing (dHash)
        $resized = imagecreatetruecolor(9, 8);
        imagecopyresampled($resized, $srcImage, 0, 0, 0, 0, 9, 8, imagesx($srcImage), imagesy($srcImage));
        imagefilter($resized, IMG_FILTER_GRAYSCALE);

        $bits = '';
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $leftColor = imagecolorat($resized, $x, $y) & 0xFF;
                $rightColor = imagecolorat($resized, $x + 1, $y) & 0xFF;
                $bits .= ($leftColor > $rightColor) ? '1' : '0';
            }
        }

        imagedestroy($resized);
        imagedestroy($srcImage);

        // Convert 64 bits to 16 hex characters
        $hex = '';
        foreach (str_split($bits, 4) as $nibble) {
            $hex .= dechex((int) bindec($nibble));
        }

        return str_pad($hex, 16, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate Hamming distance between two 16-character hex hashes (0 to 64).
     */
    public function hammingDistance(string $hash1, string $hash2): int
    {
        if (strlen($hash1) !== strlen($hash2)) {
            return 64;
        }

        $distance = 0;
        for ($i = 0; $i < strlen($hash1); $i++) {
            $val1 = hexdec($hash1[$i]);
            $val2 = hexdec($hash2[$i]);
            $xor = $val1 ^ $val2;
            while ($xor > 0) {
                $distance += ($xor & 1);
                $xor >>= 1;
            }
        }

        return $distance;
    }

    /**
     * Check if a file is an exact or perceptual duplicate.
     */
    public function findDuplicate(string $fileHash, ?string $perceptualHash = null): ?SocialMediaAsset
    {
        // 1. Exact match by SHA-256
        $exact = SocialMediaAsset::where('file_hash', $fileHash)->first();
        if ($exact) {
            return $exact;
        }

        // 2. Perceptual match (Hamming distance <= 4 out of 64 bits is ~94% identical)
        if ($perceptualHash) {
            $candidates = SocialMediaAsset::whereNotNull('perceptual_hash')
                ->where('media_type', 'IMAGE')
                ->latest('id')
                ->limit(500)
                ->get();

            foreach ($candidates as $cand) {
                if ($this->hammingDistance($perceptualHash, $cand->perceptual_hash) <= 4) {
                    return $cand;
                }
            }
        }

        return null;
    }

    /**
     * Store a single uploaded media file.
     *
     * @return array{
     *     status: string,
     *     asset: ?SocialMediaAsset,
     *     message: string,
     *     is_duplicate: bool
     * }
     */
    public function storeMediaFile(
        UploadedFile|string $file,
        ?int $supplierId = null,
        ?string $groupName = null,
        ?string $categoryName = null,
        AdminUser|int|null $admin = null,
        string $originalClientName = '',
        ?string $rawDescription = null
    ): array {
        $isUploadedFile = $file instanceof UploadedFile;
        $realPath = $isUploadedFile ? $file->getRealPath() : $file;
        $clientName = $isUploadedFile ? $file->getClientOriginalName() : ($originalClientName ?: basename($file));
        $extension = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));

        if (!file_exists($realPath)) {
            return [
                'status' => 'error',
                'asset' => null,
                'message' => "File does not exist: {$clientName}",
                'is_duplicate' => false,
            ];
        }

        $fileSize = filesize($realPath) ?: 0;
        $fileHash = $this->computeFileHash($realPath);

        $isImage = in_array($extension, $this->supportedImageExtensions, true);
        $isVideo = in_array($extension, $this->supportedVideoExtensions, true);

        if (!$isImage && !$isVideo) {
            return [
                'status' => 'error',
                'asset' => null,
                'message' => "Unsupported format for {$clientName}. Supported formats: JPG, PNG, WEBP, MP4, MOV.",
                'is_duplicate' => false,
            ];
        }

        $perceptualHash = $isImage ? $this->computePerceptualHash($realPath) : null;

        // Check duplicates
        $duplicate = $this->findDuplicate($fileHash, $perceptualHash);
        if ($duplicate) {
            return [
                'status' => 'duplicate',
                'asset' => $duplicate,
                'message' => "Duplicate detected for {$clientName} (already exists as asset #{$duplicate->id}).",
                'is_duplicate' => true,
            ];
        }

        // Determine dimensions
        $width = null;
        $height = null;
        if ($isImage) {
            $dims = @getimagesize($realPath);
            if ($dims) {
                $width = (int) $dims[0];
                $height = (int) $dims[1];
            }
        }

        // Generate target storage path
        $dir = 'social/inbox/' . date('Y/m');
        $fileName = Str::uuid()->toString() . '.' . $extension;
        $storedPath = $dir . '/' . $fileName;

        Storage::disk('public')->put($storedPath, file_get_contents($realPath));

        $mime = $isUploadedFile ? $file->getMimeType() : (mime_content_type($realPath) ?: ($isImage ? 'image/jpeg' : 'video/mp4'));

        $asset = SocialMediaAsset::create([
            'supplier_id' => $supplierId,
            'supplier_group_name' => $groupName,
            'file_path' => $storedPath,
            'file_name' => $clientName,
            'file_size_bytes' => $fileSize,
            'mime_type' => $mime ?: ($isImage ? 'image/jpeg' : 'video/mp4'),
            'media_type' => $isImage ? 'IMAGE' : 'VIDEO',
            'width' => $width,
            'height' => $height,
            'duration_seconds' => null,
            'file_hash' => $fileHash,
            'perceptual_hash' => $perceptualHash,
            'status' => 'UNPROCESSED',
            'category_name' => $categoryName,
            'tags' => [],
            'metadata' => [
                'original_name' => $clientName,
                'uploaded_from' => 'admin_inbox',
                'raw_description' => !empty($rawDescription) ? trim($rawDescription) : null,
            ],
            'created_by_admin_user_id' => $admin instanceof AdminUser ? $admin->id : $admin,
        ]);

        return [
            'status' => 'created',
            'asset' => $asset,
            'message' => "Successfully imported {$clientName}",
            'is_duplicate' => false,
        ];
    }

    /**
     * Import multiple files from bulk upload.
     */
    public function importMultipleFiles(
        array $files,
        ?int $supplierId = null,
        ?string $groupName = null,
        ?string $categoryName = null,
        ?AdminUser $admin = null
    ): array {
        $results = [
            'total' => count($files),
            'imported' => 0,
            'duplicates' => 0,
            'failed' => 0,
            'items' => [],
        ];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $res = $this->storeMediaFile($file, $supplierId, $groupName, $categoryName, $admin);
            $results['items'][] = $res;

            if ($res['status'] === 'created') {
                $results['imported']++;
            } elseif ($res['status'] === 'duplicate') {
                $results['duplicates']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Import media from an uploaded ZIP file.
     */
    public function importFromZip(
        UploadedFile $zipFile,
        ?int $supplierId = null,
        ?string $groupName = null,
        ?string $categoryName = null,
        AdminUser|int|null $admin = null,
        ?string $rawDescription = null
    ): array {
        $zip = new ZipArchive();
        $realPath = $zipFile->getRealPath();

        if ($zip->open($realPath) !== true) {
            throw new \RuntimeException('Failed to open uploaded ZIP file. Ensure it is a valid ZIP archive.');
        }

        $tempDir = storage_path('app/temp/social_zip_' . Str::uuid());
        File::ensureDirectoryExists($tempDir);
        $zip->extractTo($tempDir);
        $zip->close();

        $allFiles = File::allFiles($tempDir);
        $results = [
            'total' => count($allFiles),
            'imported' => 0,
            'duplicates' => 0,
            'failed' => 0,
            'items' => [],
        ];

        foreach ($allFiles as $file) {
            $filename = $file->getFilename();

            // Skip macOS metadata and hidden files
            if (str_starts_with($filename, '.') || str_contains($file->getPathname(), '__MACOSX')) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, array_merge($this->supportedImageExtensions, $this->supportedVideoExtensions), true)) {
                continue;
            }

            $res = $this->storeMediaFile(
                file: $file->getPathname(),
                supplierId: $supplierId,
                groupName: $groupName,
                categoryName: $categoryName,
                admin: $admin,
                originalClientName: $filename,
                rawDescription: $rawDescription
            );

            $results['items'][] = $res;

            if ($res['status'] === 'created') {
                $results['imported']++;
            } elseif ($res['status'] === 'duplicate') {
                $results['duplicates']++;
            } else {
                $results['failed']++;
            }
        }

        // Clean up temporary extract directory
        File::deleteDirectory($tempDir);

        return array_merge($results, [
            'created' => $results['imported'],
            'skipped_duplicate' => $results['duplicates'],
            'errors' => $results['failed'],
        ]);
    }

    /**
     * Bulk delete assets.
     */
    public function bulkDelete(array $assetIds): int
    {
        $assets = SocialMediaAsset::whereIn('id', $assetIds)->get();
        $count = 0;
        foreach ($assets as $asset) {
            // Delete underlying physical file
            if (!empty($asset->file_path) && Storage::disk('public')->exists($asset->file_path)) {
                Storage::disk('public')->delete($asset->file_path);
            }
            $asset->delete();
            $count++;
        }
        return $count;
    }

    /**
     * Bulk categorize assets.
     */
    public function bulkCategorize(array $assetIds, string $categoryName, ?int $supplierId = null): int
    {
        $updates = ['category_name' => $categoryName];
        if ($supplierId !== null) {
            $updates['supplier_id'] = $supplierId;
        }

        return SocialMediaAsset::whereIn('id', $assetIds)->update($updates);
    }

    /**
    /**
     * Alias for storeMediaFile.
     */
    public function processUpload(
        UploadedFile|string $file,
        ?int $supplierId = null,
        ?string $supplierGroupName = null,
        ?string $categoryName = null,
        AdminUser|int|null $adminUserId = null,
        ?string $rawDescription = null
    ): array {
        return $this->storeMediaFile(
            file: $file,
            supplierId: $supplierId,
            groupName: $supplierGroupName,
            categoryName: $categoryName,
            admin: $adminUserId,
            rawDescription: $rawDescription
        );
    }

    /**
     * Alias for importFromZip.
     */
    public function processZipArchive(
        UploadedFile|string $zipFile,
        ?int $supplierId = null,
        ?string $supplierGroupName = null,
        ?string $categoryName = null,
        AdminUser|int|null $adminUserId = null,
        ?string $rawDescription = null
    ): array {
        return $this->importFromZip(
            zipFile: $zipFile,
            supplierId: $supplierId,
            groupName: $supplierGroupName,
            categoryName: $categoryName,
            admin: $adminUserId,
            rawDescription: $rawDescription
        );
    }

    /**
     * Convert selected assets into a new SocialProduct in the Product Library.
     */
    public function convertAssetsToProduct(
        array $assetIds,
        array $data,
        AdminUser|int|null $admin = null
    ): SocialProduct {
        $adminUserId = $admin instanceof AdminUser ? $admin->id : $admin;

        return DB::transaction(function () use ($assetIds, $data, $adminUserId) {
            $assets = SocialMediaAsset::whereIn('id', $assetIds)->get();
            if ($assets->isEmpty()) {
                throw new \InvalidArgumentException('Please select at least one media item to create a product.');
            }

            $firstAsset = $assets->first();

            // Next product code
            $latest = SocialProduct::withTrashed()->latest('id')->first();
            $nextNum = ($latest?->id ?? 0) + 1;
            $productCode = 'DS-SP-' . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

            $name = !empty($data['name']) ? trim($data['name']) : 'Supplier Design ' . $productCode;
            $categoryName = $data['category_name'] ?? $firstAsset->category_name ?? "Women's Suits";
            $supplierId = !empty($data['supplier_id']) ? (int) $data['supplier_id'] : $firstAsset->supplier_id;

            $purchaseRupees = isset($data['purchase_price']) && is_numeric($data['purchase_price']) ? (float) $data['purchase_price'] : null;
            $sellingRupees = isset($data['suggested_selling_price']) && is_numeric($data['suggested_selling_price'])
                ? (float) $data['suggested_selling_price']
                : (isset($data['selling_price']) && is_numeric($data['selling_price']) ? (float) $data['selling_price'] : null);
            $offerRupees = isset($data['special_offer_price']) && is_numeric($data['special_offer_price'])
                ? (float) $data['special_offer_price']
                : (isset($data['offer_price']) && is_numeric($data['offer_price']) ? (float) $data['offer_price'] : null);

            $colors = is_string($data['available_colors'] ?? null)
                ? array_values(array_filter(array_map('trim', explode(',', $data['available_colors']))))
                : ($data['available_colors'] ?? []);
            $sizes = is_string($data['available_sizes'] ?? null)
                ? array_values(array_filter(array_map('trim', explode(',', $data['available_sizes']))))
                : ($data['available_sizes'] ?? ['M', 'L', 'XL', 'XXL']);

            $rawDescFallback = $firstAsset->metadata['raw_description'] ?? null;
            $finalDescription = !empty($data['description']) ? trim((string) $data['description']) : $rawDescFallback;

            $product = SocialProduct::create([
                'product_code' => $productCode,
                'name' => $name,
                'category_name' => $categoryName,
                'supplier_id' => $supplierId,
                'supplier_product_code' => $data['supplier_product_code'] ?? null,
                'fabric' => $data['fabric'] ?? null,
                'available_colors' => $colors,
                'available_sizes' => $sizes,
                'purchase_price_paise' => $purchaseRupees !== null ? (int) round($purchaseRupees * 100) : null,
                'suggested_selling_price_paise' => $sellingRupees !== null ? (int) round($sellingRupees * 100) : null,
                'special_offer_price_paise' => $offerRupees !== null ? (int) round($offerRupees * 100) : null,
                'description' => $finalDescription,
                'notes' => $data['notes'] ?? null,
                'availability_status' => $data['availability_status'] ?? 'NEW',
                'last_availability_confirmed_at' => now(),
                'primary_media_asset_id' => $firstAsset->id,
                'is_promoted_to_web' => false,
                'created_by_admin_user_id' => $adminUserId,
            ]);

            // Attach all assets to product
            $position = 0;
            foreach ($assets as $asset) {
                $product->mediaAssets()->attach($asset->id, [
                    'position' => $position,
                    'is_primary' => $position === 0,
                    'color_variant' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update asset status
                $asset->update(['status' => 'IN_PRODUCT']);
                $position++;
            }

            return $product;
        });
    }
}
