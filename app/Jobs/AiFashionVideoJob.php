<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ProductImage;
use App\Models\ProductVideo;
use App\Services\VideoStudio\FashionVideoPromptService;
use App\Services\VideoStudio\FfmpegService;
use App\Services\VideoStudio\GoogleVideoGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiFashionVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 2;

    public function __construct(
        public int $videoId
    ) {}

    public function handle(
        GoogleVideoGenerationService $googleService,
        FashionVideoPromptService $promptService,
        FfmpegService $ffmpegService
    ): void {
        /** @var ProductVideo|null $video */
        $video = ProductVideo::find($this->videoId);
        if (!$video) {
            Log::warning("AiFashionVideoJob: Video record #{$this->videoId} not found.");
            return;
        }

        $video->update([
            'status' => 'processing',
            'progress' => 10,
            'error_message' => null,
        ]);

        $tempDir = storage_path('app/temp/ai-video-' . Str::uuid());
        File::ensureDirectoryExists($tempDir);

        try {
            // 1. Resolve primary reference image
            $primaryImagePath = $this->resolvePrimaryImage($video, $tempDir);
            if (!$primaryImagePath || !file_exists($primaryImagePath)) {
                throw new \RuntimeException("Could not resolve reference image for product.");
            }

            // 2. Prepare prompt
            $prompt = $video->prompt;
            if (empty($prompt) && $video->product) {
                $prompt = $promptService->buildPrompt(
                    $video->product,
                    $video->prompt_template ?? 'runway_walk'
                );
                $video->update(['prompt' => $prompt]);
            }

            $negativePrompt = $video->negative_prompt ?: $promptService->getNegativePrompt();

            $video->update(['progress' => 20]);

            // 3. Submit request to Google Veo API
            $submission = $googleService->submitGeneration(
                primaryImagePath: $primaryImagePath,
                prompt: $prompt,
                aspectRatio: $video->aspect_ratio ?? '9:16',
                durationSeconds: (int) ($video->duration_seconds ?? 5),
                negativePrompt: $negativePrompt
            );

            $modelName = config('video_studio.google_video.model', 'veo-2.0-generate-001');

            $video->update([
                'ai_provider' => 'google_veo',
                'ai_model' => $modelName,
                'ai_operation_id' => $submission['operation_id'],
                'estimated_cost' => $submission['estimated_cost'] ?? 0.15,
                'progress' => 30,
            ]);

            // 4. Poll operation until complete
            $pollResult = $googleService->pollOperation(
                operationId: $submission['operation_id'],
                onProgress: function (int $progress) use ($video) {
                    $video->update(['progress' => $progress]);
                }
            );

            if ($pollResult['status'] !== 'completed') {
                throw new \RuntimeException($pollResult['error'] ?? 'AI Video generation failed or timed out.');
            }

            $video->update(['progress' => 80]);

            // 5. Download raw AI master video
            $rawMasterPath = $tempDir . DIRECTORY_SEPARATOR . 'raw_master.mp4';
            $downloaded = $googleService->downloadVideoToPath($pollResult, $rawMasterPath, $primaryImagePath);

            if (!$downloaded || !file_exists($rawMasterPath) || filesize($rawMasterPath) === 0) {
                throw new \RuntimeException("Failed to download or compile generated AI master video.");
            }

            $disk = config('video_studio.disk', 'public');
            $storageDir = config('video_studio.storage_dir', 'video-studio');

            // Store raw AI master footage
            $masterStoragePath = "{$storageDir}/ai_master/master_{$video->id}_" . Str::random(10) . '.mp4';
            Storage::disk($disk)->put($masterStoragePath, File::get($rawMasterPath));
            $video->update(['ai_master_video_path' => $masterStoragePath]);

            // 6. Handle Video Mode Output
            if ($video->video_type === 'ai_fashion_branded') {
                // Post-process with FFmpeg: add Dhansree logo, selling price, music, and closing CTA
                $video->update(['progress' => 90]);
                $branded = $ffmpegService->brandAiVideo($video, $rawMasterPath, $tempDir);

                $finalVideoFilename = "{$storageDir}/videos/video_{$video->id}_" . Str::random(10) . '.mp4';
                $finalThumbFilename = "{$storageDir}/thumbnails/thumb_{$video->id}_" . Str::random(10) . '.jpg';

                Storage::disk($disk)->put($finalVideoFilename, File::get($branded['video_file']));
                Storage::disk($disk)->put($finalThumbFilename, File::get($branded['thumbnail_file']));

                $fileSize = $branded['file_size'];
            } else {
                // Raw AI fashion footage without overlays
                $finalVideoFilename = "{$storageDir}/videos/video_{$video->id}_" . Str::random(10) . '.mp4';
                $finalThumbFilename = "{$storageDir}/thumbnails/thumb_{$video->id}_" . Str::random(10) . '.jpg';

                $thumbLocal = $tempDir . DIRECTORY_SEPARATOR . 'raw_thumb.jpg';
                $ffmpegService->extractThumbnail($rawMasterPath, $thumbLocal);

                Storage::disk($disk)->put($finalVideoFilename, File::get($rawMasterPath));
                if (file_exists($thumbLocal)) {
                    Storage::disk($disk)->put($finalThumbFilename, File::get($thumbLocal));
                } else {
                    Storage::disk($disk)->put($finalThumbFilename, File::get($primaryImagePath));
                }

                $fileSize = filesize($rawMasterPath);
            }

            // 7. Complete job & set Quality Control status to pending_review
            $video->update([
                'status' => 'completed',
                'progress' => 100,
                'video_path' => $finalVideoFilename,
                'thumbnail_path' => $finalThumbFilename,
                'file_size_bytes' => $fileSize,
                'approval_status' => 'pending_review',
                'actual_cost' => $video->estimated_cost ?: 0.15,
                'completed_at' => now(),
                'error_message' => null,
            ]);

            Log::info("AI Fashion Video #{$video->id} generated successfully. Ready for Quality Review.");
        } catch (\Throwable $e) {
            Log::error("AiFashionVideoJob failed for video #{$video->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            $video->update([
                'status' => 'failed',
                'progress' => 0,
                'error_message' => $e->getMessage(),
            ]);
        } finally {
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Resolve the primary product reference image to a local file path.
     */
    protected function resolvePrimaryImage(ProductVideo $video, string $tempDir): ?string
    {
        // 1. Check primary_image_id if explicitly designated
        if ($video->primary_image_id) {
            $image = ProductImage::find($video->primary_image_id);
            if ($image) {
                $path = $this->getLocalOrDownload($image, $tempDir, 0);
                if ($path) {
                    return $path;
                }
            }
        }

        // 2. Check first image from selected_image_ids
        $selectedIds = $video->selected_image_ids ?? [];
        if (!empty($selectedIds)) {
            $firstId = $selectedIds[0];
            $image = ProductImage::find($firstId);
            if ($image) {
                $path = $this->getLocalOrDownload($image, $tempDir, 0);
                if ($path) {
                    return $path;
                }
            }
        }

        // 3. Fallback to product's first image
        if ($video->product) {
            $image = $video->product->images()->orderBy('position')->first();
            if ($image) {
                return $this->getLocalOrDownload($image, $tempDir, 0);
            }
        }

        return null;
    }

    /**
     * Locate image locally or download to temporary directory.
     */
    protected function getLocalOrDownload(ProductImage $image, string $tempDir, int $index): ?string
    {
        $key = ltrim((string) $image->storage_key, '/');

        $candidates = [
            public_path('media/' . $key),
            storage_path('app/public/media/' . $key),
            storage_path('app/public/' . $key),
            storage_path('app/' . $key),
        ];

        $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
        if ($extraDir) {
            $candidates[] = rtrim($extraDir, '/') . '/' . $key;
        }

        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                return $candidate;
            }
        }

        $url = $image->url;
        if (!empty($url) && (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'))) {
            try {
                $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?? 'jpg', PATHINFO_EXTENSION) ?: 'jpg';
                $dest = $tempDir . DIRECTORY_SEPARATOR . sprintf('reference_%02d.%s', $index, $ext);

                $response = Http::timeout(15)->get($url);
                if ($response->successful()) {
                    File::put($dest, $response->body());
                    return $dest;
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to download image '{$url}': {$e->getMessage()}");
            }
        }

        return null;
    }
}
