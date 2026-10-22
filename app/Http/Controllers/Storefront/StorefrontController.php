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
            'store' => StoreSettings::current(),
            'seoTitle' => $category->seo_title ?: "{$category->name} | Dhansree",
            'seoDescription' => $category->seo_description ?: $category->description ?: "Shop our latest {$category->name} collection.",
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

        $page = \App\Models\ContentPage::with(['contentPageSections' => fn($q) => $q->orderBy('position')])
            ->where('key', 'FAQ')
            ->where('is_visible', true)
            ->first();

        return view('store.pages.faq', [
            'page' => $page,
            'faqGroups' => $faqGroups,
            'store' => StoreSettings::current(),
            'isSmartLivingHub' => false,
            'seoTitle' => 'Frequently Asked Questions (FAQ) | Dhansree',
            'seoDescription' => 'Find instant answers to questions regarding orders, payments, shipping times, returns, exchanges, and sizing for Dhansree Trends.',
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
