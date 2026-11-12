<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SocialReel;
use App\Services\SocialCommerce\SocialReelRendererService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RenderSocialReelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $reelId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SocialReelRendererService $renderer): void
    {
        $reel = SocialReel::find($this->reelId);
        if (!$reel) {
            Log::warning("RenderSocialReelJob: Reel ID {$this->reelId} not found.");
            return;
        }

        Log::info("Starting video render for Social Reel {$reel->reel_code} (ID: {$reel->id})");
        $renderer->renderReel($reel);
        Log::info("Successfully rendered Social Reel {$reel->reel_code}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $reel = SocialReel::find($this->reelId);
        if ($reel) {
            $reel->update([
                'status' => 'FAILED',
                'error_message' => $exception->getMessage(),
                'progress' => 0,
            ]);
        }
        Log::error("RenderSocialReelJob failed for Reel ID {$this->reelId}: " . $exception->getMessage());
    }
}
