<?php

namespace App\Jobs;

use App\Models\ProductVideo;
use App\Services\VideoStudio\VideoStudioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RenderProductVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $productVideoId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(VideoStudioService $videoStudioService): void
    {
        $video = ProductVideo::find($this->productVideoId);

        if (!$video) {
            Log::warning("RenderProductVideoJob skipped: ProductVideo #{$this->productVideoId} not found.");
            return;
        }

        $videoStudioService->render($video);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("RenderProductVideoJob failed for video #{$this->productVideoId}: " . $exception->getMessage());

        $video = ProductVideo::find($this->productVideoId);
        if ($video) {
            $video->update([
                'status' => 'failed',
                'progress' => 0,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
