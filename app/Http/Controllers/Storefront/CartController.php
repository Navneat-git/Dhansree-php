<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StoreSettings;
use App\Services\Storefront\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
    ) {}

    /**
     * Show full cart page.
     */
    public function index(Request $request): View
    {
        // 1. Explicit query parameter if passed (?return_url=...)
        $explicitReturnUrl = $request->query('return_url');
        if ($explicitReturnUrl) {
            $parsedReturn = parse_url($explicitReturnUrl);
            $parsedHost = $parsedReturn['host'] ?? null;
            $currentHost = $request->getHost();

            if (!$parsedHost || $parsedHost === $currentHost) {
                $path = $parsedReturn['path'] ?? '/';
                if (!str_starts_with($path, '/cart') && !str_starts_with($path, '/checkout')) {
                    session(['continue_shopping_url' => $explicitReturnUrl]);
                }
            }
        }

        // 2. Capture last visited internal shopping page from HTTP referer header
        $referer = $request->headers->get('referer');
        if ($referer) {
            $parsedReferer = parse_url($referer);
            $parsedHost = $parsedReferer['host'] ?? null;
            $currentHost = $request->getHost();

            // Only capture internal referrals from the same application host
            if (!$parsedHost || $parsedHost === $currentHost) {
                $path = $parsedReferer['path'] ?? '/';

                // Do not overwrite if referer is the cart or checkout itself
                if (!str_starts_with($path, '/cart') && !str_starts_with($path, '/checkout')) {
                    session(['continue_shopping_url' => $referer]);
                }
            }
        }

        $continueShoppingUrl = session('continue_shopping_url') ?: url('/');
        $cart = $this->cartService->getCart();

        return view('store.cart', [
            'cart' => $cart,
            'store' => StoreSettings::current(),
            'isSmartLivingHub' => false,
            'seoTitle' => 'Your Shopping Bag | Dhansree',
            'continueShoppingUrl' => $continueShoppingUrl,
        ]);
    }

    /**
     * Return live cart JSON state for Alpine.js drawers & widgets.
     */
    public function data(): JsonResponse
    {
        return response()->json($this->cartService->getCart());
    }

    /**
     * Add item to cart.
     */
    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $variantId = (int) $validated['variant_id'];
        $quantity = (int) ($validated['quantity'] ?? 1);

        try {
            $cart = $this->cartService->addItem($variantId, $quantity);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item added to your shopping bag!',
                    'cart' => $cart,
                ]);
            }

            return back()->with('success', 'Item added to your shopping bag!');
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update quantity of an item in the cart.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        $cart = $this->cartService->updateQuantity(
            (int) $validated['variant_id'],
            (int) $validated['quantity']
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
            ]);
        }

        return back();
    }

    /**
     * Remove an item from the cart.
     */
    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
        ]);

        $cart = $this->cartService->removeItem((int) $validated['variant_id']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart' => $cart,
            ]);
        }

        return back()->with('success', 'Item removed from bag.');
    }

    /**
     * Clear all items in cart.
     */
    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->cartService->clear();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'cart' => $this->cartService->getCart(),
            ]);
        }

        return back()->with('success', 'Bag has been emptied.');
    }
}
