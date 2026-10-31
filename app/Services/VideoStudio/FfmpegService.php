<?php

namespace App\Services\VideoStudio;

use App\Models\Product;
use App\Models\ProductVideo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class FfmpegService
{
    protected string $ffmpegPath;
    protected string $ffprobePath;

    public function __construct()
    {
        $this->ffmpegPath = $this->resolveBinary('ffmpeg_path', 'ffmpeg');
        $this->ffprobePath = $this->resolveBinary('ffprobe_path', 'ffprobe');
    }

    /**
     * Resolve executable binary with multiple search locations.
     */
    protected function resolveBinary(string $configKey, string $defaultBinary): string
    {
        $configured = config("video_studio.{$configKey}");
        if ($configured && (file_exists($configured) || !str_contains($configured, '/'))) {
            return $configured;
        }

        $candidates = [
            '/usr/bin/' . $defaultBinary,
            '/usr/local/bin/' . $defaultBinary,
            '/home/administrator/.local/bin/' . $defaultBinary,
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return $defaultBinary;
    }

    protected function canExecute(string $binary): bool
    {
        try {
            if (!function_exists('proc_open')) {
                return false;
            }
            if (str_contains($binary, '/') && (!file_exists($binary) || !is_executable($binary))) {
                return false;
            }
            $process = new Process([$binary, '-version']);
            $process->setTimeout(2);
            $process->run();
            return $process->isSuccessful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check whether FFmpeg is available and executable.
     */
    public function isAvailable(): bool
    {
        return $this->canExecute($this->ffmpegPath);
    }

    /**
     * Get the installed FFmpeg version string.
     */
    public function getVersion(): ?string
    {
        try {
            $process = new Process([$this->ffmpegPath, '-version']);
            $process->setTimeout(2);
            $process->run();
            if ($process->isSuccessful()) {
                $firstLine = explode("\n", trim($process->getOutput()))[0] ?? '';
                if (preg_match('/ffmpeg version ([^\s]+)/i', $firstLine, $matches)) {
                    $raw = explode('-', $matches[1])[0];
                    return 'v' . $raw;
                }
                return $firstLine ? substr($firstLine, 0, 30) : null;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }

    /**
     * Render the product slideshow video.
     *
     * @param ProductVideo $video
     * @param array<string> $imagePaths Local absolute paths of selected images
     * @param string $tempDir Working temporary directory
     * @return array{video_path: string, thumbnail_path: string, file_size: int}
     * @throws \RuntimeException
     */
    public function renderSlideshow(ProductVideo $video, array $imagePaths, string $tempDir): array
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException("FFmpeg executable not found or not executable at '{$this->ffmpegPath}'.");
        }

        if (empty($imagePaths)) {
            throw new \RuntimeException("At least one product image is required to generate a video.");
        }

        File::ensureDirectoryExists($tempDir);

        $width = $video->width ?: 1080;
        $height = $video->height ?: 1920;
        $duration = max(5, min(30, (int) ($video->duration_seconds ?: 10)));
        $templateKey = $video->template ?: 'fashion_showcase';
        $templateConfig = config("video_studio.templates.{$templateKey}", []);
        $settings = $video->render_settings ?? [];

        // 1. Prepare and normalize each slide into full-canvas image with premium fashion framing
        $preparedSlides = [];
        foreach ($imagePaths as $idx => $rawImgPath) {
            $slideFile = $tempDir . DIRECTORY_SEPARATOR . sprintf('prepared_slide_%02d.png', $idx);
            $this->createFramedSlide($rawImgPath, $slideFile, $width, $height, $templateConfig);
            $preparedSlides[] = $slideFile;
        }

        // 2. Generate graphic overlay elements (watermark, title/price card, closing CTA)
        $overlayFile = $tempDir . DIRECTORY_SEPARATOR . 'overlay_card.png';
        $this->createOverlayGraphic($video, $overlayFile, $width, $height, $templateConfig, $settings);

        // 3. Assemble slideshow video with crossfades
        $baseVideoFile = $tempDir . DIRECTORY_SEPARATOR . 'base_slideshow.mp4';
        $this->renderTransitions($preparedSlides, $baseVideoFile, $width, $height, $duration);

        // 4. Burn overlay graphic and final call-to-action
        $finalVideoFile = $tempDir . DIRECTORY_SEPARATOR . 'final_render.mp4';
        $this->applyOverlaysAndAudio($baseVideoFile, $overlayFile, $finalVideoFile, $video, $duration, $settings);

        if (!file_exists($finalVideoFile) || filesize($finalVideoFile) === 0) {
            throw new \RuntimeException("Video rendering failed: output file is empty or was not created.");
        }

        // 5. Generate high-quality thumbnail at 1.0 second
        $thumbnailFile = $tempDir . DIRECTORY_SEPARATOR . 'poster_thumbnail.jpg';
        $this->generateThumbnail($finalVideoFile, $thumbnailFile);

        return [
            'video_file' => $finalVideoFile,
            'thumbnail_file' => $thumbnailFile,
            'file_size' => filesize($finalVideoFile),
        ];
    }

    /**
     * Post-process an AI-generated fashion video with Dhansree branding or clean storefront WebM optimization.
     *
     * @return array{video_file: string, webm_file?: string|null, branded_file?: string|null, thumbnail_file: string, file_size: int}
     */
    public function brandAiVideo(
        ProductVideo $video,
        string $rawMasterVideoFile,
        string $tempDir
    ): array {
        $width = $video->width ?: 1080;
        $height = $video->height ?: 1920;
        $duration = $video->duration_seconds ?: 5;
        $settings = $video->render_settings ?? [];
        $exportPreset = $settings['export_preset'] ?? 'clean_storefront';
        $templateKey = $video->template ?? 'fashion_showcase';
        $templateConfig = config("video_studio.templates.{$templateKey}", [
            'name' => "Women's Fashion Showcase",
            'tag' => 'ELEGANT COLLECTION',
            'badge_color' => '#B08858',
            'bg_tone' => '#140808',
        ]);

        $thumbnailFile = $tempDir . DIRECTORY_SEPARATOR . 'ai_poster_thumbnail.webp';
        $result = [];

        // 1. Clean Storefront (WebM + MP4) - Optimized for website PDP and listings with zero distracting overlays or audio
        if ($exportPreset === 'clean_storefront' || $exportPreset === 'dual') {
            $cleanWebmFile = $tempDir . DIRECTORY_SEPARATOR . 'clean_storefront.webm';
            $this->renderCleanWebm($rawMasterVideoFile, $cleanWebmFile, $width, $height);

            $cleanMp4File = $tempDir . DIRECTORY_SEPARATOR . 'clean_storefront.mp4';
            $this->renderCleanMp4($rawMasterVideoFile, $cleanMp4File, $width, $height);

            $this->generateThumbnail($cleanMp4File, $thumbnailFile);

            $result['video_file'] = $cleanMp4File;
            $result['webm_file'] = file_exists($cleanWebmFile) ? $cleanWebmFile : null;
            $result['thumbnail_file'] = $thumbnailFile;
            $result['file_size'] = filesize($cleanMp4File);
        }

        // 2. Social Branded MP4 - Burns luxury Dhansree logo, verified price, royal audio music, and closing CTA
        if ($exportPreset === 'social_branded' || $exportPreset === 'dual') {
            $overlayFile = $tempDir . DIRECTORY_SEPARATOR . 'ai_overlay_card.png';
            $this->createOverlayGraphic($video, $overlayFile, $width, $height, $templateConfig, $settings);

            $brandedVideoFile = $tempDir . DIRECTORY_SEPARATOR . 'ai_branded_render.mp4';
            $this->applyOverlaysAndAudio($rawMasterVideoFile, $overlayFile, $brandedVideoFile, $video, $duration, $settings);

            if (!file_exists($brandedVideoFile) || filesize($brandedVideoFile) === 0) {
                throw new \RuntimeException("AI video branding failed: output file is empty or was not created.");
            }

            if ($exportPreset === 'social_branded') {
                $this->generateThumbnail($brandedVideoFile, $thumbnailFile);
                $result['video_file'] = $brandedVideoFile;
                $result['thumbnail_file'] = $thumbnailFile;
                $result['file_size'] = filesize($brandedVideoFile);
            } else {
                // In dual export mode: clean MP4/WebM serves the storefront, and branded MP4 is available for social media download
                $result['branded_file'] = $brandedVideoFile;
            }
        }

        return $result;
    }

    /**
     * Render clean, high-efficiency WebM (VP9, muted) for instant web playback.
     */
    public function renderCleanWebm(string $inputFile, string $outputWebmFile, int $width, int $height): void
    {
        $scaleFilter = "scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2,format=yuv420p";
        $cmd = [
            $this->ffmpegPath, '-y',
            '-i', $inputFile,
            '-vf', $scaleFilter,
            '-c:v', 'libvpx-vp9',
            '-deadline', 'realtime',
            '-cpu-used', '4',
            '-crf', '32',
            '-b:v', '0',
            '-an',
            $outputWebmFile,
        ];
        $this->runCommand($cmd, 120);
    }

    /**
     * Render clean web-optimized MP4 (H.264, muted, faststart) as universal web fallback.
     */
    public function renderCleanMp4(string $inputFile, string $outputMp4File, int $width, int $height): void
    {
        $scaleFilter = "scale={$width}:{$height}:force_original_aspect_ratio=decrease,pad={$width}:{$height}:(ow-iw)/2:(oh-ih)/2,format=yuv420p";
        $cmd = [
            $this->ffmpegPath, '-y',
            '-i', $inputFile,
            '-vf', $scaleFilter,
            '-c:v', 'libx264',
            '-preset', 'fast',
            '-crf', '22',
            '-an',
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            $outputMp4File,
        ];
        $this->runCommand($cmd, 120);
    }

    /**
     * Public helper to generate poster thumbnail image from any video.
     */
    public function extractThumbnail(string $videoPath, string $thumbPath): void
    {
        $this->generateThumbnail($videoPath, $thumbPath);
    }

    /**
     * Inspect video metadata (width, height, duration) using ffprobe.
     *
     * @return array{width: int, height: int, duration: int}
     */
    public function getVideoDimensions(string $videoPath): array
    {
        try {
            $cmd = [
                $this->ffprobePath,
                '-v', 'error',
                '-select_streams', 'v:0',
                '-show_entries', 'stream=width,height,duration',
                '-of', 'json',
                $videoPath,
            ];
            $process = new Process($cmd);
            $process->setTimeout(10);
            $process->run();
            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput(), true);
                $stream = $data['streams'][0] ?? [];
                $w = (int) ($stream['width'] ?? 0);
                $h = (int) ($stream['height'] ?? 0);
                $dur = (int) round((float) ($stream['duration'] ?? 10));
                if ($w > 0 && $h > 0) {
                    return [
                        'width' => $w,
                        'height' => $h,
                        'duration' => max(1, $dur),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("FFprobe metadata inspection failed: " . $e->getMessage());
        }

        return ['width' => 1080, 'height' => 1920, 'duration' => 10];
    }

    public function getFfmpegPath(): string
    {
        return $this->ffmpegPath;
    }

    public function getFfprobePath(): string
    {
        return $this->ffprobePath;
    }

    /**
     * Create a framed slide image fitting the canvas with no distortion.
     * Uses subtle blur background padding of the original image + crisp centered foreground.
     */
    protected function createFramedSlide(
        string $sourceImagePath,
        string $destImagePath,
        int $canvasW,
        int $canvasH,
        array $templateConfig
    ): void {
        $source = $this->loadImage($sourceImagePath);
        if (!$source) {
            throw new \RuntimeException("Unable to load source image: {$sourceImagePath}");
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        imagealphablending($canvas, true);

        // Default background tone from template
        $bgHex = $templateConfig['bg_tone'] ?? '#140808';
        [$r, $g, $b] = $this->hexToRgb($bgHex);
        $bgColor = imagecolorallocate($canvas, $r, $g, $b);
        imagefilledrectangle($canvas, 0, 0, $canvasW, $canvasH, $bgColor);

        // Render blurred ambient background from the product photo for luxury aesthetic
        $blurFactor = 0.05;
        $smallW = max(10, (int) ($canvasW * $blurFactor));
        $smallH = max(10, (int) ($canvasH * $blurFactor));
        $small = imagecreatetruecolor($smallW, $smallH);
        imagecopyresampled($small, $source, 0, 0, 0, 0, $smallW, $smallH, $srcW, $srcH);

        // Scale back up to cover full canvas (fills background elegantly)
        $bgCover = imagecreatetruecolor($canvasW, $canvasH);
        imagecopyresampled($bgCover, $small, 0, 0, 0, 0, $canvasW, $canvasH, $smallW, $smallH);
        imagedestroy($small);

        // Darken / tint the background so foreground stands out
        imagefilter($bgCover, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($bgCover, IMG_FILTER_BRIGHTNESS, -60);
        imagecopymerge($canvas, $bgCover, 0, 0, 0, 0, $canvasW, $canvasH, 85);
        imagedestroy($bgCover);

        // Calculate aspect-preserving fit for foreground product image
        // Leave margin at top for brand header (120px) and bottom for product card (320px)
        $marginV = (int) ($canvasH * 0.08);
        $availW = (int) ($canvasW * 0.90);
        $availH = (int) ($canvasH - ($marginV * 2));

        $scale = min($availW / $srcW, $availH / $srcH);
        $dstW = (int) ($srcW * $scale);
        $dstH = (int) ($srcH * $scale);
        $dstX = (int) (($canvasW - $dstW) / 2);
        $dstY = (int) (($canvasH - $dstH) / 2);

        // Add soft dark shadow under the garment for depth
        $shadowColor = imagecolorallocatealpha($canvas, 0, 0, 0, 95);
        $shadowPadding = 8;
        imagefilledrectangle(
            $canvas,
            max(0, $dstX - $shadowPadding),
            max(0, $dstY - $shadowPadding),
            min($canvasW, $dstX + $dstW + $shadowPadding),
            min($canvasH, $dstY + $dstH + $shadowPadding),
            $shadowColor
        );

        // Place original product photo uncropped and undistorted
        imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($source);

        imagepng($canvas, $destImagePath, 6);
        imagedestroy($canvas);
    }

    /**
     * Resolve path to a bold TrueType font.
     */
    protected function resolveBoldFont(): ?string
    {
        $candidates = [
            config('video_studio.brand.font_bold'),
            resource_path('fonts/LiberationSans-Bold.ttf'),
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
        ];

        foreach ($candidates as $cand) {
            if ($cand && file_exists($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Resolve path to a regular TrueType font.
     */
    protected function resolveRegularFont(): ?string
    {
        $candidates = [
            config('video_studio.brand.font_regular'),
            resource_path('fonts/LiberationSans-Regular.ttf'),
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
        ];

        foreach ($candidates as $cand) {
            if ($cand && file_exists($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Create transparent PNG overlay with Dhansree branding or modern Watch in Motion card.
     */
    protected function createOverlayGraphic(
        ProductVideo $video,
        string $destImagePath,
        int $canvasW,
        int $canvasH,
        array $templateConfig,
        array $settings
    ): void {
        // When modern Watch in Motion style is requested or by default for social reels without brand header plate
        if (($settings['show_brand_header'] ?? false) !== true) {
            $this->createWatchInMotionOverlayGraphic($video, $destImagePath, $canvasW, $canvasH, $settings, $templateConfig);
            return;
        }

        $im = imagecreatetruecolor($canvasW, $canvasH);
        imagealphablending($im, false);
        imagesavealpha($im, true);

        // Transparent canvas
        $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefilledrectangle($im, 0, 0, $canvasW, $canvasH, $trans);
        imagealphablending($im, true);

        $fontBold = $this->resolveBoldFont();
        $fontRegular = $this->resolveRegularFont();

        // Brand colors
        $maroon = imagecolorallocate($im, 88, 0, 0);
        $gold = imagecolorallocate($im, 176, 136, 88);
        $goldBright = imagecolorallocate($im, 212, 175, 55);
        $white = imagecolorallocate($im, 255, 255, 255);
        $creamBg = imagecolorallocatealpha($im, 251, 250, 248, 8); // 94% opaque luxury cream plate
        $cardBg = imagecolorallocatealpha($im, 16, 6, 6, 25);
        $badgeBg = imagecolorallocatealpha($im, 88, 0, 0, 25);
        $shadow = imagecolorallocatealpha($im, 0, 0, 0, 80);

        // Top safe area: Dhansree Brand Header inside Luxury Contrast Plate (only when explicitly requested)
        $showBrandHeader = ($settings['show_brand_header'] ?? false) === true;
        if ($showBrandHeader) {
            $logoPath = config('video_studio.brand.logo_path');
            $tagY = (int) ($canvasH * 0.10);
            if (file_exists($logoPath)) {
                $logo = @imagecreatefrompng($logoPath);
                if ($logo) {
                    $lw = imagesx($logo);
                    $lh = imagesy($logo);
                    $targetLogoH = (int) ($canvasH * 0.040);
                    $targetLogoW = (int) ($lw * ($targetLogoH / $lh));

                    $padX = 32;
                    $padY = 14;
                    $cardW = $targetLogoW + ($padX * 2);
                    $cardH = $targetLogoH + ($padY * 2);
                    $cardX = (int) (($canvasW - $cardW) / 2);
                    $cardY = (int) ($canvasH * 0.045);

                    imagefilledrectangle($im, $cardX + 3, $cardY + 3, $cardX + $cardW + 3, $cardY + $cardH + 3, $shadow);
                    imagefilledrectangle($im, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $creamBg);
                    imagerectangle($im, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $gold);

                    imagecopyresampled($im, $logo, $cardX + $padX, $cardY + $padY, 0, 0, $targetLogoW, $targetLogoH, $lw, $lh);
                    imagedestroy($logo);

                    $tagY = $cardY + $cardH + 24;
                }
            }

            // Template Tag Badge
            $tagText = strtoupper($templateConfig['tag'] ?? 'DHANSREE TRENDS');
            $this->drawBadge($im, $tagText, (int) ($canvasW / 2), $tagY, $white, $badgeBg, $fontBold, $goldBright);
        }

        // Bottom Product Title & Price Card
        $showOverlays = $settings['text_overlay'] ?? true;
        if ($showOverlays) {
            $product = $video->product;
            $title = $video->title ?: ($product?->name ?? 'Dhansree Exclusive');
            $priceText = '';
            if ($product && ($settings['show_price'] ?? true)) {
                $priceText = $product->formatted_price;
            }

            $cardW = (int) ($canvasW * 0.88);
            $cardH = (int) ($canvasH * 0.125);
            $cardX = (int) (($canvasW - $cardW) / 2);
            $cardY = (int) ($canvasH * 0.82);

            imagefilledrectangle($im, $cardX + 4, $cardY + 4, $cardX + $cardW + 4, $cardY + $cardH + 4, $shadow);
            imagefilledrectangle($im, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $cardBg);
            imagerectangle($im, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $gold);

            $displayTitle = mb_strimwidth($title, 0, 44, '...');
            if ($fontBold) {
                $titleFontSize = (int) ($canvasW * 0.025);
                $bbox = imagettfbbox($titleFontSize, 0, $fontBold, $displayTitle);
                $tWidth = abs($bbox[2] - $bbox[0]);
                $tX = (int) (($canvasW - $tWidth) / 2);
                $tY = $cardY + (int) ($cardH * 0.44);
                imagettftext($im, $titleFontSize, 0, $tX, $tY, $white, $fontBold, $displayTitle);
            } else {
                $tX = (int) (($canvasW - (strlen($displayTitle) * 9)) / 2);
                imagestring($im, 5, $tX, $cardY + 16, $displayTitle, $white);
            }

            $subline = $priceText ? "{$priceText}  •  Dhansree Trends" : "Exclusively at dhansree.com";
            if ($fontBold) {
                $subFontSize = (int) ($canvasW * 0.021);
                $sbox = imagettfbbox($subFontSize, 0, $fontBold, $subline);
                $sWidth = abs($sbox[2] - $sbox[0]);
                $sX = (int) (($canvasW - $sWidth) / 2);
                $sY = $cardY + (int) ($cardH * 0.80);
                imagettftext($im, $subFontSize, 0, $sX, $sY, $goldBright, $fontBold, $subline);
            } else {
                $sX = (int) (($canvasW - (strlen($subline) * 8)) / 2);
                imagestring($im, 5, $sX, $cardY + 54, $subline, $goldBright);
            }
        }

        imagepng($im, $destImagePath, 6);
        imagedestroy($im);
    }

    /**
     * Create transparent PNG overlay replicating the frontend "Watch in Motion" modal popup.
     * Includes AI Video Disclaimer pill (if AI) and bottom floating frosted card with
     * FEATURED IN REEL tag, wrapped product title, pricing (with slashed MRP and discount %),
     * and bright amber "Buy Now →" button.
     */
    public function createWatchInMotionOverlayGraphic(
        ProductVideo $video,
        string $destImagePath,
        int $canvasW = 1080,
        int $canvasH = 1920,
        array $settings = [],
        array $templateConfig = []
    ): void {
        $im = imagecreatetruecolor($canvasW, $canvasH);
        imagealphablending($im, false);
        imagesavealpha($im, true);

        // Transparent canvas
        $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefilledrectangle($im, 0, 0, $canvasW, $canvasH, $trans);
        imagealphablending($im, true);

        $fontBold = $this->resolveBoldFont();
        $fontRegular = $this->resolveRegularFont() ?: $fontBold;

        // Color palette matching Watch in Motion storefront modal
        $amber = imagecolorallocate($im, 251, 191, 36); // #FBBF24
        $orangeBtn = imagecolorallocate($im, 245, 158, 11); // #F59E0B Amber-500
        $white = imagecolorallocate($im, 255, 255, 255);
        $mrpGray = imagecolorallocate($im, 203, 213, 225); // #CBD5E1 Slate-300
        $green = imagecolorallocate($im, 52, 211, 153); // #34D399 Emerald-400
        $darkSlate = imagecolorallocate($im, 15, 23, 42); // #0F172A Slate-900
        $cardBg = imagecolorallocatealpha($im, 10, 10, 10, 52); // Frosted dark glassmorphism (~60% opacity)
        $cardBorder = imagecolorallocatealpha($im, 255, 255, 255, 80); // Subtle 30% white border
        $shadow = imagecolorallocatealpha($im, 0, 0, 0, 95);

        // Scale factors relative to 1080x1920 base
        $scaleX = $canvasW / 1080.0;
        $scaleY = $canvasH / 1920.0;

        // 1. Top-Left: AI Disclaimer Pill (if video is AI generated)
        if ($video->isAiGenerated()) {
            $pillX1 = (int) (40 * $scaleX);
            $pillY1 = (int) (50 * $scaleY);
            $pillW = (int) (320 * $scaleX);
            $pillH = (int) (60 * $scaleY);
            $pillX2 = $pillX1 + $pillW;
            $pillY2 = $pillY1 + $pillH;
            $pillRad = (int) ($pillH / 2);

            $this->drawRoundedRect($im, $pillX1, $pillY1, $pillX2, $pillY2, $pillRad, imagecolorallocatealpha($im, 0, 0, 0, 25));
            $this->drawRoundedRectBorder($im, $pillX1, $pillY1, $pillX2, $pillY2, $pillRad, $amber);

            // Icon circle with italic 'i'
            $iconSize = (int) (32 * $scaleX);
            $iconCenterX = $pillX1 + (int) (30 * $scaleX);
            $iconCenterY = (int) (($pillY1 + $pillY2) / 2);
            imagefilledellipse($im, $iconCenterX, $iconCenterY, $iconSize, $iconSize, imagecolorallocatealpha($im, 251, 191, 36, 100));
            imagearc($im, $iconCenterX, $iconCenterY, $iconSize, $iconSize, 0, 360, $amber);

            if ($fontBold) {
                $iconFontSize = max(10, (int) (14 * $scaleX));
                $ibox = imagettfbbox($iconFontSize, 0, $fontBold, 'i');
                $iw = abs($ibox[2] - $ibox[0]);
                $ih = abs($ibox[1] - $ibox[7]);
                imagettftext($im, $iconFontSize, 0, $iconCenterX - (int) ($iw / 2), $iconCenterY + (int) ($ih / 2), $amber, $fontBold, 'i');

                $pillFontSize = max(10, (int) (14 * $scaleX));
                $pTextY = $iconCenterY + (int) ($pillFontSize / 2);
                imagettftext($im, $pillFontSize, 0, $iconCenterX + (int) (26 * $scaleX), $pTextY, $amber, $fontBold, 'AI Video Disclaimer');
            } else {
                imagestring($im, 3, $iconCenterX - 3, $iconCenterY - 6, 'i', $amber);
                imagestring($im, 3, $iconCenterX + 20, $iconCenterY - 6, 'AI Video Disclaimer', $amber);
            }
        }

        // 2. Bottom: Floating Watch in Motion Card
        $cardMarginH = (int) (40 * $scaleX);
        $cardX1 = $cardMarginH;
        $cardX2 = $canvasW - $cardMarginH;
        $cardW = $cardX2 - $cardX1;

        $cardH = (int) (260 * $scaleY);
        $cardMarginB = (int) (60 * $scaleY);
        $cardY2 = $canvasH - $cardMarginB;
        $cardY1 = $cardY2 - $cardH;
        $cardRad = (int) (30 * $scaleX);

        // Draw soft drop shadow behind card
        $this->drawRoundedRect($im, $cardX1 + 4, $cardY1 + 4, $cardX2 + 4, $cardY2 + 4, $cardRad, $shadow);
        // Draw card frosted dark background
        $this->drawRoundedRect($im, $cardX1, $cardY1, $cardX2, $cardY2, $cardRad, $cardBg);
        // Draw card subtle border
        $this->drawRoundedRectBorder($im, $cardX1, $cardY1, $cardX2, $cardY2, $cardRad, $cardBorder);

        // Dimensions inside card
        $innerPadX = (int) (34 * $scaleX);
        $leftX = $cardX1 + $innerPadX;

        // Button dimensions (right column)
        $btnW = (int) (215 * $scaleX);
        $btnH = (int) (76 * $scaleY);
        $btnRad = (int) (20 * $scaleX);
        $btnX2 = $cardX2 - $innerPadX;
        $btnX1 = $btnX2 - $btnW;
        $btnY1 = $cardY1 + (int) (($cardH - $btnH) / 2);
        $btnY2 = $btnY1 + $btnH;

        // Available width for text on left side
        $availTextW = $btnX1 - $leftX - (int) (20 * $scaleX);

        // Resolve Product Data
        $product = $video->product;
        $tagText = strtoupper($templateConfig['tag'] ?? 'FEATURED IN REEL');
        $titleText = $video->title ?: ($product?->name ?? 'Dhansree Exclusive');

        $priceStr = $product?->formatted_price ?: ($product && $product->min_price_rupees ? '₹' . number_format($product->min_price_rupees, 0) : '₹1,566');
        $mrpStr = $product?->formatted_mrp ?: ($product && $product->mrp_rupees && $product->mrp_rupees > ($product->min_price_rupees ?? 0) ? '₹' . number_format($product->mrp_rupees, 0) : null);
        $discountStr = $product?->discount_percentage ? ($product->discount_percentage . '% OFF') : ($mrpStr && $product && $product->min_price_rupees ? round((($product->mrp_rupees - $product->min_price_rupees) / $product->mrp_rupees) * 100) . '% OFF' : null);

        // Pinned variant specific price override if applicable
        if ($product && !empty($video->pinned_variant_ids)) {
            $pinnedVar = $product->variants->whereIn('id', $video->pinned_variant_ids)->first();
            if ($pinnedVar) {
                if (!empty($pinnedVar->formatted_price)) $priceStr = $pinnedVar->formatted_price;
                if (!empty($pinnedVar->formatted_mrp)) $mrpStr = $pinnedVar->formatted_mrp;
                if (!empty($pinnedVar->discount_percentage)) $discountStr = $pinnedVar->discount_percentage . '% OFF';
            }
        }

        if ($fontBold) {
            // A. Tag: FEATURED IN REEL
            $tagFontSize = max(10, (int) (13 * $scaleX));
            $tagY = $cardY1 + (int) (44 * $scaleY);
            imagettftext($im, $tagFontSize, 0, $leftX, $tagY, $amber, $fontBold, $tagText);

            // B. Wrapped Product Title (1 or 2 lines)
            $titleFontSize = max(14, (int) (20 * $scaleX));
            $words = explode(' ', $titleText);
            $lines = [];
            $curLine = '';
            foreach ($words as $word) {
                $test = $curLine === '' ? $word : $curLine . ' ' . $word;
                $box = imagettfbbox($titleFontSize, 0, $fontBold, $test);
                $tw = abs($box[2] - $box[0]);
                if ($tw > $availTextW && $curLine !== '') {
                    $lines[] = $curLine;
                    $curLine = $word;
                } else {
                    $curLine = $test;
                }
            }
            if ($curLine !== '') {
                $lines[] = $curLine;
            }
            $titleLines = array_slice($lines, 0, 2);

            $lineHeight = (int) (32 * $scaleY);
            $titleStartY = $tagY + (int) (34 * $scaleY);
            foreach ($titleLines as $idx => $line) {
                $y = $titleStartY + ($idx * $lineHeight);
                // Subtle drop shadow for title legibility
                imagettftext($im, $titleFontSize, 0, $leftX + 1, $y + 1, $shadow, $fontBold, $line);
                imagettftext($im, $titleFontSize, 0, $leftX, $y, $white, $fontBold, $line);
            }

            // C. Pricing Row
            $priceY = $cardY2 - (int) (30 * $scaleY);
            $priceFontSize = max(16, (int) (24 * $scaleX));
            $curPriceX = $leftX;

            imagettftext($im, $priceFontSize, 0, $curPriceX + 1, $priceY + 1, $shadow, $fontBold, $priceStr);
            imagettftext($im, $priceFontSize, 0, $curPriceX, $priceY, $amber, $fontBold, $priceStr);

            $pBox = imagettfbbox($priceFontSize, 0, $fontBold, $priceStr);
            $curPriceX += abs($pBox[2] - $pBox[0]) + (int) (18 * $scaleX);

            // Slashed MRP
            if ($mrpStr && $mrpStr !== $priceStr) {
                $mrpFontSize = max(12, (int) (17 * $scaleX));
                imagettftext($im, $mrpFontSize, 0, $curPriceX, $priceY, $mrpGray, $fontRegular, $mrpStr);

                $mBox = imagettfbbox($mrpFontSize, 0, $fontRegular, $mrpStr);
                $mW = abs($mBox[2] - $mBox[0]);
                $strikeY = $priceY - (int) (6 * $scaleY);
                imageline($im, $curPriceX - 2, $strikeY, $curPriceX + $mW + 2, $strikeY, $mrpGray);
                imageline($im, $curPriceX - 2, $strikeY + 1, $curPriceX + $mW + 2, $strikeY + 1, $mrpGray);

                $curPriceX += $mW + (int) (18 * $scaleX);
            }

            // Discount Badge
            if ($discountStr) {
                $discFontSize = max(11, (int) (16 * $scaleX));
                imagettftext($im, $discFontSize, 0, $curPriceX, $priceY, $green, $fontBold, $discountStr);
            }

            // D. Buy Now Button (Right Column)
            $this->drawRoundedRect($im, $btnX1 + 3, $btnY1 + 3, $btnX2 + 3, $btnY2 + 3, $btnRad, $shadow);
            $this->drawRoundedRect($im, $btnX1, $btnY1, $btnX2, $btnY2, $btnRad, $orangeBtn);

            $btnText = 'Buy Now →';
            $btnFontSize = max(12, (int) (18 * $scaleX));
            $bBox = imagettfbbox($btnFontSize, 0, $fontBold, $btnText);
            $bW = abs($bBox[2] - $bBox[0]);
            $bH = abs($bBox[1] - $bBox[7]);
            $btnTextX = $btnX1 + (int) (($btnW - $bW) / 2);
            $btnTextY = $btnY1 + (int) (($btnH + $bH) / 2);

            imagettftext($im, $btnFontSize, 0, $btnTextX, $btnTextY, $darkSlate, $fontBold, $btnText);
        } else {
            // GD system font fallback
            imagestring($im, 4, $leftX, $cardY1 + 16, $tagText, $amber);
            imagestring($im, 5, $leftX, $cardY1 + 40, mb_strimwidth($titleText, 0, 40, '...'), $white);
            imagestring($im, 5, $leftX, $cardY2 - 34, "{$priceStr} " . ($mrpStr ? "({$mrpStr})" : ''), $amber);
            $this->drawRoundedRect($im, $btnX1, $btnY1, $btnX2, $btnY2, $btnRad, $orangeBtn);
            imagestring($im, 4, $btnX1 + 40, $btnY1 + 25, 'Buy Now', $darkSlate);
        }

        imagepng($im, $destImagePath, 6);
        imagedestroy($im);
    }

    /**
     * Draw rounded rectangle filled in GD.
     */
    protected function drawRoundedRect($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        $radius = max(1, min($radius, (int) (($x2 - $x1) / 2), (int) (($y2 - $y1) / 2)));
        imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    }

    /**
     * Draw rounded rectangle outline border in GD.
     */
    protected function drawRoundedRectBorder($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $borderColor): void
    {
        $radius = max(1, min($radius, (int) (($x2 - $x1) / 2), (int) (($y2 - $y1) / 2)));
        imagearc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $borderColor);
        imagearc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $borderColor);
        imagearc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $borderColor);
        imagearc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $borderColor);
        imageline($im, $x1 + $radius, $y1, $x2 - $radius, $y1, $borderColor);
        imageline($im, $x2, $y1 + $radius, $x2, $y2 - $radius, $borderColor);
        imageline($im, $x1 + $radius, $y2, $x2 - $radius, $y2, $borderColor);
        imageline($im, $x1, $y1 + $radius, $x1, $y2 - $radius, $borderColor);
    }

    /**
     * Render Watch in Motion Reel MP4 (vertical 9:16 with floating product details card and Buy Now CTA).
     * Matches the frontend "Watch in Motion" lightbox popup exactly for Instagram & Facebook Reels.
     */
    public function renderWatchInMotionReel(
        ProductVideo $video,
        string $sourceVideoPath,
        string $outputVideoPath,
        int $targetW = 1080,
        int $targetH = 1920
    ): string {
        $tempDir = dirname($outputVideoPath);
        File::ensureDirectoryExists($tempDir);

        $overlayPath = $tempDir . DIRECTORY_SEPARATOR . 'wim_overlay_' . Str::uuid() . '.png';
        $this->createWatchInMotionOverlayGraphic($video, $overlayPath, $targetW, $targetH);

        $scaleFilter = "[0:v]scale={$targetW}:{$targetH}:force_original_aspect_ratio=decrease,pad={$targetW}:{$targetH}:(ow-iw)/2:(oh-ih)/2,format=yuv420p[base]";
        $overlayFilter = "[base][1:v]overlay=0:0:shortest=1[v_out]";

        $cmd = [
            $this->ffmpegPath, '-y',
            '-i', $sourceVideoPath,
            '-loop', '1', '-i', $overlayPath,
            '-filter_complex', "{$scaleFilter};{$overlayFilter}",
            '-map', '[v_out]',
            '-map', '0:a?',
            '-c:v', 'libx264',
            '-preset', 'fast',
            '-crf', '20',
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            '-shortest',
            $outputVideoPath,
        ];

        $this->runCommand($cmd, 120);

        if (file_exists($overlayPath)) {
            @unlink($overlayPath);
        }

        if (!file_exists($outputVideoPath) || filesize($outputVideoPath) === 0) {
            throw new \RuntimeException('Failed to render Watch in Motion Reel video: output file is empty or missing.');
        }

        return $outputVideoPath;
    }

    /**
     * Render the slideshow transitions using FFmpeg xfade.
     */
    protected function renderTransitions(
        array $preparedSlides,
        string $outputVideoFile,
        int $width,
        int $height,
        int $totalDuration
    ): void {
        $count = count($preparedSlides);

        if ($count === 1) {
            // Single slide: smooth motion loop
            $cmd = [
                $this->ffmpegPath, '-y',
                '-loop', '1', '-t', (string) $totalDuration,
                '-i', $preparedSlides[0],
                '-vf', "scale={$width}:{$height},format=yuv420p",
                '-c:v', 'libx264',
                '-preset', 'fast',
                '-pix_fmt', 'yuv420p',
                '-r', '30',
                $outputVideoFile,
            ];
            $this->runCommand($cmd, 60);
            return;
        }

        // Multiple slides: calculate duration and transition timing
        $transitionDuration = 0.6; // 600ms tasteful crossfade
        // Effective duration of each slide so total ends up approximately $totalDuration
        $slideDuration = ($totalDuration + (($count - 1) * $transitionDuration)) / $count;
        $slideDuration = max($transitionDuration + 1.0, $slideDuration);

        $inputs = [];
        foreach ($preparedSlides as $slide) {
            $inputs[] = '-loop';
            $inputs[] = '1';
            $inputs[] = '-t';
            $inputs[] = sprintf('%.2f', $slideDuration);
            $inputs[] = '-i';
            $inputs[] = $slide;
        }

        // Build xfade filter chain: [0:v][1:v]xfade=...[v1]; [v1][2:v]xfade=...[v2]
        $filterParts = [];
        $lastOutput = '0:v';

        for ($i = 1; $i < $count; $i++) {
            $offset = ($i * $slideDuration) - ($i * $transitionDuration);
            $nextOutput = ($i === $count - 1) ? 'vfinal' : "v{$i}";
            $filterParts[] = "[{$lastOutput}][{$i}:v]xfade=transition=fade:duration={$transitionDuration}:offset=" . sprintf('%.2f', $offset) . "[{$nextOutput}]";
            $lastOutput = $nextOutput;
        }

        $filterParts[] = "[vfinal]scale={$width}:{$height},format=yuv420p[vout]";
        $filterComplex = implode(';', $filterParts);

        $cmd = array_merge(
            [$this->ffmpegPath, '-y'],
            $inputs,
            [
                '-filter_complex', $filterComplex,
                '-map', '[vout]',
                '-c:v', 'libx264',
                '-preset', 'fast',
                '-pix_fmt', 'yuv420p',
                '-r', '30',
                $outputVideoFile,
            ]
        );

        $this->runCommand($cmd, 120);
    }

    /**
     * Burn overlay graphic, optional closing CTA card, and audio track.
     */
    protected function applyOverlaysAndAudio(
        string $baseVideoFile,
        string $overlayFile,
        string $outputVideoFile,
        ProductVideo $video,
        int $duration,
        array $settings
    ): void {
        $audioTrack = $settings['audio_track'] ?? 'festive_royal';
        $audioConfig = config("video_studio.audio_tracks.{$audioTrack}");
        $audioFile = ($audioConfig && file_exists($audioConfig['file'])) ? $audioConfig['file'] : null;
        $hasAudio = !empty($settings['enable_music']) && $audioFile !== null;
        $hasCta = ($settings['final_cta_screen'] ?? false) === true;

        // If CTA enabled, create closing card that fades in over the last 2.2 seconds
        $ctaFile = null;
        if ($hasCta) {
            $ctaFile = dirname($outputVideoFile) . DIRECTORY_SEPARATOR . 'closing_cta.png';
            $this->createClosingCtaGraphic($video, $ctaFile, $video->width ?: 1080, $video->height ?: 1920);
        }

        $ctaStartTime = max(1.0, $duration - 2.5);

        // Build filter complex
        // [0:v] base video, [1:v] overlay graphic, [2:v] CTA closing card (if present)
        $inputs = [
            $this->ffmpegPath, '-y',
            '-i', $baseVideoFile,
            '-loop', '1', '-i', $overlayFile,
        ];

        if ($hasCta && file_exists($ctaFile)) {
            $inputs[] = '-loop';
            $inputs[] = '1';
            $inputs[] = '-i';
            $inputs[] = $ctaFile;
        }

        if ($hasAudio) {
            $inputs[] = '-stream_loop';
            $inputs[] = '-1';
            $inputs[] = '-i';
            $inputs[] = $audioFile;
        }

        $filters = [];
        // Overlay 1: main overlay card visible until CTA begins
        if ($hasCta && file_exists($ctaFile)) {
            $filters[] = "[0:v][1:v]overlay=0:0:enable='lte(t,{$ctaStartTime})'[v_mid]";
            // Overlay 2: CTA fades in at $ctaStartTime
            $filters[] = "[v_mid][2:v]overlay=0:0:enable='gte(t,{$ctaStartTime})'[v_out]";
            $videoMap = '[v_out]';
        } else {
            $filters[] = "[0:v][1:v]overlay=0:0[v_out]";
            $videoMap = '[v_out]';
        }

        $cmd = array_merge($inputs, [
            '-filter_complex', implode(';', $filters),
            '-map', $videoMap,
        ]);

        if ($hasAudio) {
            $fadeStart = max(1.0, $duration - 1.5);
            $cmd = array_merge($cmd, [
                '-af', "afade=t=in:ss=0:d=1,afade=t=out:st={$fadeStart}:d=1.5",
                '-c:a', 'aac',
                '-b:a', '192k',
            ]);
        } else {
            $cmd = array_merge($cmd, ['-an']);
        }

        $cmd = array_merge($cmd, [
            '-t', (string) $duration,
            '-c:v', 'libx264',
            '-crf', '18',
            '-preset', 'medium',
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            $outputVideoFile,
        ]);

        $this->runCommand($cmd, 120);
    }

    /**
     * Create final call-to-action closing frame with Dhansree branding.
     */
    protected function createClosingCtaGraphic(
        ProductVideo $video,
        string $destImagePath,
        int $canvasW,
        int $canvasH
    ): void {
        $im = imagecreatetruecolor($canvasW, $canvasH);
        imagealphablending($im, false);
        imagesavealpha($im, true);

        // Rich dark maroon background with subtle transparency
        $bg = imagecolorallocatealpha($im, 20, 4, 4, 15);
        imagefilledrectangle($im, 0, 0, $canvasW, $canvasH, $bg);
        imagealphablending($im, true);

        $fontBold = $this->resolveBoldFont();
        $fontRegular = $this->resolveRegularFont();

        $gold = imagecolorallocate($im, 176, 136, 88);
        $goldBright = imagecolorallocate($im, 212, 175, 55);
        $white = imagecolorallocate($im, 255, 255, 255);
        $maroon = imagecolorallocate($im, 88, 0, 0);
        $creamBg = imagecolorallocatealpha($im, 251, 250, 248, 8); // 94% opaque luxury cream plate
        $shadow = imagecolorallocatealpha($im, 0, 0, 0, 80);

        // Center Dhansree Logo inside Luxury Contrast Plate
        $logoPath = config('video_studio.brand.logo_path');
        if (file_exists($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                $lw = imagesx($logo);
                $lh = imagesy($logo);
                $targetLogoW = (int) ($canvasW * 0.45);
                $targetLogoH = (int) ($lh * ($targetLogoW / $lw));

                $padX = 36;
                $padY = 18;
                $cardW = $targetLogoW + ($padX * 2);
                $cardH = $targetLogoH + ($padY * 2);
                $cardX = (int) (($canvasW - $cardW) / 2);
                $cardY = (int) ($canvasH * 0.33);

                // Draw luxury plate for logo
                imagefilledrectangle($im, $cardX + 4, $cardY + 4, $cardX + $cardW + 4, $cardY + $cardH + 4, $shadow);
                imagefilledrectangle($im, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $creamBg);
                imagerectangle($im, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $gold);

                imagecopyresampled($im, $logo, $cardX + $padX, $cardY + $padY, 0, 0, $targetLogoW, $targetLogoH, $lw, $lh);
                imagedestroy($logo);
            }
        }

        // Tagline
        $tagline = 'PREMIUM ETHNIC & FASHION WEAR';
        $tagY = (int) ($canvasH * 0.48);
        $this->drawBadge($im, $tagline, (int) ($canvasW / 2), $tagY, $goldBright, imagecolorallocatealpha($im, 88, 0, 0, 40), $fontBold, $gold);

        // Shop Now Button Card
        $btnW = (int) ($canvasW * 0.68);
        $btnH = (int) ($canvasH * 0.068);
        $btnX = (int) (($canvasW - $btnW) / 2);
        $btnY = (int) ($canvasH * 0.56);

        imagefilledrectangle($im, $btnX + 4, $btnY + 4, $btnX + $btnW + 4, $btnY + $btnH + 4, $shadow);
        imagefilledrectangle($im, $btnX, $btnY, $btnX + $btnW, $btnY + $btnH, $maroon);
        imagerectangle($im, $btnX, $btnY, $btnX + $btnW, $btnY + $btnH, $goldBright);

        $btnText = 'SHOP NOW AT DHANSREE.COM';
        if ($fontBold) {
            $fSize = (int) ($canvasW * 0.024);
            $box = imagettfbbox($fSize, 0, $fontBold, $btnText);
            $tW = abs($box[2] - $box[0]);
            $tX = (int) (($canvasW - $tW) / 2);
            $tY = $btnY + (int) ($btnH * 0.62);
            imagettftext($im, $fSize, 0, $tX, $tY, $white, $fontBold, $btnText);
        } else {
            $tX = (int) (($canvasW - (strlen($btnText) * 9)) / 2);
            imagestring($im, 5, $tX, $btnY + 18, $btnText, $white);
        }

        // Website footer
        $footerText = 'Free Shipping Across India  •  Cash on Delivery Available';
        $footFont = $fontRegular ?: $fontBold;
        if ($footFont) {
            $footSize = (int) ($canvasW * 0.018);
            $fbox = imagettfbbox($footSize, 0, $footFont, $footerText);
            $fW = abs($fbox[2] - $fbox[0]);
            $fX = (int) (($canvasW - $fW) / 2);
            $fY = (int) ($canvasH * 0.66);
            imagettftext($im, $footSize, 0, $fX, $fY, $gold, $footFont, $footerText);
        }

        imagepng($im, $destImagePath, 6);
        imagedestroy($im);
    }

    /**
     * Generate poster thumbnail image from the generated video.
     */
    protected function generateThumbnail(string $videoPath, string $thumbPath): void
    {
        $cmd = [
            $this->ffmpegPath, '-y',
            '-ss', '00:00:01',
            '-i', $videoPath,
            '-vframes', '1',
            '-q:v', '2',
            $thumbPath,
        ];

        try {
            $this->runCommand($cmd, 15);
        } catch (\Throwable $e) {
            Log::warning("Thumbnail generation fallback: {$e->getMessage()}");
        }
    }

    /**
     * Safely execute command using Symfony Process.
     */
    protected function runCommand(array $cmd, int $timeout = 60): void
    {
        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput() ?: $process->getOutput();
            Log::error("FFmpeg execution failure: {$error}");
            throw new \RuntimeException("FFmpeg execution failed: " . mb_substr($error, -500));
        }
    }

    /**
     * Helper to load GD image from file (JPG, PNG, WEBP).
     */
    protected function loadImage(string $path): ?\GdImage
    {
        if (!file_exists($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => null,
        };
    }

    /**
     * Draw stylish badge with rounded pill background.
     */
    protected function drawBadge(
        \GdImage $im,
        string $text,
        int $centerX,
        int $centerY,
        int $textColor,
        int $bgColor,
        ?string $font,
        ?int $borderColor = null
    ): void {
        $fontSize = (int) (imagesx($im) * 0.018);
        $padX = 22;
        $padY = 10;

        if ($font) {
            $bbox = imagettfbbox($fontSize, 0, $font, $text);
            $w = abs($bbox[2] - $bbox[0]);
            $h = abs($bbox[7] - $bbox[1]);
        } else {
            $w = strlen($text) * 9;
            $h = 16;
        }

        $rx = (int) ($centerX - ($w / 2) - $padX);
        $ry = (int) ($centerY - ($h / 2) - $padY);
        $rw = (int) ($w + ($padX * 2));
        $rh = (int) ($h + ($padY * 2));

        imagefilledrectangle($im, $rx, $ry, $rx + $rw, $ry + $rh, $bgColor);
        imagerectangle($im, $rx, $ry, $rx + $rw, $ry + $rh, $borderColor ?? $textColor);

        if ($font) {
            imagettftext($im, $fontSize, 0, (int) ($centerX - ($w / 2)), (int) ($centerY + ($h / 2.5)), $textColor, $font, $text);
        } else {
            imagestring($im, 5, (int) ($centerX - ($w / 2)), (int) ($centerY - 8), $text, $textColor);
        }
    }

    /**
     * Draw modern card rectangle with border.
     */
    protected function drawRoundedCard(
        \GdImage $im,
        int $x,
        int $y,
        int $w,
        int $h,
        int $radius,
        int $bgColor,
        int $borderColor
    ): void {
        imagefilledrectangle($im, $x, $y, $x + $w, $y + $h, $bgColor);
        imagerectangle($im, $x, $y, $x + $w, $y + $h, $borderColor);
    }

    /**
     * Convert #RRGGBB hex string to RGB array.
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
