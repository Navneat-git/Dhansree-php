<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminWishlistWebController extends Controller
{
    /**
     * Display wishlist insights: Most wishlisted products & customer wishlists.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'products'); // 'products' or 'customers'
        $search = trim((string) $request->query('search', ''));

        // Query 1: Most wishlisted products
        $productsQuery = Product::with(['category', 'primaryImage', 'variants.inventoryLevels'])
            ->withCount('wishlistItems')
            ->having('wishlist_items_count', '>', 0)
            ->orderByDesc('wishlist_items_count');

        if ($search !== '' && $tab === 'products') {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $mostWishlisted = $productsQuery->paginate(15, ['*'], 'p_page');

        // Query 2: Customers with wishlist items
        $customersQuery = Customer::whereHas('wishlistItems')
            ->with([
                'wishlistItems' => fn($q) => $q->orderByDesc('id')->with(['product.primaryImage', 'product.category'])
            ])
            ->withCount('wishlistItems')
            ->orderByDesc('wishlist_items_count');

        if ($search !== '' && $tab === 'customers') {
            $customersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customerWishlists = $customersQuery->paginate(15, ['*'], 'c_page');

        $totalWishlistItems = WishlistItem::count();
        $totalCustomersWithWishlist = Customer::has('wishlistItems')->count();
        $totalProductsWishlisted = Product::has('wishlistItems')->count();

        return view('admin.catalog.wishlists.index', [
            'tab' => $tab,
            'search' => $search,
            'mostWishlisted' => $mostWishlisted,
            'customerWishlists' => $customerWishlists,
            'totalWishlistItems' => $totalWishlistItems,
            'totalCustomersWithWishlist' => $totalCustomersWithWishlist,
            'totalProductsWishlisted' => $totalProductsWishlisted,
        ]);
    }
}
