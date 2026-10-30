<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductVideo;
use App\Services\VideoStudio\FfmpegService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TranscodeProductVideosToWebmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:transcode-webm 
                            {--force : Re-transcode videos even if a WebM version already exists}
                            {--product= : Transcode videos only for a specific product ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transcode product MP4 videos to high-efficiency WebM (VP9) and generate WebP poster thumbnails to reduce storage and accelerate page render time.';

    /**
     * Execute the console command.
     */
    public function handle(FfmpegService $ffmpeg): int
    {
        $this->info('─────────────────────────────────────────────────────────────────');
        $this->info('  Dhansree Video Studio: WebM & WebP Transcoding Engine');
        $this->info('─────────────────────────────────────────────────────────────────');

        if (!$ffmpeg->isAvailable()) {
            $this->error('FFmpeg is not available on this server. Please ensure ffmpeg is installed.');
            return Command::FAILURE;
        }

        $this->info("✓ FFmpeg binary detected at: {$ffmpeg->getFfmpegPath()}");
        $this->info("✓ FFprobe binary detected at: {$ffmpeg->getFfprobePath()}");

        $force = (bool) $this->option('force');
        $productId = $this->option('product');

        $query = ProductVideo::query()->with('product');
        if ($productId) {
            $query->where('product_id', (int) $productId);
        }

        $videos = $query->orderBy('id')->get();

        if ($videos->isEmpty()) {
            $this->info('No product videos found to process.');
            return Command::SUCCESS;
        }

        $this->info("Found {$videos->count()} product video(s) to inspect." . ($force ? ' [FORCE MODE]' : ''));

        $disk = config('video_studio.disk', 'public');
        $storageDir = config('video_studio.storage_dir', 'video-studio');

        $convertedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $totalOriginalBytes = 0;
        $totalWebmBytes = 0;

        foreach ($videos as $video) {
            $this->line('');
            $this->info("▶ Video #{$video->id} — \"{$video->title}\" (Product #{$video->product_id})");

            $sourcePath = $video->video_path;
            if (!$sourcePath || !Storage::disk($disk)->exists($sourcePath)) {
                $this->warn("  [SKIPPED] Source video file not found in storage: {$sourcePath}");
                $skippedCount++;
                continue;
            }

            $currentSettings = $video->render_settings ?? [];
            $existingWebmPath = $currentSettings['webm_path'] ?? null;
            $hasValidWebm = $existingWebmPath && Storage::disk($disk)->exists($existingWebmPath) && Storage::disk($disk)->size($existingWebmPath) > 0;

            if ($hasValidWebm && !$force) {
                $size = Storage::disk($disk)->size($existingWebmPath);
                $this->line("  ✓ Already has WebM: {$existingWebmPath} (" . $this->formatBytes($size) . ")");
                $skippedCount++;
                continue;
            }

            $sourceFullPath = Storage::disk($disk)->path($sourcePath);
            $sourceBytes = filesize($sourceFullPath);
            $totalOriginalBytes += $sourceBytes;

            $this->line("  Source: " . basename($sourcePath) . " (" . $this->formatBytes($sourceBytes) . ")");

            // 1. Inspect dimensions
            $dimensions = $ffmpeg->getVideoDimensions($sourceFullPath);
            $w = $dimensions['width'] ?: ($video->width ?: 1080);
            $h = $dimensions['height'] ?: ($video->height ?: 1920);
            $dur = $dimensions['duration'] ?: ($video->duration_seconds ?: 10);
            $this->line("  Dimensions: {$w}x{$h} &bull; Duration: {$dur}s");

            // 2. Transcode to WebM (VP9, CRF 32, realtime cpu-used 4)
            $tempWebm = storage_path('app/temp/webm_' . Str::uuid() . '.webm');
            File::ensureDirectoryExists(dirname($tempWebm));

            $this->output->write("  Converting to WebM (VP9)... ");
            $start = microtime(true);

            try {
                $ffmpeg->renderCleanWebm($sourceFullPath, $tempWebm, $w, $h);
                $elapsed = round(microtime(true) - $start, 2);

                if (!file_exists($tempWebm) || filesize($tempWebm) === 0) {
                    throw new \RuntimeException("WebM output file was not created or is empty.");
                }

                $webmBytes = filesize($tempWebm);
                $totalWebmBytes += $webmBytes;
                $savedPct = $sourceBytes > 0 ? round((1 - ($webmBytes / $sourceBytes)) * 100, 1) : 0;

                $webmFilename = 'clean_' . Str::uuid() . '.webm';
                $webmRelativePath = "{$storageDir}/videos/{$webmFilename}";
                Storage::disk($disk)->put($webmRelativePath, File::get($tempWebm));
                @unlink($tempWebm);

                $currentSettings['webm_path'] = $webmRelativePath;

                $this->info("DONE in {$elapsed}s");
                $this->line("  ✓ WebM: " . $this->formatBytes($webmBytes) . " (Saved {$savedPct}% bandwidth/storage!)");

                // 3. Extract lightweight WebP thumbnail if missing or if current is JPG
                $currentThumb = $video->thumbnail_path;
                $needsWebpThumb = !$currentThumb 
                    || !Storage::disk($disk)->exists($currentThumb) 
                    || str_ends_with(strtolower($currentThumb), '.jpg') 
                    || str_ends_with(strtolower($currentThumb), '.jpeg');

                if ($needsWebpThumb || $force) {
                    $tempThumb = storage_path('app/temp/thumb_' . Str::uuid() . '.webp');
                    try {
                        $ffmpeg->extractThumbnail($sourceFullPath, $tempThumb);
                        if (file_exists($tempThumb) && filesize($tempThumb) > 0) {
                            $thumbFilename = 'thumb_' . Str::uuid() . '.webp';
                            $thumbRelativePath = "{$storageDir}/thumbnails/{$thumbFilename}";
                            Storage::disk($disk)->put($thumbRelativePath, File::get($tempThumb));
                            @unlink($tempThumb);

                            $video->thumbnail_path = $thumbRelativePath;
                            $this->line("  ✓ WebP Poster Thumbnail generated: {$thumbRelativePath}");
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Thumbnail extraction failed in command: " . $e->getMessage());
                    }
                }

                $video->render_settings = $currentSettings;
                $video->width = $w;
                $video->height = $h;
                $video->duration_seconds = $dur;
                $video->save();

                $convertedCount++;
            } catch (\Throwable $e) {
                $this->error("FAILED: " . $e->getMessage());
                Log::error("WebM transcode command error on video #{$video->id}: " . $e->getMessage());
                $failedCount++;
                if (file_exists($tempWebm)) {
                    @unlink($tempWebm);
                }
            }
        }

        $this->line('');
        $this->info('─────────────────────────────────────────────────────────────────');
        $this->info('  Transcoding Summary');
        $this->info('─────────────────────────────────────────────────────────────────');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Videos Inspected', (string) $videos->count()],
                ['Successfully Converted to WebM', "<info>{$convertedCount}</info>"],
                ['Skipped (Already WebM / Missing)', (string) $skippedCount],
                ['Failed', $failedCount > 0 ? "<error>{$failedCount}</error>" : '0'],
                ['Original MP4 Total Size', $this->formatBytes($totalOriginalBytes)],
                ['Optimized WebM Total Size', $this->formatBytes($totalWebmBytes)],
                ['Total Storage Saved', $this->formatBytes(max(0, $totalOriginalBytes - $totalWebmBytes))],
            ]
        );

        return $failedCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
