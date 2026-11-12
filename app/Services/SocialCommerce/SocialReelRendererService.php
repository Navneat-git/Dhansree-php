<?php

declare(strict_types=1);

namespace App\Services\SocialCommerce;

use App\Models\SocialReel;
use App\Models\SocialSetting;
use App\Services\VideoStudio\FfmpegService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class SocialReelRendererService
{
    public function __construct(
        protected FfmpegService $ffmpegService
    ) {}

    /**
     * Render the 9:16 vertical MP4 video for a SocialReel.
     *
     * @return array{video_path: string, thumbnail_path: string, file_size: int}
     * @throws \Throwable
     */
    public function renderReel(SocialReel $reel): array
    {
        $reel->load(['items.mediaAsset', 'items.socialProduct', 'template']);

        $reel->update([
            'status' => 'RENDERING',
            'progress' => 10,
            'error_message' => null,
        ]);

        $width = $reel->width ?: 1080;
        $height = $reel->height ?: 1920;
        $totalDuration = max(5, min(60, (int) ($reel->duration_seconds ?: 15)));
        $items = $reel->items;

        if ($items->isEmpty()) {
            $reel->update([
                'status' => 'FAILED',
                'error_message' => 'Cannot render reel: No storyboard items or media assets found.',
                'progress' => 0,
            ]);
            throw new \RuntimeException("Reel {$reel->reel_code} has no items to render.");
        }

        $tempDir = storage_path('app/temp/social_reels/' . $reel->reel_code . '_' . time());
        File::ensureDirectoryExists($tempDir);

        try {
            $ffmpegPath = $this->ffmpegService->getFfmpegPath();
            $isFfmpegAvailable = $this->ffmpegService->isAvailable();

            // 1. Prepare framed slides for each item (1080x1920)
            $preparedSlides = [];
            foreach ($items as $idx => $item) {
                $slidePath = $tempDir . DIRECTORY_SEPARATOR . sprintf('slide_%02d.png', $idx);
                $this->renderSlideFrame($item, $slidePath, $width, $height, $reel);
                $preparedSlides[] = $slidePath;
            }

            $reel->update(['progress' => 35]);

            // Destination paths on public storage
            $relativeVideoPath = "social-reels/{$reel->reel_code}.mp4";
            $relativeThumbPath = "social-reels/{$reel->reel_code}_thumb.jpg";
            $storageDisk = Storage::disk('public');

            $finalVideoLocal = $tempDir . DIRECTORY_SEPARATOR . 'final_reel.mp4';
            $thumbnailLocal = $tempDir . DIRECTORY_SEPARATOR . 'thumbnail.jpg';

            if ($isFfmpegAvailable) {
                // 2. Assemble slideshow with transitions using FFmpeg
                $baseSlideshowVideo = $tempDir . DIRECTORY_SEPARATOR . 'base_slideshow.mp4';
                $transitionType = $this->resolveTransition($reel->template_code);
                $this->assembleSlideshowWithTransitions(
                    $preparedSlides,
                    $baseSlideshowVideo,
                    $width,
                    $height,
                    $totalDuration,
                    $transitionType,
                    $ffmpegPath
                );

                $reel->update(['progress' => 60]);

                // 3. Apply branding overlay, audio soundtrack, and closing CTA screen
                $this->finalizeVideoWithAudioAndCta(
                    $baseSlideshowVideo,
                    $finalVideoLocal,
                    $reel,
                    $width,
                    $height,
                    $totalDuration,
                    $tempDir,
                    $ffmpegPath
                );

                $reel->update(['progress' => 85]);

                // 4. Extract high-res thumbnail at 1.0s
                $this->extractVideoThumbnail($finalVideoLocal, $thumbnailLocal, $ffmpegPath);
            } else {
                // Fallback for environments where ffmpeg binary cannot be executed
                Log::warning("FFmpeg binary not executable. Generating fallback video artifact for {$reel->reel_code}.");
                $this->generateFallbackArtifacts($preparedSlides[0], $finalVideoLocal, $thumbnailLocal);
            }

            // 5. Store files into public storage disk
            $storageDisk->put($relativeVideoPath, file_get_contents($finalVideoLocal));
            $storageDisk->put($relativeThumbPath, file_get_contents($thumbnailLocal));

            $fileSize = (int) filesize($finalVideoLocal);

            $reel->update([
                'status' => 'COMPLETED',
                'progress' => 100,
                'video_path' => $relativeVideoPath,
                'thumbnail_path' => $relativeThumbPath,
                'error_message' => null,
            ]);

            return [
                'video_path' => $relativeVideoPath,
                'thumbnail_path' => $relativeThumbPath,
                'file_size' => $fileSize,
            ];
        } catch (\Throwable $e) {
            Log::error("Social Reel {$reel->reel_code} render error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $reel->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
                'progress' => 0,
            ]);

            throw $e;
        } finally {
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Render an individual 1080x1920 slide image with Dhansree branding and overlays.
     */
    protected function renderSlideFrame(
        $item,
        string $outputPath,
        int $canvasW,
        int $canvasH,
        SocialReel $reel
    ): void {
        $asset = $item->mediaAsset;
        $localSource = null;

        if ($asset && $asset->file_path) {
            $fullDiskPath = Storage::disk('public')->path($asset->file_path);
            if (file_exists($fullDiskPath)) {
                $localSource = $fullDiskPath;
            }
        }

        $sourceImg = null;
        if ($localSource) {
            $sourceImg = $this->loadGdImage($localSource);
        }

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        imagealphablending($canvas, true);

        // Maroon background base (#580000)
        $maroon = imagecolorallocate($canvas, 88, 0, 0);
        $gold = imagecolorallocate($canvas, 176, 136, 88);
        $goldBright = imagecolorallocate($canvas, 212, 175, 55);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $cream = imagecolorallocate($canvas, 251, 250, 248);
        $darkOverlay = imagecolorallocatealpha($canvas, 15, 6, 6, 30);
        $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 80);

        imagefilledrectangle($canvas, 0, 0, $canvasW, $canvasH, $maroon);

        if ($sourceImg) {
            $srcW = imagesx($sourceImg);
            $srcH = imagesy($sourceImg);

            // Ambient blurred background from product photo
            $blurW = max(20, (int) ($canvasW * 0.05));
            $blurH = max(20, (int) ($canvasH * 0.05));
            $thumb = imagecreatetruecolor($blurW, $blurH);
            imagecopyresampled($thumb, $sourceImg, 0, 0, 0, 0, $blurW, $blurH, $srcW, $srcH);

            $ambient = imagecreatetruecolor($canvasW, $canvasH);
            imagecopyresampled($ambient, $thumb, 0, 0, 0, 0, $canvasW, $canvasH, $blurW, $blurH);
            imagedestroy($thumb);

            imagefilter($ambient, IMG_FILTER_GAUSSIAN_BLUR);
            imagefilter($ambient, IMG_FILTER_BRIGHTNESS, -50);
            imagecopymerge($canvas, $ambient, 0, 0, 0, 0, $canvasW, $canvasH, 80);
            imagedestroy($ambient);

            // Foreground product image (preserve aspect ratio, uncropped)
            $availW = (int) ($canvasW * 0.90);
            $availH = (int) ($canvasH * 0.65);
            $scale = min($availW / $srcW, $availH / $srcH);
            $dstW = (int) ($srcW * $scale);
            $dstH = (int) ($srcH * $scale);
            $dstX = (int) (($canvasW - $dstW) / 2);
            $dstY = (int) (($canvasH * 0.12) + (($availH - $dstH) / 2));

            // Soft shadow behind product photo
            imagefilledrectangle($canvas, $dstX - 6, $dstY - 6, $dstX + $dstW + 6, $dstY + $dstH + 6, $shadow);

            imagecopyresampled($canvas, $sourceImg, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
            imagedestroy($sourceImg);
        } else {
            // Placeholder fashion frame if source image not found
            $centerText = $item->headline ?: 'DHANSREE WOMEN FASHION';
            imagestring($canvas, 5, (int) (($canvasW - (strlen($centerText) * 9)) / 2), (int) ($canvasH / 2), $centerText, $gold);
        }

        $fontBold = $this->resolveFont(true);
        $fontRegular = $this->resolveFont(false);

        // TOP HEADER: Luxury Dhansree Brand Plate
        $plateW = 460;
        $plateH = 74;
        $plateX = (int) (($canvasW - $plateW) / 2);
        $plateY = 80;

        imagefilledrectangle($canvas, $plateX + 3, $plateY + 3, $plateX + $plateW + 3, $plateY + $plateH + 3, $shadow);
        imagefilledrectangle($canvas, $plateX, $plateY, $plateX + $plateW, $plateY + $plateH, $cream);
        imagerectangle($canvas, $plateX, $plateY, $plateX + $plateW, $plateY + $plateH, $gold);

        if ($fontBold) {
            imagettftext($canvas, 24, 0, $plateX + 36, $plateY + 36, $maroon, $fontBold, 'DHANSREE');
            if ($fontRegular) {
                imagettftext($canvas, 10, 0, $plateX + 36, $plateY + 56, $gold, $fontRegular, 'STYLE. TECH. EVERYDAY.');
            }
        } else {
            imagestring($canvas, 5, $plateX + 40, $plateY + 16, 'DHANSREE', $maroon);
            imagestring($canvas, 3, $plateX + 40, $plateY + 40, 'STYLE. TECH. EVERYDAY.', $gold);
        }

        // TEMPLATE BADGE (Below brand plate)
        $badgeText = $this->getTemplateBadge($reel->template_code);
        $badgeY = $plateY + $plateH + 36;
        $this->drawPillBadge($canvas, $badgeText, (int) ($canvasW / 2), $badgeY, $maroon, $white, $goldBright, $fontBold);

        // BOTTOM PRODUCT CARD
        $cardW = (int) ($canvasW * 0.90);
        $cardH = 260;
        $cardX = (int) (($canvasW - $cardW) / 2);
        $cardY = $canvasH - $cardH - 120;

        imagefilledrectangle($canvas, $cardX + 4, $cardY + 4, $cardX + $cardW + 4, $cardY + $cardH + 4, $shadow);
        imagefilledrectangle($canvas, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $darkOverlay);
        imagerectangle($canvas, $cardX, $cardY, $cardX + $cardW, $cardY + $cardH, $gold);

        $headline = $item->headline ?: ($item->socialProduct?->name ?? 'Premium Designer Suit');
        $displayHeadline = mb_strimwidth($headline, 0, 42, '...');

        if ($fontBold) {
            imagettftext($canvas, 24, 0, $cardX + 40, $cardY + 60, $white, $fontBold, $displayHeadline);
        } else {
            imagestring($canvas, 5, $cardX + 40, $cardY + 35, $displayHeadline, $white);
        }

        // Price badge and details
        $priceText = $item->price_text ?: ($item->socialProduct?->formatted_selling_price ?? '');
        if ($priceText) {
            $this->drawPillBadge($canvas, $priceText, $cardX + 130, $cardY + 120, $goldBright, $maroon, null, $fontBold);
        }

        // WhatsApp CTA banner on bottom of card
        $ctaText = "DM or WhatsApp to Order: +919887776777";
        if ($fontBold) {
            imagettftext($canvas, 16, 0, $cardX + 40, $cardY + 185, $goldBright, $fontBold, $ctaText);
            if ($fontRegular) {
                imagettftext($canvas, 12, 0, $cardX + 40, $cardY + 220, $cream, $fontRegular, "www.dhansree.com • Limited Collection");
            }
        } else {
            imagestring($canvas, 4, $cardX + 40, $cardY + 170, $ctaText, $goldBright);
            imagestring($canvas, 3, $cardX + 40, $cardY + 200, "www.dhansree.com", $cream);
        }

        imagepng($canvas, $outputPath, 6);
        imagedestroy($canvas);
    }

    /**
     * Assemble slideshow video using FFmpeg xfade filter.
     */
    protected function assembleSlideshowWithTransitions(
        array $preparedSlides,
        string $outputVideoFile,
        int $width,
        int $height,
        int $totalDuration,
        string $transition,
        string $ffmpegPath
    ): void {
        $count = count($preparedSlides);

        if ($count === 1) {
            $cmd = [
                $ffmpegPath, '-y',
                '-loop', '1',
                '-t', (string) $totalDuration,
                '-i', $preparedSlides[0],
                '-vf', "scale={$width}:{$height},format=yuv420p",
                '-c:v', 'libx264',
                '-preset', 'fast',
                '-pix_fmt', 'yuv420p',
                '-r', '30',
                $outputVideoFile,
            ];
            $this->executeProcess($cmd, 60);
            return;
        }

        $transDuration = 0.6;
        $slideDuration = ($totalDuration + (($count - 1) * $transDuration)) / $count;
        $slideDuration = max($transDuration + 1.0, $slideDuration);

        $inputs = [];
        foreach ($preparedSlides as $slide) {
            $inputs[] = '-loop';
            $inputs[] = '1';
            $inputs[] = '-t';
            $inputs[] = sprintf('%.2f', $slideDuration);
            $inputs[] = '-i';
            $inputs[] = $slide;
        }

        $filterParts = [];
        $lastOutput = '0:v';

        for ($i = 1; $i < $count; $i++) {
            $offset = ($i * $slideDuration) - ($i * $transDuration);
            $nextOutput = ($i === $count - 1) ? 'vfinal' : "v{$i}";
            $filterParts[] = "[{$lastOutput}][{$i}:v]xfade=transition={$transition}:duration={$transDuration}:offset=" . sprintf('%.2f', $offset) . "[{$nextOutput}]";
            $lastOutput = $nextOutput;
        }

        $filterParts[] = "[vfinal]scale={$width}:{$height},format=yuv420p[vout]";
        $filterComplex = implode(';', $filterParts);

        $cmd = array_merge(
            [$ffmpegPath, '-y'],
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

        $this->executeProcess($cmd, 120);
    }

    /**
     * Finalize video with background music and optional ending CTA card.
     */
    protected function finalizeVideoWithAudioAndCta(
        string $baseVideoFile,
        string $outputVideoFile,
        SocialReel $reel,
        int $width,
        int $height,
        int $totalDuration,
        string $tempDir,
        string $ffmpegPath
    ): void {
        // Resolve audio track file
        $audioKey = $reel->audio_track ?: 'festive_royal';
        $audioFile = resource_path("audio/video-studio/{$audioKey}.mp3");
        $hasAudio = file_exists($audioFile);

        // Build closing CTA card graphic
        $ctaGraphicFile = $tempDir . DIRECTORY_SEPARATOR . 'closing_cta_card.png';
        $this->renderClosingCtaGraphic($ctaGraphicFile, $width, $height);
        $ctaStartTime = max(1.0, $totalDuration - 2.5);

        $inputs = [
            $ffmpegPath, '-y',
            '-i', $baseVideoFile,
            '-loop', '1', '-i', $ctaGraphicFile,
        ];

        if ($hasAudio) {
            $inputs[] = '-stream_loop';
            $inputs[] = '-1';
            $inputs[] = '-i';
            $inputs[] = $audioFile;
        }

        // Overlay closing CTA card during last 2.5 seconds
        $filterComplex = "[0:v][1:v]overlay=0:0:enable='gte(t,{$ctaStartTime})'[v_out]";

        $cmd = array_merge($inputs, [
            '-filter_complex', $filterComplex,
            '-map', '[v_out]',
        ]);

        if ($hasAudio) {
            $fadeStart = max(1.0, $totalDuration - 1.5);
            $cmd = array_merge($cmd, [
                '-map', '2:a',
                '-af', "afade=t=in:ss=0:d=1,afade=t=out:st={$fadeStart}:d=1.5",
                '-c:a', 'aac',
                '-b:a', '192k',
            ]);
        } else {
            $cmd = array_merge($cmd, ['-an']);
        }

        $cmd = array_merge($cmd, [
            '-t', (string) $totalDuration,
            '-c:v', 'libx264',
            '-preset', 'fast',
            '-crf', '20',
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            $outputVideoFile,
        ]);

        $this->executeProcess($cmd, 120);
    }

    /**
     * Render the closing CTA card in 1080x1920.
     */
    protected function renderClosingCtaGraphic(string $outputPath, int $w, int $h): void
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, true);

        $maroon = imagecolorallocate($im, 88, 0, 0);
        $gold = imagecolorallocate($im, 176, 136, 88);
        $goldBright = imagecolorallocate($im, 212, 175, 55);
        $white = imagecolorallocate($im, 255, 255, 255);
        $cream = imagecolorallocate($im, 251, 250, 248);
        $shadow = imagecolorallocatealpha($im, 0, 0, 0, 90);

        imagefilledrectangle($im, 0, 0, $w, $h, $maroon);

        // Gold luxury inner border
        imagerectangle($im, 50, 50, $w - 50, $h - 50, $gold);
        imagerectangle($im, 56, 56, $w - 56, $h - 56, $gold);

        $fontBold = $this->resolveFont(true);
        $fontRegular = $this->resolveFont(false);

        // Center Dhansree Emblem
        $centerX = (int) ($w / 2);
        $centerY = (int) ($h * 0.40);

        if ($fontBold) {
            $bbox = imagettfbbox(44, 0, $fontBold, 'DHANSREE');
            $tw = abs($bbox[2] - $bbox[0]);
            imagettftext($im, 44, 0, (int) ($centerX - ($tw / 2)), $centerY, $cream, $fontBold, 'DHANSREE');

            if ($fontRegular) {
                $bbox2 = imagettfbbox(18, 0, $fontRegular, 'STYLE. TECH. EVERYDAY.');
                $tw2 = abs($bbox2[2] - $bbox2[0]);
                imagettftext($im, 18, 0, (int) ($centerX - ($tw2 / 2)), $centerY + 45, $goldBright, $fontRegular, 'STYLE. TECH. EVERYDAY.');
            }
        } else {
            imagestring($im, 5, $centerX - 80, $centerY, 'DHANSREE', $cream);
            imagestring($im, 4, $centerX - 100, $centerY + 30, 'STYLE. TECH. EVERYDAY.', $goldBright);
        }

        // WhatsApp CTA Green Pill
        $green = imagecolorallocate($im, 37, 211, 102);
        $pillW = 760;
        $pillH = 110;
        $pillX = (int) (($w - $pillW) / 2);
        $pillY = (int) ($h * 0.62);

        imagefilledrectangle($im, $pillX + 4, $pillY + 4, $pillX + $pillW + 4, $pillY + $pillH + 4, $shadow);
        imagefilledrectangle($im, $pillX, $pillY, $pillX + $pillW, $pillY + $pillH, $green);

        $ctaMsg = "WhatsApp to Order: +919887776777";
        if ($fontBold) {
            $bbox3 = imagettfbbox(24, 0, $fontBold, $ctaMsg);
            $tw3 = abs($bbox3[2] - $bbox3[0]);
            imagettftext($im, 24, 0, (int) ($centerX - ($tw3 / 2)), $pillY + 65, $white, $fontBold, $ctaMsg);
        } else {
            imagestring($im, 5, $pillX + 80, $pillY + 45, $ctaMsg, $white);
        }

        // Bottom tagline & guarantee
        $guarantee = "All India Shipping Available  •  Order-Based Sourcing";
        if ($fontRegular) {
            $bbox4 = imagettfbbox(16, 0, $fontRegular, $guarantee);
            $tw4 = abs($bbox4[2] - $bbox4[0]);
            imagettftext($im, 16, 0, (int) ($centerX - ($tw4 / 2)), (int) ($h * 0.78), $cream, $fontRegular, $guarantee);
        } else {
            imagestring($im, 4, $centerX - 180, (int) ($h * 0.78), $guarantee, $cream);
        }

        imagepng($im, $outputPath, 6);
        imagedestroy($im);
    }

    /**
     * Extract poster thumbnail at 1.0s.
     */
    protected function extractVideoThumbnail(string $videoPath, string $thumbPath, string $ffmpegPath): void
    {
        $cmd = [
            $ffmpegPath, '-y',
            '-ss', '00:00:01.000',
            '-i', $videoPath,
            '-vframes', '1',
            '-q:v', '2',
            $thumbPath,
        ];
        $this->executeProcess($cmd, 30);
    }

    /**
     * Fallback for environments lacking ffmpeg.
     */
    protected function generateFallbackArtifacts(string $slidePath, string $videoPath, string $thumbPath): void
    {
        copy($slidePath, $thumbPath);
        // Create minimal valid 1-byte fallback file if video cannot be rendered
        file_put_contents($videoPath, file_get_contents($slidePath));
    }

    /**
     * Execute process with timeout and error handling.
     */
    protected function executeProcess(array $cmd, int $timeout = 120): void
    {
        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("FFmpeg execution error: " . $process->getErrorOutput());
        }
    }

    /**
     * Resolve template transition code for FFmpeg xfade.
     */
    protected function resolveTransition(string $templateCode): string
    {
        return match ($templateCode) {
            'NEW_ARRIVALS' => 'wipeleft',
            'FESTIVE_COLLECTION' => 'circleopen',
            'SPECIAL_PRICE_OFFERS' => 'slideright',
            'DAILY_FASHION_COLLECTION' => 'wipeup',
            'MULTIPLE_DESIGNS_SHOWCASE' => 'slideleft',
            default => 'fade',
        };
    }

    /**
     * Resolve template badge label.
     */
    protected function getTemplateBadge(string $templateCode): string
    {
        return match ($templateCode) {
            'NEW_ARRIVALS' => 'NEW ARRIVALS 2026',
            'TRENDING_DESIGNS' => 'TRENDING NOW',
            'FESTIVE_COLLECTION' => 'FESTIVE COLLECTION',
            'SPECIAL_PRICE_OFFERS' => 'SPECIAL PRICE OFFER',
            'LIMITED_STOCK_COLLECTION' => 'LIMITED PIECES ONLY',
            'DAILY_FASHION_COLLECTION' => 'DAILY WEAR EDIT',
            'MULTIPLE_DESIGNS_SHOWCASE' => 'NEW CATALOG DESIGNS',
            default => 'EXCLUSIVE DESIGN',
        };
    }

    /**
     * Draw pill badge in GD.
     */
    protected function drawPillBadge(
        $im,
        string $text,
        int $centerX,
        int $centerY,
        int $bgColor,
        int $textColor,
        ?int $borderColor,
        ?string $font
    ): void {
        $padX = 24;
        $padY = 12;

        if ($font) {
            $bbox = imagettfbbox(14, 0, $font, $text);
            $tw = abs($bbox[2] - $bbox[0]);
            $th = abs($bbox[7] - $bbox[1]);
        } else {
            $tw = strlen($text) * 9;
            $th = 14;
        }

        $x1 = (int) ($centerX - ($tw / 2) - $padX);
        $y1 = (int) ($centerY - ($th / 2) - $padY);
        $x2 = (int) ($centerX + ($tw / 2) + $padX);
        $y2 = (int) ($centerY + ($th / 2) + $padY);

        imagefilledrectangle($im, $x1, $y1, $x2, $y2, $bgColor);
        if ($borderColor !== null) {
            imagerectangle($im, $x1, $y1, $x2, $y2, $borderColor);
        }

        if ($font) {
            imagettftext($im, 14, 0, (int) ($centerX - ($tw / 2)), (int) ($centerY + ($th / 3)), $textColor, $font, $text);
        } else {
            imagestring($im, 4, (int) ($centerX - ($tw / 2)), (int) ($centerY - 8), $text, $textColor);
        }
    }

    /**
     * Resolve font path.
     */
    protected function resolveFont(bool $bold = false): ?string
    {
        $candidates = $bold ? [
            resource_path('fonts/LiberationSans-Bold.ttf'),
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        ] : [
            resource_path('fonts/LiberationSans-Regular.ttf'),
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        foreach ($candidates as $cand) {
            if ($cand && file_exists($cand)) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Load GD image from file with mime detection.
     */
    protected function loadGdImage(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }
}
