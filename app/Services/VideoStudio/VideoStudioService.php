<?php

namespace App\Services\VideoStudio;

use App\Jobs\AiFashionVideoJob;
use App\Jobs\RenderProductVideoJob;
use App\Models\AdminUser;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVideo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoStudioService
{
    public function __construct(
        protected FfmpegService $ffmpegService,
        protected GoogleVideoGenerationService $googleVideoService,
        protected FashionVideoPromptService $promptService
    ) {}

    /**
     * Create a new video studio record and dispatch rendering job.
     */
    public function createVideo(array $data, ?AdminUser $admin = null): ProductVideo
    {
        $productId = $data['product_id'] ?? null;
        $product = $productId ? Product::find($productId) : null;

        $videoType = $data['video_type'] ?? 'slideshow';
        $isAiMode = in_array($videoType, ['ai_fashion', 'ai_fashion_branded', 'ai_motion'], true);

        // Guard AI fashion video generation if limits reached or not configured
        if ($isAiMode) {
            $limits = $this->googleVideoService->checkLimits();
            if (!$limits['allowed']) {
                throw new \RuntimeException($limits['reason'] ?? 'AI Fashion Video generation is currently unavailable.');
            }
        }

        $aspectRatio = $data['aspect_ratio'] ?? '9:16';
        $aspectConfig = config("video_studio.aspect_ratios.{$aspectRatio}", [
            'width' => 1080,
            'height' => 1920,
        ]);

        $template = $data['template'] ?? 'fashion_showcase';
        $duration = (int) ($data['duration_seconds'] ?? config('video_studio.default_duration', 10));

        // Default or custom title
        $title = !empty($data['title']) ? trim($data['title']) : ($product?->name ?? 'Dhansree Promotional Reel');

        // Selected images or fallback to all product images
        $selectedImageIds = $data['image_ids'] ?? [];
        if (empty($selectedImageIds) && $product) {
            $selectedImageIds = $product->images()->pluck('id')->all();
        }

        $primaryImageId = !empty($data['primary_image_id'])
            ? (int) $data['primary_image_id']
            : ($selectedImageIds[0] ?? null);

        $renderSettings = [
            'enable_music' => (bool) ($data['enable_music'] ?? true),
            'audio_track' => $data['audio_track'] ?? 'festive_royal',
            'text_overlay' => (bool) ($data['text_overlay'] ?? true),
            'show_price' => (bool) ($data['show_price'] ?? true),
            'call_to_action' => (bool) ($data['call_to_action'] ?? true),
            'custom_headline' => $data['custom_headline'] ?? null,
        ];

        $promptTemplate = $data['prompt_template'] ?? 'runway_walk';
        $prompt = !empty($data['prompt']) ? trim($data['prompt']) : null;
        if ($isAiMode && empty($prompt) && $product) {
            $prompt = $this->promptService->buildPrompt($product, $promptTemplate);
        }

        $negativePrompt = !empty($data['negative_prompt'])
            ? trim($data['negative_prompt'])
            : ($isAiMode ? $this->promptService->getNegativePrompt() : null);

        $estimatedCost = $isAiMode ? (float) config('video_studio.google_video.estimated_cost_per_clip', 0.15) : null;

        $video = ProductVideo::create([
            'product_id' => $product?->id,
            'title' => $title,
            'video_type' => $videoType,
            'template' => $template,
            'aspect_ratio' => $aspectRatio,
            'width' => $aspectConfig['width'] ?? 1080,
            'height' => $aspectConfig['height'] ?? 1920,
            'duration_seconds' => $duration,
            'status' => 'queued',
            'progress' => 0,
            'selected_image_ids' => array_values($selectedImageIds),
            'primary_image_id' => $primaryImageId,
            'render_settings' => $renderSettings,
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt,
            'prompt_template' => $promptTemplate,
            'estimated_cost' => $estimatedCost,
            'approval_status' => $isAiMode ? 'pending_review' : 'not_required',
            'created_by' => $admin?->id,
        ]);

        // Dispatch appropriate background job
        if ($isAiMode) {
            AiFashionVideoJob::dispatch($video->id);
        } else {
            RenderProductVideoJob::dispatch($video->id);
        }

        return $video;
    }

    /**
     * Render the video job in background.
     */
    public function render(ProductVideo $video): bool
    {
        $video->update([
            'status' => 'processing',
            'progress' => 10,
            'error_message' => null,
        ]);

        $tempDir = storage_path('app/temp/video-studio-' . Str::uuid());
        File::ensureDirectoryExists($tempDir);

        try {
            // Resolve local image paths
            $imagePaths = $this->resolveSelectedImages($video, $tempDir);

            if (empty($imagePaths)) {
                throw new \RuntimeException("No usable product images found for this product.");
            }

            $video->update(['progress' => 30]);

            // Call FFmpeg to render slideshow, overlays, and thumbnail
            $renderResult = $this->ffmpegService->renderSlideshow($video, $imagePaths, $tempDir);

            $video->update(['progress' => 85]);

            // Save output video and thumbnail to configured disk
            $disk = config('video_studio.disk', 'public');
            $storageDir = config('video_studio.storage_dir', 'video-studio');

            $videoFilename = "{$storageDir}/videos/video_{$video->id}_" . Str::random(10) . '.mp4';
            $thumbFilename = "{$storageDir}/thumbnails/thumb_{$video->id}_" . Str::random(10) . '.jpg';

            // Store files on disk
            Storage::disk($disk)->put(
                $videoFilename,
                File::get($renderResult['video_file'])
            );

            Storage::disk($disk)->put(
                $thumbFilename,
                File::get($renderResult['thumbnail_file'])
            );

            $video->update([
                'status' => 'completed',
                'progress' => 100,
                'video_path' => $videoFilename,
                'thumbnail_path' => $thumbFilename,
                'file_size_bytes' => $renderResult['file_size'],
                'error_message' => null,
            ]);

            Log::info("Product video #{$video->id} rendered successfully ({$renderResult['file_size']} bytes).");
            return true;
        } catch (\Throwable $e) {
            Log::error("Product video #{$video->id} render failed: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            $video->update([
                'status' => 'failed',
                'progress' => 0,
                'error_message' => $e->getMessage(),
            ]);

            return false;
        } finally {
            // Clean up temporary workspace directory
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Retry rendering a previously failed or stuck video.
     */
    public function retry(ProductVideo $video): bool
    {
        $video->update([
            'status' => 'queued',
            'progress' => 0,
            'error_message' => null,
        ]);

        if ($video->isAi()) {
            AiFashionVideoJob::dispatch($video->id);
        } else {
            RenderProductVideoJob::dispatch($video->id);
        }

        return true;
    }

    /**
     * Approve AI video following manual quality control review.
     */
    public function approve(ProductVideo $video, AdminUser $admin, ?array $checklist = null): bool
    {
        return $video->update([
            'approval_status' => 'approved',
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'quality_checklist' => $checklist,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject AI video with specific feedback.
     */
    public function reject(ProductVideo $video, AdminUser $admin, string $reason, ?array $checklist = null): bool
    {
        return $video->update([
            'approval_status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'rejection_reason' => $reason,
            'quality_checklist' => $checklist,
        ]);
    }

    /**
     * Re-generate an AI fashion video with updated prompt or settings.
     */
    public function regenerate(ProductVideo $video, AdminUser $admin, ?string $updatedPrompt = null): bool
    {
        $video->update([
            'status' => 'queued',
            'progress' => 0,
            'approval_status' => 'pending_review',
            'prompt' => $updatedPrompt ?: $video->prompt,
            'error_message' => null,
            'rejection_reason' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        AiFashionVideoJob::dispatch($video->id);
        return true;
    }

    /**
     * Safely delete video and thumbnail from storage, preserving source product images.
     */
    public function delete(ProductVideo $video): bool
    {
        $disk = config('video_studio.disk', 'public');

        // Delete rendered video file from storage
        if ($video->video_path && Storage::disk($disk)->exists($video->video_path)) {
            Storage::disk($disk)->delete($video->video_path);
        }

        // Delete raw AI master video if exists
        if ($video->ai_master_video_path && Storage::disk($disk)->exists($video->ai_master_video_path)) {
            Storage::disk($disk)->delete($video->ai_master_video_path);
        }

        // Delete rendered thumbnail file from storage
        if ($video->thumbnail_path && Storage::disk($disk)->exists($video->thumbnail_path)) {
            Storage::disk($disk)->delete($video->thumbnail_path);
        }

        // Source product images in product_images table remain completely untouched!
        return (bool) $video->delete();
    }

    /**
     * Resolve and prepare local image files from database IDs or product associations.
     *
     * @return array<string> Absolute local file paths
     */
    protected function resolveSelectedImages(ProductVideo $video, string $tempDir): array
    {
        $selectedIds = $video->selected_image_ids ?? [];
        $images = collect();

        if (!empty($selectedIds)) {
            // Maintain selected ordering
            $foundImages = ProductImage::whereIn('id', $selectedIds)->get()->keyBy('id');
            foreach ($selectedIds as $id) {
                if (isset($foundImages[$id])) {
                    $images->push($foundImages[$id]);
                }
            }
        }

        // Fallback to product images if none specifically picked
        if ($images->isEmpty() && $video->product) {
            $images = $video->product->images()->orderBy('position')->get();
        }

        $localPaths = [];
        foreach ($images as $idx => $img) {
            $local = $this->getLocalPathForImage($img, $tempDir, $idx);
            if ($local && file_exists($local)) {
                $localPaths[] = $local;
            }
        }

        return $localPaths;
    }

    /**
     * Find local file path for ProductImage or download to temporary directory if remote.
     */
    protected function getLocalPathForImage(ProductImage $image, string $tempDir, int $index): ?string
    {
        $key = ltrim((string) $image->storage_key, '/');

        // Check local disk directories
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

        // If stored as full URL or only accessible via URL, fetch temporarily
        $url = $image->url;
        if (!empty($url) && (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'))) {
            try {
                $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?? 'jpg', PATHINFO_EXTENSION) ?: 'jpg';
                $dest = $tempDir . DIRECTORY_SEPARATOR . sprintf('downloaded_%02d.%s', $index, $ext);

                $response = Http::timeout(15)->get($url);
                if ($response->successful()) {
                    File::put($dest, $response->body());
                    return $dest;
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to fetch image URL '{$url}': {$e->getMessage()}");
            }
        }

        return null;
    }
}
