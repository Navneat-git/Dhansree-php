<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\StoreSettings;
use App\Services\Catalog\SeoRedirectService;
use App\Services\Storefront\CatalogBrowsingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function __construct(
        protected CatalogBrowsingService $catalogService,
        protected SeoRedirectService $seoRedirectService,
        protected \App\Services\Review\ReviewService $reviewService,
    ) {}

    /**
     * Hub 1: Women's Fashion & Ethnic Kurties Homepage
     */
    public function home(): View
    {
        $data = $this->catalogService->getWomenFashionHomeData();

        return view('store.home', [
            'categories' => $data['categories'],
            'featuredProducts' => $data['featuredProducts'],
            'smartLivingCategories' => $data['smartLivingCategories'],
            'banners' => $data['banners'],
            'store' => $data['store'],
            'isSmartLivingHub' => false,
            'seoTitle' => 'Dhansree Trends — Ethnic Elegance. Timeless You.',
            'seoDescription' => 'Explore premium handcrafted women kurties, suit sets, and elegant ethnic co-ords. Pure fabrics, exquisite embroidery, and fast shipping across India.',
        ]);
    }

    /**
     * Hub 2: Smart Living & Modern Tech Essentials Dedicated Landing Page
     */
    public function smartLiving(): View
    {
        $data = $this->catalogService->getSmartLivingHubData();

        return view('store.smart-living', [
            'categories' => $data['categories'],
            'products' => $data['products'],
            'womenCategories' => $data['womenCategories'],
            'featuredWomenProduct' => $data['featuredWomenProduct'] ?? null,
            'store' => $data['store'],
            'isSmartLivingHub' => true,
            'seoTitle' => 'Smart Living & Tech Essentials | Dhansree Traders Direct',
            'seoDescription' => 'Discover innovative smart gadgets, fast chargers, multi-cables, and home essentials. Quality-tested tech accessories at direct customer prices.',
        ]);
    }

    /**
     * Category collection page
     */
    public function category(string $slug, Request $request): View|RedirectResponse
    {
        // 1. Check for SEO 301 redirects
        $redirect = $this->seoRedirectService->findRedirect('/' . $slug);
        if ($redirect) {
            return redirect()->to($redirect->destination_path, $redirect->status_code ?? 301);
        }

        $data = $this->catalogService->getCategoryPageData($slug, $request->all());
        $category = $data['category'];

        return view('store.category', [
            'category' => $category,
            'products' => $data['products'],
            'isSmartLivingHub' => $data['isSmartLiving'],
            'filters' => $data['filters'],
            'availableOptions' => $data['availableOptions'] ?? [],
            'selectedOptions' => $data['selectedOptions'] ?? [],
            'store' => StoreSettings::current(),
            'seoTitle' => $category->seo_title ?: "{$category->name} | Dhansree",
            'seoDescription' => $category->seo_description ?: $category->description ?: "Shop our latest {$category->name} collection.",
        ]);
    }

    /**
     * Curated collection page (gathers products from multiple categories)
     */
    public function collection(string $slug, Request $request): View|RedirectResponse
    {
        // 1. Check for SEO 301 redirects
        $redirect = $this->seoRedirectService->findRedirect('/collections/' . $slug);
        if ($redirect) {
            return redirect()->to($redirect->destination_path, $redirect->status_code ?? 301);
        }

        $data = $this->catalogService->getCollectionPageData($slug, $request->all());
        $collection = $data['collection'];

        return view('store.collection', [
            'collection' => $collection,
            'products' => $data['products'],
            'isSmartLivingHub' => $data['isSmartLiving'],
            'filters' => $data['filters'],
            'availableOptions' => $data['availableOptions'] ?? [],
            'selectedOptions' => $data['selectedOptions'] ?? [],
            'store' => StoreSettings::current(),
            'seoTitle' => $collection->seo_title ?: "{$collection->name} | Dhansree",
            'seoDescription' => $collection->seo_description ?: $collection->intro ?: "Shop our latest {$collection->name} collection.",
        ]);
    }

    /**
     * Product detail page
     */
    public function product(string $slug): View|RedirectResponse
    {
        // 1. Check for SEO 301 redirects
        $redirect = $this->seoRedirectService->findRedirect('/product/' . $slug);
        if ($redirect) {
            return redirect()->to($redirect->destination_path, $redirect->status_code ?? 301);
        }

        $data = $this->catalogService->getProductDetailPageData($slug);
        $product = $data['product'];

        $reviewsData = $this->reviewService->publicForProduct($product->id, 1, 10);

        return view('store.product', [
            'product' => $product,
            'relatedProducts' => $data['relatedProducts'],
            'sizeChart' => $data['sizeChart'] ?? null,
            'isSmartLivingHub' => $data['isSmartLiving'],
            'store' => $data['store'],
            'reviewSummary' => $reviewsData['summary'],
            'initialReviews' => $reviewsData['reviews'],
            'reviewsTotal' => $reviewsData['total_items'],
            'seoTitle' => $product->seo_title ?: "{$product->name} | Dhansree",
            'seoDescription' => $product->seo_description ?: $product->short_description ?: $product->description,
        ]);
    }

    /**
     * Search page
     */
    public function search(Request $request): View
    {
        $query = trim((string) $request->input('q', ''));
        $products = !empty($query)
            ? $this->catalogService->search($query, $request->all())
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 16);

        return view('store.search', [
            'query' => $query,
            'products' => $products,
            'isSmartLivingHub' => false,
            'store' => StoreSettings::current(),
            'seoTitle' => $query !== '' ? "Search results for '{$query}' | Dhansree" : 'Search Products | Dhansree',
        ]);
    }

    /**
     * Render Content Page from database
     */
    protected function renderContentPage(string $key): View
    {
        $page = \App\Models\ContentPage::with(['contentPageSections' => fn($q) => $q->orderBy('position')])
            ->where('key', $key)
            ->where('is_visible', true)
            ->firstOrFail();

        return view('store.pages.content', [
            'page' => $page,
            'store' => StoreSettings::current(),
            'isSmartLivingHub' => false,
            'seoTitle' => $page->seo_title ?: "{$page->title} | Dhansree",
            'seoDescription' => $page->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) ($page->intro ?? '')), 160),
        ]);
    }

    /**
     * Static Policy and Info Pages
     */
    public function about(): View
    {
        return $this->renderContentPage('ABOUT');
    }

    public function contact(): View
    {
        return $this->renderContentPage('CONTACT');
    }

    public function faq(): View
    {
        $faqGroups = \App\Models\FaqGroup::with(['faqEntries' => fn($q) => $q->where('is_visible', true)->orderBy('position')])
            ->where('is_visible', true)
            ->where('show_on_faq_page', true)
            ->orderBy('position')
            ->get();

        $ungroupedEntries = \App\Models\FaqEntry::where('is_visible', true)
            ->whereNull('group_id')
            ->orderBy('position')
            ->get();

        $page = \App\Models\ContentPage::with(['contentPageSections' => fn($q) => $q->orderBy('position')])
            ->where('key', 'FAQ')
            ->where('is_visible', true)
            ->first();

        return view('store.pages.faq', [
            'page' => $page,
            'faqGroups' => $faqGroups,
            'ungroupedEntries' => $ungroupedEntries,
            'store' => StoreSettings::current(),
            'isSmartLivingHub' => false,
            'seoTitle' => 'Frequently Asked Questions (FAQ) | Dhansree',
            'seoDescription' => 'Find instant answers to questions regarding orders, payments, shipping times, returns, exchanges, and sizing for Dhansree Trends.',
        ]);
    }

    public function sitemap(): Response
    {
        $products = \App\Models\Product::where('status', 'ACTIVE')->orderBy('updated_at', 'desc')->get();
        $categories = \App\Models\Category::where('is_visible', true)->whereNull('archived_at')->orderBy('position')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        // Core Static Pages
        $staticRoutes = [
            ['url' => url('/'), 'priority' => '1.0', 'freq' => 'daily'],
            ['url' => url('/smart-living'), 'priority' => '0.9', 'freq' => 'daily'],
            ['url' => url('/faq'), 'priority' => '0.7', 'freq' => 'weekly'],
            ['url' => url('/about'), 'priority' => '0.6', 'freq' => 'monthly'],
            ['url' => url('/contact'), 'priority' => '0.6', 'freq' => 'monthly'],
            ['url' => url('/privacy-policy'), 'priority' => '0.5', 'freq' => 'monthly'],
            ['url' => url('/terms-of-service'), 'priority' => '0.5', 'freq' => 'monthly'],
            ['url' => url('/returns-and-exchanges'), 'priority' => '0.5', 'freq' => 'monthly'],
            ['url' => url('/shipping-policy'), 'priority' => '0.5', 'freq' => 'monthly'],
        ];

        foreach ($staticRoutes as $r) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($r['url']) . "</loc>\n";
            $xml .= '    <changefreq>' . $r['freq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $r['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        // Categories
        foreach ($categories as $cat) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars(url('/' . $cat->slug)) . "</loc>\n";
            $xml .= '    <lastmod>' . ($cat->updated_at ? $cat->updated_at->toAtomString() : now()->toAtomString()) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        // Active Products
        foreach ($products as $prod) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars(url('/product/' . $prod->slug)) . "</loc>\n";
            $xml .= '    <lastmod>' . ($prod->updated_at ? $prod->updated_at->toAtomString() : now()->toAtomString()) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.9</priority>\n";
            if ($prod->primary_image_url) {
                $xml .= "    <image:image>\n";
                $xml .= '      <image:loc>' . htmlspecialchars($prod->primary_image_url) . "</image:loc>\n";
                $xml .= '      <image:title>' . htmlspecialchars($prod->name) . "</image:title>\n";
                $xml .= "    </image:image>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    public function robots(): Response
    {
        $txt = "User-agent: *\n";
        $txt .= "Allow: /\n";
        $txt .= "Disallow: /admin/\n";
        $txt .= "Disallow: /checkout\n";
        $txt .= "Disallow: /account/\n";
        $txt .= "Disallow: /order-confirmed/\n\n";
        $txt .= 'Sitemap: ' . url('/sitemap.xml') . "\n";

        return response($txt, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function privacyPolicy(): View
    {
        return $this->renderContentPage('PRIVACY_POLICY');
    }

    public function returnPolicy(): View
    {
        return $this->renderContentPage('RETURN_POLICY');
    }

    public function shippingPolicy(): View
    {
        return $this->renderContentPage('SHIPPING_POLICY');
    }

    public function terms(): View
    {
        return $this->renderContentPage('TERMS');
    }
}
