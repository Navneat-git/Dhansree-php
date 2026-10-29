<?php

namespace App\Services\VideoStudio;

use App\Models\Product;
use App\Models\ProductVideo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
     * Create transparent PNG overlay with Dhansree logo, template badge, and product card.
     */
    protected function createOverlayGraphic(
        ProductVideo $video,
        string $destImagePath,
        int $canvasW,
        int $canvasH,
        array $templateConfig,
        array $settings
    ): void {
        $im = imagecreatetruecolor($canvasW, $canvasH);
        imagealphablending($im, false);
        imagesavealpha($im, true);

        // Transparent canvas
        $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefilledrectangle($im, 0, 0, $canvasW, $canvasH, $trans);
        imagealphablending($im, true);

        $fontBold = config('video_studio.brand.font_bold');
        $fontRegular = config('video_studio.brand.font_regular');

        // Brand colors
        $maroon = imagecolorallocate($im, 88, 0, 0);
        $gold = imagecolorallocate($im, 176, 136, 88);
        $goldBright = imagecolorallocate($im, 212, 175, 55);
        $white = imagecolorallocate($im, 255, 255, 255);
        $cardBg = imagecolorallocatealpha($im, 18, 8, 8, 35); // Translucent glassmorphism
        $badgeBg = imagecolorallocatealpha($im, 88, 0, 0, 20);

        // Top safe area: Dhansree Brand Header
        $logoPath = config('video_studio.brand.logo_path');
        if (file_exists($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                $lw = imagesx($logo);
                $lh = imagesy($logo);
                $targetLogoH = (int) ($canvasH * 0.038); // proportional logo height
                $targetLogoW = (int) ($lw * ($targetLogoH / $lh));
                $logoX = (int) (($canvasW - $targetLogoW) / 2);
                $logoY = (int) ($canvasH * 0.045); // top safe area

                imagecopyresampled($im, $logo, $logoX, $logoY, 0, 0, $targetLogoW, $targetLogoH, $lw, $lh);
                imagedestroy($logo);
            }
        }

        // Template Tag Badge (e.g. "NEW ARRIVAL", "FESTIVE SPECIAL", etc.)
        $tagText = strtoupper($templateConfig['tag'] ?? 'DHANSREE TRENDS');
        $tagY = (int) ($canvasH * 0.098);
        $this->drawBadge($im, $tagText, (int) ($canvasW / 2), $tagY, $gold, $badgeBg, $fontBold);

        // Bottom Product Title & Price Card (if text overlays enabled)
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
            $cardY = (int) ($canvasH * 0.82); // Above safe footer zone for Reels

            // Draw rounded card
            $this->drawRoundedCard($im, $cardX, $cardY, $cardW, $cardH, 20, $cardBg, $gold);

            // Title text
            $displayTitle = mb_strimwidth($title, 0, 48, '...');
            if ($fontBold) {
                $titleFontSize = (int) ($canvasW * 0.026);
                $bbox = imagettfbbox($titleFontSize, 0, $fontBold, $displayTitle);
                $tWidth = abs($bbox[2] - $bbox[0]);
                $tX = (int) (($canvasW - $tWidth) / 2);
                $tY = $cardY + (int) ($cardH * 0.42);
                imagettftext($im, $titleFontSize, 0, $tX, $tY, $white, $fontBold, $displayTitle);
            } else {
                $tX = (int) (($canvasW - (strlen($displayTitle) * 9)) / 2);
                imagestring($im, 5, $tX, $cardY + 16, $displayTitle, $white);
            }

            // Price badge & Dhansree Trends note
            $subline = $priceText ? "{$priceText}  •  Dhansree Trends" : "Exclusively at dhansree.com";
            if ($fontBold) {
                $subFontSize = (int) ($canvasW * 0.022);
                $sbox = imagettfbbox($subFontSize, 0, $fontBold, $subline);
                $sWidth = abs($sbox[2] - $sbox[0]);
                $sX = (int) (($canvasW - $sWidth) / 2);
                $sY = $cardY + (int) ($cardH * 0.80);
                imagettftext($im, $subFontSize, 0, $sX, $sY, $goldBright, $fontBold, $subline);
            } else {
                $sX = (int) (($canvasW - (strlen($subline) * 8)) / 2);
                imagestring($im, 4, $sX, $cardY + 54, $subline, $goldBright);
            }
        }

        imagepng($im, $destImagePath, 6);
        imagedestroy($im);
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

        $hasCta = $settings['call_to_action'] ?? true;

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
            '-preset', 'fast',
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

        $fontBold = config('video_studio.brand.font_bold');
        $fontRegular = config('video_studio.brand.font_regular');

        $gold = imagecolorallocate($im, 212, 175, 55);
        $white = imagecolorallocate($im, 255, 255, 255);
        $maroon = imagecolorallocate($im, 88, 0, 0);

        // Center Dhansree Logo
        $logoPath = config('video_studio.brand.logo_path');
        if (file_exists($logoPath)) {
            $logo = @imagecreatefrompng($logoPath);
            if ($logo) {
                $lw = imagesx($logo);
                $lh = imagesy($logo);
                $targetLogoW = (int) ($canvasW * 0.45);
                $targetLogoH = (int) ($lh * ($targetLogoW / $lw));
                $logoX = (int) (($canvasW - $targetLogoW) / 2);
                $logoY = (int) ($canvasH * 0.35);

                imagecopyresampled($im, $logo, $logoX, $logoY, 0, 0, $targetLogoW, $targetLogoH, $lw, $lh);
                imagedestroy($logo);
            }
        }

        // Tagline
        $tagline = 'PREMIUM ETHNIC & FASHION WEAR';
        $tagY = (int) ($canvasH * 0.46);
        $this->drawBadge($im, $tagline, (int) ($canvasW / 2), $tagY, $gold, imagecolorallocatealpha($im, 88, 0, 0, 40), $fontBold);

        // Shop Now Button Card
        $btnW = (int) ($canvasW * 0.65);
        $btnH = (int) ($canvasH * 0.065);
        $btnX = (int) (($canvasW - $btnW) / 2);
        $btnY = (int) ($canvasH * 0.55);

        $this->drawRoundedCard($im, $btnX, $btnY, $btnW, $btnH, 15, $maroon, $gold);

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
        if ($fontRegular) {
            $footSize = (int) ($canvasW * 0.018);
            $fbox = imagettfbbox($footSize, 0, $fontRegular, $footerText);
            $fW = abs($fbox[2] - $fbox[0]);
            $fX = (int) (($canvasW - $fW) / 2);
            $fY = (int) ($canvasH * 0.66);
            imagettftext($im, $footSize, 0, $fX, $fY, $gold, $fontRegular, $footerText);
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
        ?string $font
    ): void {
        $fontSize = (int) (imagesx($im) * 0.017);
        $padX = 18;
        $padY = 8;

        if ($font) {
            $bbox = imagettfbbox($fontSize, 0, $font, $text);
            $w = abs($bbox[2] - $bbox[0]);
            $h = abs($bbox[7] - $bbox[1]);
        } else {
            $w = strlen($text) * 8;
            $h = 12;
        }

        $rx = (int) ($centerX - ($w / 2) - $padX);
        $ry = (int) ($centerY - ($h / 2) - $padY);
        $rw = (int) ($w + ($padX * 2));
        $rh = (int) ($h + ($padY * 2));

        imagefilledrectangle($im, $rx, $ry, $rx + $rw, $ry + $rh, $bgColor);
        imagerectangle($im, $rx, $ry, $rx + $rw, $ry + $rh, $textColor);

        if ($font) {
            imagettftext($im, $fontSize, 0, (int) ($centerX - ($w / 2)), (int) ($centerY + ($h / 3)), $textColor, $font, $text);
        } else {
            imagestring($im, 4, (int) ($centerX - ($w / 2)), (int) ($centerY - 6), $text, $textColor);
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
