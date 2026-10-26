<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Feeds\ProductFeedService;
use Illuminate\Console\Command;

class GenerateProductFeedsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feeds:generate {--type=all : Feed type: all, meta, or google}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and update CSV product feeds for Meta Business (Facebook/Instagram) and Google Merchant Center';

    /**
     * Execute the console command.
     */
    public function handle(ProductFeedService $feedService): int
    {
        $type = $this->option('type') ?: 'all';

        $this->info("Generating {$type} product feed(s)...");

        if ($type === 'meta') {
            $feedService->generateMetaCatalogCsv();
            $this->info("✓ Meta Catalog Feed generated at: " . public_path('feeds/' . ProductFeedService::META_FILENAME));
        } elseif ($type === 'google') {
            $feedService->generateGoogleMerchantCsv();
            $this->info("✓ Google Merchant Center Feed generated at: " . public_path('feeds/' . ProductFeedService::GOOGLE_FILENAME));
        } else {
            $results = $feedService->generateAllFeeds();
            $this->info("✓ Meta Catalog Feed: {$results['meta']['url']} ({$results['meta']['size_formatted']})");
            $this->info("✓ Google Merchant Feed: {$results['google']['url']} ({$results['google']['size_formatted']})");
        }

        $this->info("Feeds are placed in the public folder and accessible for scheduled fetching.");

        return Command::SUCCESS;
    }
}
