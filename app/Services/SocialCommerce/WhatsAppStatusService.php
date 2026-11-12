<?php

declare(strict_types=1);

namespace App\Services\SocialCommerce;

use App\Models\SocialMediaAsset;
use App\Models\SocialProduct;
use App\Models\SocialSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class WhatsAppStatusService
{
    public const CANVAS_WIDTH = 1080;
    public const CANVAS_HEIGHT = 1920;

    public const FONT_BOLD = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
    public const FONT_REGULAR = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

    public const TEMPLATES = [
        'classic_maroon' => 'Classic Maroon & Gold',
        'clean_overlay' => 'Clean Bottom Overlay',
        'festive_royal' => 'Royal Festive Border',
        'minimal_gold' => 'Minimal Luxury Gold',
    ];

    /**
     * Render a 9:16 (1080x1920) WhatsApp Status image for a media asset and optional product.
     * Returns the relative public storage path (e.g. 'social-commerce/status/card_123.jpg').
     */
    public function renderStatusCard(
        SocialMediaAsset|string $mediaSource,
        ?SocialProduct $product = null,
        array $options = []
    ): string {
        $sourcePath = $this->resolveSourcePath($mediaSource);
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Source image not found: {$sourcePath}");
        }

        $template = $options['template'] ?? 'classic_maroon';
        $brandName = $options['brand_name'] ?? SocialSetting::get('brand_name', 'Dhansree');
        $tagline = $options['tagline'] ?? SocialSetting::get('tagline', 'STYLE. TECH. EVERYDAY.');
        $whatsappNumber = $options['whatsapp_number'] ?? SocialSetting::get('whatsapp_number', '+919887776777');
        $ctaText = $options['cta_text'] ?? SocialSetting::get('whatsapp_order_cta', 'WhatsApp to Order');

        $productName = $options['product_name'] ?? ($product ? $product->name : 'Designer Collection');
        $category = $options['category'] ?? ($product ? $product->category_name : "Women's Fashion");
        $fabric = $options['fabric'] ?? ($product ? $product->fabric : null);
        $productCode = $options['product_code'] ?? ($product ? $product->product_code : null);

        // Price formatting
        $priceText = $options['price_text'] ?? null;
        if ($priceText === null && $product) {
            if ($product->special_offer_price_rupees !== null) {
                $priceText = 'Special: ₹' . number_format($product->special_offer_price_rupees, 0);
            } elseif ($product->suggested_selling_price_rupees !== null) {
                $priceText = '₹' . number_format($product->suggested_selling_price_rupees, 0);
            }
        }

        // Create 1080 x 1920 canvas
        $canvas = imagecreatetruecolor(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        // Color palette
        $maroon = imagecolorallocate($canvas, 0x58, 0x00, 0x00);
        $gold = imagecolorallocate($canvas, 0xB0, 0x88, 0x58);
        $cream = imagecolorallocate($canvas, 0xFB, 0xFA, 0xF8);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $dark = imagecolorallocate($canvas, 26, 5, 5);
        $green = imagecolorallocate($canvas, 0x25, 0xD3, 0x66); // WhatsApp brand green

        // Load source image
        $srcImage = $this->loadSourceImage($sourcePath);
        if (!$srcImage) {
            throw new \RuntimeException("Unable to decode source image: {$sourcePath}");
        }

        $srcW = imagesx($srcImage);
        $srcH = imagesy($srcImage);

        // 1. Draw Background
        // Deep elegant background (dark maroon / cream depending on template)
        imagefilledrectangle($canvas, 0, 0, self::CANVAS_WIDTH, self::CANVAS_HEIGHT, $dark);

        // Draw blurred / zoomed ambient background from source
        imagecopyresampled($canvas, $srcImage, -100, -100, 0, 0, self::CANVAS_WIDTH + 200, self::CANVAS_HEIGHT + 200, $srcW, $srcH);
        imagefilter($canvas, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($canvas, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($canvas, IMG_FILTER_BRIGHTNESS, -40);

        // Dark tint overlay over ambient background
        $tint = imagecolorallocatealpha($canvas, 20, 0, 5, 40);
        imagefilledrectangle($canvas, 0, 0, self::CANVAS_WIDTH, self::CANVAS_HEIGHT, $tint);

        // 2. Position product main photo
        // Reserved space: Top 200px for header, Bottom 320px for card
        $targetY = 200;
        $availH = 1400;
        $availW = 1000;
        $targetX = 40;

        $scale = min($availW / $srcW, $availH / $srcH);
        $dstW = (int) round($srcW * $scale);
        $dstH = (int) round($srcH * $scale);
        $dstX = (int) round($targetX + ($availW - $dstW) / 2);
        $dstY = (int) round($targetY + ($availH - $dstH) / 2);

        // White/Gold frame card behind product image
        imagefilledrectangle($canvas, $dstX - 8, $dstY - 8, $dstX + $dstW + 8, $dstY + $dstH + 8, $gold);
        imagefilledrectangle($canvas, $dstX - 4, $dstY - 4, $dstX + $dstW + 4, $dstY + $dstH + 4, $cream);
        imagecopyresampled($canvas, $srcImage, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($srcImage);

        // 3. Render Top Branding Bar (0 to 190)
        imagefilledrectangle($canvas, 0, 0, self::CANVAS_WIDTH, 180, $maroon);
        // Gold accent bottom line
        imagefilledrectangle($canvas, 0, 175, self::CANVAS_WIDTH, 180, $gold);

        // Brand Name (Centered)
        $brandText = strtoupper($brandName);
        $this->drawText($canvas, $brandText, self::CANVAS_WIDTH / 2, 90, 48, $gold, self::FONT_BOLD, 'center');

        // Tagline (Centered)
        $this->drawText($canvas, $tagline, self::CANVAS_WIDTH / 2, 140, 22, $cream, self::FONT_REGULAR, 'center');

        // 4. Render Bottom Information & CTA Card (1620 to 1920)
        imagefilledrectangle($canvas, 0, 1610, self::CANVAS_WIDTH, self::CANVAS_HEIGHT, $maroon);
        imagefilledrectangle($canvas, 0, 1610, self::CANVAS_WIDTH, 1616, $gold);

        // Product Category Badge (left)
        $categoryBadge = strtoupper($category) . ($productCode ? " • {$productCode}" : '');
        $this->drawText($canvas, $categoryBadge, 60, 1660, 22, $gold, self::FONT_BOLD, 'left');

        // Product Title
        $shortTitle = Str::limit($productName, 36);
        $this->drawText($canvas, $shortTitle, 60, 1715, 34, $white, self::FONT_BOLD, 'left');

        // Fabric (if present)
        if (!empty($fabric)) {
            $this->drawText($canvas, "Fabric: {$fabric}", 60, 1760, 22, $cream, self::FONT_REGULAR, 'left');
        }

        // Price Tag Badge (Pill on right side of bottom card)
        if (!empty($priceText)) {
            $badgeX1 = 760;
            $badgeY1 = 1640;
            $badgeX2 = 1020;
            $badgeY2 = 1730;
            imagefilledrectangle($canvas, $badgeX1, $badgeY1, $badgeX2, $badgeY2, $gold);
            imagefilledrectangle($canvas, $badgeX1 + 3, $badgeY1 + 3, $badgeX2 - 3, $badgeY2 - 3, $cream);
            $this->drawText($canvas, $priceText, (int)(($badgeX1 + $badgeX2) / 2), 1695, 28, $maroon, self::FONT_BOLD, 'center');
        }

        // WhatsApp CTA Button Bar (bottom strip: 1795 to 1890)
        $btnX1 = 50;
        $btnY1 = 1790;
        $btnX2 = 1030;
        $btnY2 = 1885;
        imagefilledrectangle($canvas, $btnX1, $btnY1, $btnX2, $btnY2, $green);
        imagefilledrectangle($canvas, $btnX1 + 4, $btnY1 + 4, $btnX2 - 4, $btnY2 - 4, $green);

        // Clean phone for CTA
        $cleanPhone = preg_replace('/[^0-9]/', '', $whatsappNumber);
        $fullCta = "💬 " . strtoupper($ctaText) . ": +{$cleanPhone}";
        $this->drawText($canvas, $fullCta, self::CANVAS_WIDTH / 2, 1850, 32, $white, self::FONT_BOLD, 'center');

        // Save output to storage
        $outputDir = storage_path('app/public/social-commerce/status');
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $filename = 'status_' . uniqid() . '.jpg';
        $fullOutputPath = $outputDir . '/' . $filename;

        imagejpeg($canvas, $fullOutputPath, 92);
        imagedestroy($canvas);

        return 'social-commerce/status/' . $filename;
    }

    /**
     * Create a ZIP package containing multiple rendered status images.
     */
    public function createZipPackage(array $relativeFilePaths, string $zipName = 'dhansree_status_cards.zip'): string
    {
        $zipDir = storage_path('app/public/social-commerce/downloads');
        if (!File::exists($zipDir)) {
            File::makeDirectory($zipDir, 0755, true);
        }

        $zipPath = $zipDir . '/' . $zipName;
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Unable to create zip file at: {$zipPath}");
        }

        foreach ($relativeFilePaths as $idx => $relPath) {
            $absPath = storage_path('app/public/' . $relPath);
            if (file_exists($absPath)) {
                $entryName = sprintf('dhansree_status_%02d.jpg', $idx + 1);
                $zip->addFile($absPath, $entryName);
            }
        }

        $zip->close();
        return $zipPath;
    }

    /**
     * Generate WhatsApp Web click-to-chat URL with prefilled text.
     */
    public function generateWhatsAppWebUrl(?SocialProduct $product = null, ?string $customMessage = null): string
    {
        $phone = SocialSetting::get('whatsapp_number', '+919887776777');
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        if (!empty($customMessage)) {
            $text = $customMessage;
        } elseif ($product) {
            $price = $product->special_offer_price_rupees ?? $product->suggested_selling_price_rupees ?? '';
            $priceText = $price ? " (Price: ₹{$price})" : '';
            $text = "Hello Dhansree Team! I want to enquire / place an order for {$product->name}{$priceText}. Product Code: {$product->product_code}. Is it available?";
        } else {
            $text = "Hello Dhansree Team! I would like to check the latest women's fashion suit designs.";
        }

        return "https://api.whatsapp.com/send?phone={$cleanPhone}&text=" . urlencode($text);
    }

    /**
     * Draw text using TrueType font or fallback to GD string.
     */
    protected function drawText(
        $image,
        string $text,
        float|int $x,
        float|int $y,
        int $fontSize,
        $color,
        string $fontFile,
        string $align = 'left'
    ): void {
        if (function_exists('imagettftext') && file_exists($fontFile)) {
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
            $textWidth = abs($bbox[4] - $bbox[0]);

            $finalX = match ($align) {
                'center' => (int) round($x - ($textWidth / 2)),
                'right' => (int) round($x - $textWidth),
                default => (int) round($x),
            };

            imagettftext($image, $fontSize, 0, $finalX, (int) round($y), $color, $fontFile, $text);
        } else {
            // Built-in GD font fallback
            $gdFont = 5;
            $charWidth = imagefontwidth($gdFont);
            $textWidth = strlen($text) * $charWidth;

            $finalX = match ($align) {
                'center' => (int) round($x - ($textWidth / 2)),
                'right' => (int) round($x - $textWidth),
                default => (int) round($x),
            };

            imagestring($image, $gdFont, $finalX, (int) round($y - 10), $text, $color);
        }
    }

    /**
     * Resolve absolute disk path from asset or string.
     */
    protected function resolveSourcePath(SocialMediaAsset|string $mediaSource): string
    {
        if ($mediaSource instanceof SocialMediaAsset) {
            return storage_path('app/public/' . $mediaSource->file_path);
        }

        if (file_exists($mediaSource)) {
            return $mediaSource;
        }

        $publicStoragePath = storage_path('app/public/' . $mediaSource);
        if (file_exists($publicStoragePath)) {
            return $publicStoragePath;
        }

        return $mediaSource;
    }

    /**
     * Decode image using GD.
     */
    protected function loadSourceImage(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }

        return match ($info['mime'] ?? '') {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }
}
