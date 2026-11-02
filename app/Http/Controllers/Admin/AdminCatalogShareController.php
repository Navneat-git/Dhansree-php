<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\Product;
use App\Support\Rbac\Permissions;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCatalogShareController extends Controller
{
    /**
     * Display the Women's Fashion Catalog Share & WhatsApp Hub.
     */
    public function index(Request $request): View
    {
        $admin = auth('admin')->user();
        if (!$admin || (!$admin->hasSuperAdmin() && !$admin->hasPermission(Permissions::PRODUCT_VIEW))) {
            abort(403, 'Unauthorized.');
        }

        // Active Categories for Women Fashion & Ethnic Wear
        $categories = Category::query()
            ->whereNull('archived_at')
            ->where('is_visible', true)
            ->orderBy('position')
            ->get(['id', 'name', 'slug']);

        // Active Collections (e.g., Women Fashion)
        $collections = Collection::query()
            ->where('is_visible', true)
            ->orderBy('position')
            ->get(['id', 'name', 'slug', 'heading']);

        // Active Products (recent active catalog items)
        $products = Product::query()
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->take(60)
            ->get(['id', 'name', 'slug']);

        // Active Promotional Coupons
        $coupons = Coupon::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get(['id', 'code', 'type', 'value', 'description']);

        // Base URL for production customer links
        $storeBaseUrl = config('store.url', 'https://dhansree.com');
        if (empty($storeBaseUrl) || str_contains($storeBaseUrl, 'localhost')) {
            $storeBaseUrl = 'https://dhansree.com';
        }

        return view('admin.catalog.share', [
            'categories' => $categories,
            'collections' => $collections,
            'products' => $products,
            'coupons' => $coupons,
            'storeBaseUrl' => rtrim($storeBaseUrl, '/'),
        ]);
    }
}
