<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StoreSettings;
use App\Services\Storefront\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(
        protected WishlistService $wishlistService
    ) {}

    /**
     * Show customer/guest wishlist view.
     */
    public function index(): View
    {
        $customer = Auth::guard('customer')->user();
        $products = $this->wishlistService->getWishlistProducts($customer?->id);

        return view('store.wishlist', [
            'products' => $products,
            'store' => StoreSettings::current(),
            'isSmartLivingHub' => false,
            'seoTitle' => 'My Wishlist | Dhansree',
            'seoDescription' => 'Saved products in your Dhansree Wishlist. Quick add to shopping bag or view details.',
        ]);
    }

    /**
     * Return wishlist IDs and count as JSON for frontend/Alpine.js.
     */
    public function ids(): JsonResponse
    {
        $customer = Auth::guard('customer')->user();
        $ids = $this->wishlistService->getWishlistIds($customer?->id);

        return response()->json([
            'success' => true,
            'count' => count($ids),
            'ids' => $ids,
        ]);
    }

    /**
     * Toggle a product in wishlist.
     */
    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $customer = Auth::guard('customer')->user();
        $productId = (int) $validated['product_id'];

        $result = $this->wishlistService->toggleItem($productId, $customer?->id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'added' => $result['added'],
                'count' => $result['count'],
                'ids' => $result['ids'],
                'message' => $result['added'] ? 'Added to wishlist' : 'Removed from wishlist',
            ]);
        }

        return back()->with('success', $result['added'] ? 'Product added to wishlist.' : 'Product removed from wishlist.');
    }

    /**
     * Remove a product from wishlist.
     */
    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
        ]);

        $customer = Auth::guard('customer')->user();
        $productId = (int) $validated['product_id'];

        $result = $this->wishlistService->removeItem($productId, $customer?->id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'count' => $result['count'],
                'ids' => $result['ids'],
                'message' => 'Product removed from wishlist',
            ]);
        }

        return back()->with('success', 'Product removed from wishlist.');
    }
}
