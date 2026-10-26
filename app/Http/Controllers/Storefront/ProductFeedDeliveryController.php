<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Feeds\ProductFeedService;
use Illuminate\Http\Response;

class ProductFeedDeliveryController extends Controller
{
    public function __construct(
        private readonly ProductFeedService $feedService
    ) {}

    /**
     * Serve Meta Business Catalog Feed CSV.
     */
    public function metaFeed(): Response
    {
        $path = $this->feedService->resolveFeedFilePath(ProductFeedService::META_FILENAME);

        if ($path && file_exists($path) && is_readable($path)) {
            $content = (string) file_get_contents($path);
        } else {
            $content = $this->feedService->generateMetaCatalogCsv(save: true);
        }

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="meta_catalog.csv"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Serve Google Merchant Center Feed CSV.
     */
    public function googleFeed(): Response
    {
        $path = $this->feedService->resolveFeedFilePath(ProductFeedService::GOOGLE_FILENAME);

        if ($path && file_exists($path) && is_readable($path)) {
            $content = (string) file_get_contents($path);
        } else {
            $content = $this->feedService->generateGoogleMerchantCsv(save: true);
        }

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="google_merchant.csv"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
