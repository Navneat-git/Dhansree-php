<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use RuntimeException;

class ImageProcessor
{
    public const PRODUCT_IMAGE_MIN_SIDE = 200;
    public const MAX_INPUT_PIXELS = 40_000_000;
    public const MAX_OUTPUT_SIDE = 2400;

    /**
     * Magic byte sniff to check image type.
     */
    public function sniffType(string $bytes): ?string
    {
        $len = strlen($bytes);
        if ($len >= 3 && substr($bytes, 0, 3) === "\xFF\xD8\xFF") {
            return 'jpeg';
        }
        if ($len >= 8 && substr($bytes, 0, 8) === "\x89PNG\x0D\x0A\x1A\x0A") {
            return 'png';
        }
        if ($len >= 12 && substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP') {
            return 'webp';
        }
        if ($len >= 12 && substr($bytes, 4, 4) === 'ftyp') {
            return 'avif';
        }

        return null;
    }

    /**
     * Process an image file or buffer into a sanitized, downscaled WebP image.
     *
     * @param UploadedFile|string $file
     * @return array{data: string, width: int, height: int, byteSize: int, contentType: string}
     */
    public function process(UploadedFile|string $file): array
    {
        if ($file instanceof UploadedFile) {
            $bytes = file_get_contents($file->getRealPath());
            if ($bytes === false) {
                throw new InvalidArgumentException('Could not read uploaded file content.');
            }
        } else {
            $bytes = $file;
        }

        if (empty($bytes)) {
            throw new InvalidArgumentException('Image file is empty.');
        }

        $type = $this->sniffType($bytes);
        if ($type === null) {
            throw new InvalidArgumentException('Unsupported image format. Allowed formats are JPEG, PNG, and WebP.');
        }

        $imageInfo = @getimagesizefromstring($bytes);
        if ($imageInfo === false) {
            throw new InvalidArgumentException('Image data is corrupted or unreadable.');
        }

        $origWidth = $imageInfo[0];
        $origHeight = $imageInfo[1];

        if ($origWidth * $origHeight > self::MAX_INPUT_PIXELS) {
            throw new InvalidArgumentException('Image exceeds the maximum allowed resolution (40 MP).');
        }

        if ($origWidth < self::PRODUCT_IMAGE_MIN_SIDE || $origHeight < self::PRODUCT_IMAGE_MIN_SIDE) {
            throw new InvalidArgumentException(
                sprintf('Image is too small (%dx%d). Minimum dimensions are %dx%d pixels.',
                    $origWidth, $origHeight, self::PRODUCT_IMAGE_MIN_SIDE, self::PRODUCT_IMAGE_MIN_SIDE)
            );
        }

        // Load image resource
        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            throw new RuntimeException('Failed to decode image.');
        }

        // Compute output dimensions preserving aspect ratio
        $targetWidth = $origWidth;
        $targetHeight = $origHeight;

        $longest = max($origWidth, $origHeight);
        if ($longest > self::MAX_OUTPUT_SIDE) {
            $ratio = self::MAX_OUTPUT_SIDE / $longest;
            $targetWidth = (int) round($origWidth * $ratio);
            $targetHeight = (int) round($origHeight * $ratio);
        }

        // Create canvas with alpha channel support
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($target === false) {
            imagedestroy($source);
            throw new RuntimeException('Failed to allocate memory for target image.');
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        // Resample with high quality interpolation
        imagecopyresampled(
            $target,
            $source,
            0, 0, 0, 0,
            $targetWidth,
            $targetHeight,
            $origWidth,
            $origHeight
        );

        imagedestroy($source);

        // Encode as WebP with quality 85
        ob_start();
        $success = imagewebp($target, null, 85);
        $webpData = ob_get_clean();
        imagedestroy($target);

        if (!$success || $webpData === false) {
            throw new RuntimeException('Failed to encode image to WebP.');
        }

        return [
            'data' => $webpData,
            'width' => $targetWidth,
            'height' => $targetHeight,
            'byteSize' => strlen($webpData),
            'contentType' => 'image/webp',
        ];
    }
}
