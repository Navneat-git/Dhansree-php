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
                $this->feedService->generateMetaCatalogCsv();
                $message = 'Meta Catalog Feed (meta_catalog.csv) generated successfully.';
            } elseif ($type === 'google') {
                $this->feedService->generateGoogleMerchantCsv();
                $message = 'Google Merchant Center Feed (google_merchant.csv) generated successfully.';
            } else {
                $result = $this->feedService->generateAllFeeds();
                $message = 'Both Meta Business and Google Merchant Center feeds generated and placed in the public folder.';
            }

            return redirect()->route('admin.feeds.index')->with('success', $message);
        } catch (Throwable $e) {
            return redirect()->route('admin.feeds.index')->with('error', 'Feed generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV feed file directly.
     */
    public function download(string $type): BinaryFileResponse|RedirectResponse
    {
        $filename = ($type === 'google')
            ? ProductFeedService::GOOGLE_FILENAME
            : ProductFeedService::META_FILENAME;

        $path = public_path('feeds/' . $filename);

        if (!file_exists($path)) {
            if ($type === 'google') {
                $this->feedService->generateGoogleMerchantCsv();
            } else {
                $this->feedService->generateMetaCatalogCsv();
            }
        }

        if (!file_exists($path)) {
            return redirect()->route('admin.feeds.index')->with('error', 'Feed file could not be generated.');
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
