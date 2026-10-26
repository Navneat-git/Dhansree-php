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
        $path = public_path('feeds/' . ProductFeedService::META_FILENAME);

        if (file_exists($path)) {
            $content = (string) file_get_contents($path);
        } else {
            $content = $this->feedService->generateMetaCatalogCsv();
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
        $path = public_path('feeds/' . ProductFeedService::GOOGLE_FILENAME);

        if (file_exists($path)) {
            $content = (string) file_get_contents($path);
        } else {
            $content = $this->feedService->generateGoogleMerchantCsv();
        }

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="google_merchant.csv"',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
