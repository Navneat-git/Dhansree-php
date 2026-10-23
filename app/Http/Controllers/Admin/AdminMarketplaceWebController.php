<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MarketplaceTemplate;
use App\Models\Product;
use App\Models\ProductMarketplaceValue;
use App\Models\VariantMarketplaceValue;
use App\Services\Marketplace\MarketplaceListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminMarketplaceWebController extends Controller
{
    public function __construct(
        private readonly MarketplaceListingService $marketplaceService
    ) {}

    /**
     * Show marketplace listings dashboard.
     */
    public function listings(Request $request): View
    {
        $templates = $this->marketplaceService->getTemplates();

        $selectedTemplateId = (int) $request->input('template', $templates->first()?->id);
        $selectedTemplate = $templates->firstWhere('id', $selectedTemplateId) ?? $templates->first();

        $filters = [
            'category_id' => $request->input('category_id'),
            'search' => $request->input('q'),
        ];

        $listingData = $selectedTemplate
            ? $this->marketplaceService->resolveListing($selectedTemplate->id, $filters)
            : ['columns' => collect(), 'rows' => [], 'total_rows' => 0, 'ready_count' => 0];

        $categories = Category::orderBy('name')->get();

        return view('admin.marketplace.listings', [
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate,
            'columns' => $listingData['columns'],
            'rows' => $listingData['rows'],
            'totalRows' => $listingData['total_rows'],
            'readyCount' => $listingData['ready_count'],
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Export listing to CSV for the marketplace.
     */
    public function export(Request $request): Response
    {
        $templates = $this->marketplaceService->getTemplates();
        $templateId = (int) $request->input('template', $templates->first()?->id);
        $template = $templates->firstWhere('id', $templateId) ?? $templates->firstOrFail();

        $filters = [
            'category_id' => $request->input('category_id'),
            'search' => $request->input('q'),
        ];

        $csv = $this->marketplaceService->generateCsv($template->id, $filters);
        $date = now()->format('Y-m-d');
        $platformSlug = strtolower($template->platform);
        $filename = "dhansree-{$platformSlug}-listing-{$date}.csv";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Get TSV for clipboard copy.
     */
    public function copyTsv(Request $request): Response
    {
        $templates = $this->marketplaceService->getTemplates();
        $templateId = (int) $request->input('template', $templates->first()?->id);
        $template = $templates->firstWhere('id', $templateId) ?? $templates->firstOrFail();

        $filters = [
            'category_id' => $request->input('category_id'),
            'search' => $request->input('q'),
        ];

        $tsv = $this->marketplaceService->generateTsv($template->id, $filters);

        return response($tsv, 200, [
            'Content-Type' => 'text/tab-separated-values; charset=UTF-8',
        ]);
    }

    /**
     * Save product custom marketplace values.
     */
    public function saveProductValues(Request $request, Product $product): RedirectResponse
    {
        $templateId = (int) $request->input('template_id');
        $productValues = $request->input('product_values', []);
        $variantValues = $request->input('variant_values', []);

        // Save product-scoped values
        foreach ($productValues as $columnId => $value) {
            if ($value !== null && $value !== '') {
                ProductMarketplaceValue::updateOrCreate(
                    ['product_id' => $product->id, 'column_id' => (int) $columnId],
                    ['value' => (string) $value]
                );
            } else {
                ProductMarketplaceValue::where('product_id', $product->id)
                    ->where('column_id', (int) $columnId)
                    ->delete();
            }
        }

        // Save variant-scoped values
        foreach ($variantValues as $variantId => $cols) {
            foreach ($cols as $columnId => $value) {
                if ($value !== null && $value !== '') {
                    VariantMarketplaceValue::updateOrCreate(
                        ['variant_id' => (int) $variantId, 'column_id' => (int) $columnId],
                        ['value' => (string) $value]
                    );
                } else {
                    VariantMarketplaceValue::where('variant_id', (int) $variantId)
                        ->where('column_id', (int) $columnId)
                        ->delete();
                }
            }
        }

        return back()->with('success', 'Marketplace listing attributes saved.');
    }
}
