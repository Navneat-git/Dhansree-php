<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Feeds\ProductFeedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AdminProductFeedWebController extends Controller
{
    public function __construct(
        private readonly ProductFeedService $feedService
    ) {}

    /**
     * Display product feeds management screen.
     */
    public function index(): View
    {
        $status = $this->feedService->getFeedStatus();

        $activeProductsCount = Product::where('status', \App\Enums\ProductStatus::ACTIVE)->count();
        $activeVariantsCount = ProductVariant::whereNull('archived_at')
            ->whereHas('product', fn($q) => $q->where('status', \App\Enums\ProductStatus::ACTIVE))
            ->count();

        return view('admin.feeds.index', [
            'meta' => $status['meta'],
            'google' => $status['google'],
            'publicDirectoryWritable' => $status['public_directory_writable'] ?? true,
            'activeProductsCount' => $activeProductsCount,
            'activeVariantsCount' => $activeVariantsCount,
        ]);
    }

    /**
     * Generate or regenerate feed files.
     */
    public function generate(Request $request): RedirectResponse
    {
        $type = $request->input('type', 'all');

        try {
            if ($type === 'meta') {
                $metaContent = $this->feedService->generateMetaCatalogCsv(save: false);
                $result = $this->feedService->saveFeed(
                    ProductFeedService::META_FILENAME,
                    $metaContent,
                    ['meta-catalog.csv']
                );
                $wrotePublic = $result['wrote_to_public'] ?? true;
                $message = $wrotePublic
                    ? 'Meta Catalog Feed (meta_catalog.csv) generated and placed in the public folder.'
                    : 'Meta Catalog Feed generated and saved to application storage. Feed is live at /feeds/meta_catalog.csv. (Optional: run "chmod -R 777 public/feeds" on server for direct public static caching).';
            } elseif ($type === 'google') {
                $googleContent = $this->feedService->generateGoogleMerchantCsv(save: false);
                $result = $this->feedService->saveFeed(
                    ProductFeedService::GOOGLE_FILENAME,
                    $googleContent,
                    ['google-merchant.csv']
                );
                $wrotePublic = $result['wrote_to_public'] ?? true;
                $message = $wrotePublic
                    ? 'Google Merchant Center Feed (google_merchant.csv) generated and placed in the public folder.'
                    : 'Google Merchant Center Feed generated and saved to application storage. Feed is live at /feeds/google_merchant.csv. (Optional: run "chmod -R 777 public/feeds" on server for direct public static caching).';
            } else {
                $results = $this->feedService->generateAllFeeds();
                $wrotePublic = ($results['meta']['wrote_to_public'] ?? true) && ($results['google']['wrote_to_public'] ?? true);
                $message = $wrotePublic
                    ? 'Both Meta Business and Google Merchant Center feeds generated and placed in the public folder.'
                    : 'Both Meta Business and Google Merchant Center feeds generated and saved to application storage. Feeds are live at /feeds/meta_catalog.csv and /feeds/google_merchant.csv. (Optional: run "chmod -R 777 public/feeds" on server for direct public static caching).';
            }

            return redirect()->route('admin.feeds.index')->with('success', $message);
        } catch (Throwable $e) {
            return redirect()->route('admin.feeds.index')->with('error', 'Feed generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV feed file directly.
     */
    public function download(string $type): BinaryFileResponse|\Illuminate\Http\Response|RedirectResponse
    {
        $filename = ($type === 'google')
            ? ProductFeedService::GOOGLE_FILENAME
            : ProductFeedService::META_FILENAME;

        $path = $this->feedService->resolveFeedFilePath($filename);

        if (!$path || !file_exists($path)) {
            if ($type === 'google') {
                $this->feedService->generateGoogleMerchantCsv();
            } else {
                $this->feedService->generateMetaCatalogCsv();
            }
            $path = $this->feedService->resolveFeedFilePath($filename);
        }

        if ($path && file_exists($path) && is_readable($path)) {
            return response()->download($path, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        // Stream generated in-memory fallback
        $content = ($type === 'google')
            ? $this->feedService->generateGoogleMerchantCsv(save: false)
            : $this->feedService->generateMetaCatalogCsv(save: false);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
