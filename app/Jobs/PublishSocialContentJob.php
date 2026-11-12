<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SocialPublishingQueue;
use App\Services\SocialCommerce\MetaPublishingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishSocialContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $queueItemId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(MetaPublishingService $publishingService): void
    {
        $item = SocialPublishingQueue::find($this->queueItemId);
        if (!$item) {
            Log::warning("PublishSocialContentJob: Queue item #{$this->queueItemId} not found.");
            return;
        }

        Log::info("Starting social publishing for queue item #{$item->id} ('{$item->title}')");
        $publishingService->publishQueueItem($item);
        Log::info("Finished social publishing for queue item #{$item->id}. Status: {$item->status}");
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $item = SocialPublishingQueue::find($this->queueItemId);
        if ($item) {
            $item->update([
                'status' => 'FAILED',
                'error_message' => 'Job execution error: ' . $exception->getMessage(),
            ]);
        }
        Log::error("PublishSocialContentJob failed for queue item #{$this->queueItemId}: " . $exception->getMessage());
    }
}
