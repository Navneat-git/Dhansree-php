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
    public function index(): View
    {
        $cart = $this->cartService->getCart();

        return view('store.cart', [
            'cart' => $cart,
            'store' => StoreSettings::current(),
            'isSmartLivingHub' => false,
            'seoTitle' => 'Your Shopping Bag | Dhansree',
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
