<?php

declare(strict_types=1);

namespace App\Services\Storefront;

use App\Models\ProductVariant;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

class CartService
{
    public const SESSION_KEY = 'dhansree_cart';

    /**
     * Get the full cart details, items, subtotals, and shipping calculation.
     *
     * @return array{
     *     items: array<int, array{
     *         variant_id: int,
     *         product_id: int,
     *         product_name: string,
     *         product_slug: string,
     *         variant_title: string,
     *         sku: string,
     *         image_url: string|null,
     *         price_paise: int,
     *         price_rupees: float,
     *         mrp_paise: int,
     *         mrp_rupees: float,
     *         quantity: int,
     *         line_total_paise: int,
     *         line_total_rupees: float,
     *         stock_on_hand: int
     *     }>,
     *     total_quantity: int,
     *     subtotal_paise: int,
     *     subtotal_rupees: float,
     *     free_shipping_threshold_paise: int,
     *     free_shipping_threshold_rupees: float,
     *     remaining_for_free_shipping_paise: int,
     *     remaining_for_free_shipping_rupees: float,
     *     free_shipping_progress_percentage: int,
     *     is_free_shipping: bool,
     *     shipping_paise: int,
     *     shipping_rupees: float,
     *     total_paise: int,
     *     total_rupees: float
     * }
     */
    public function getCart(): array
    {
        $rawCart = Session::get(self::SESSION_KEY, []);
        $variantIds = array_keys($rawCart);

        if (empty($variantIds)) {
            return $this->formatEmptyCart();
        }

        $variants = ProductVariant::with(['product.images', 'product.primaryImage', 'optionValues', 'inventoryLevels'])
            ->whereIn('id', $variantIds)
            ->whereNull('archived_at')
            ->get()
            ->keyBy('id');

        $items = [];
        $subtotalPaise = 0;
        $totalQuantity = 0;

        $store = StoreSettings::current();
        $defaultCoupon = $store->getDefaultCoupon();

        foreach ($rawCart as $variantId => $cartRow) {
            $variant = $variants->get($variantId);
            if (!$variant || !$variant->product || $variant->product->status->value === 'ARCHIVED') {
                unset($rawCart[$variantId]);
                continue;
            }

            $qty = max(1, (int) ($cartRow['quantity'] ?? 1));
            $availableStock = $variant->stock_on_hand;

            // Cap at available stock if stock > 0
            if ($availableStock > 0 && $qty > $availableStock) {
                $qty = $availableStock;
            }

            $pricePaise = (int) $variant->price_paise;
            $priceRupees = $pricePaise / 100;
            $mrpPaise = (int) $variant->mrp_paise;
            $mrpRupees = $mrpPaise > 0 ? ($mrpPaise / 100) : null;

            // Calculate discounted price using store coupon rules (same as product listing & PDP)
            $calc = $store->calculateDiscountedPrice($priceRupees, $mrpRupees);
            $discountedPriceRupees = (float) $calc['discounted_price'];
            $discountedPricePaise = (int) round($discountedPriceRupees * 100);

            $lineTotalPaise = $pricePaise * $qty;
            $lineTotalRupees = $lineTotalPaise / 100;

            $lineDiscountedTotalPaise = $discountedPricePaise * $qty;
            $lineDiscountedTotalRupees = $lineDiscountedTotalPaise / 100;

            $lineMrpTotalPaise = $mrpPaise > 0 ? ($mrpPaise * $qty) : $lineTotalPaise;
            $lineMrpTotalRupees = $lineMrpTotalPaise / 100;

            $hasDiscount = ($discountedPriceRupees < $priceRupees) || ($mrpPaise > $pricePaise);

            $subtotalPaise += $lineTotalPaise;
            $totalQuantity += $qty;

            // Compute variant title
            $optionNames = $variant->optionValues->pluck('value')->all();
            $variantTitle = !empty($optionNames) ? implode(' / ', $optionNames) : 'Standard';

            // Pick image
            $image = $variant->product->primary_image_url ?? $variant->product->images->first()?->url;

            $items[] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product->id,
                'product_name' => $variant->product->name,
                'product_slug' => $variant->product->slug,
                'variant_title' => $variantTitle,
                'sku' => $variant->sku,
                'image_url' => $image,
                'price_paise' => $pricePaise,
                'price_rupees' => $priceRupees,
                'mrp_paise' => $mrpPaise,
                'mrp_rupees' => $mrpRupees,
                'discounted_price_paise' => $discountedPricePaise,
                'discounted_price_rupees' => $discountedPriceRupees,
                'quantity' => $qty,
                'line_total_paise' => $lineTotalPaise,
                'line_total_rupees' => $lineTotalRupees,
                'line_discounted_total_paise' => $lineDiscountedTotalPaise,
                'line_discounted_total_rupees' => $lineDiscountedTotalRupees,
                'line_mrp_total_paise' => $lineMrpTotalPaise,
                'line_mrp_total_rupees' => $lineMrpTotalRupees,
                'discount_percentage' => $calc['discount_percentage'],
                'has_discount' => $hasDiscount,
                'stock_on_hand' => $availableStock,
            ];
        }

        Session::put(self::SESSION_KEY, $rawCart);

        // Auto-applied default coupon calculation for cart
        $discountPaise = 0;
        if ($defaultCoupon && $subtotalPaise > 0) {
            if ($defaultCoupon->type === \App\Enums\CouponType::PERCENTAGE) {
                $discountPaise = (int) round(($subtotalPaise * (int) $defaultCoupon->value) / 10000);
                if ($defaultCoupon->max_discount_paise !== null && $discountPaise > $defaultCoupon->max_discount_paise) {
                    $discountPaise = $defaultCoupon->max_discount_paise;
                }
            } elseif ($defaultCoupon->type === \App\Enums\CouponType::FIXED_AMOUNT) {
                $discountPaise = min((int) $defaultCoupon->value, $subtotalPaise);
            }
        }
        $effectiveSubtotalPaise = max(0, $subtotalPaise - $discountPaise);

        $freeShippingThresholdPaise = $store->free_shipping_threshold_paise !== null ? (int) $store->free_shipping_threshold_paise : null;
        $flatShippingPaise = (int) ($store->shipping_flat_rate_paise ?? 5000);

        $isFreeShipping = ($freeShippingThresholdPaise !== null && $subtotalPaise >= $freeShippingThresholdPaise);
        $shippingPaise = $isFreeShipping || $subtotalPaise === 0 ? 0 : $flatShippingPaise;
        $totalPaise = $effectiveSubtotalPaise + $shippingPaise;

        $remainingForFreeShippingPaise = max(0, $freeShippingThresholdPaise - $subtotalPaise);
        $progress = $freeShippingThresholdPaise > 0
            ? (int) min(100, round(($subtotalPaise / $freeShippingThresholdPaise) * 100))
            : 100;

        return [
            'items' => $items,
            'total_quantity' => $totalQuantity,
            'subtotal_paise' => $subtotalPaise,
            'subtotal_rupees' => round($subtotalPaise / 100, 2),
            'discount_paise' => $discountPaise,
            'discount_rupees' => round($discountPaise / 100, 2),
            'effective_subtotal_paise' => $effectiveSubtotalPaise,
            'effective_subtotal_rupees' => round($effectiveSubtotalPaise / 100, 2),
            'coupon_code' => $defaultCoupon?->code,
            'free_shipping_threshold_paise' => $freeShippingThresholdPaise,
            'free_shipping_threshold_rupees' => round($freeShippingThresholdPaise / 100, 2),
            'remaining_for_free_shipping_paise' => $remainingForFreeShippingPaise,
            'remaining_for_free_shipping_rupees' => round($remainingForFreeShippingPaise / 100, 2),
            'free_shipping_progress_percentage' => $progress,
            'is_free_shipping' => $isFreeShipping,
            'shipping_paise' => $shippingPaise,
            'shipping_rupees' => round($shippingPaise / 100, 2),
            'total_paise' => $totalPaise,
            'total_rupees' => round($totalPaise / 100, 2),
        ];
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(int $variantId, int $quantity = 1): array
    {
        if ($quantity <= 0) {
            $quantity = 1;
        }

        $variant = ProductVariant::with(['product', 'inventoryLevels'])
            ->whereNull('archived_at')
            ->findOrFail($variantId);

        if (!$variant->product || $variant->product->status->value !== 'ACTIVE') {
            throw new InvalidArgumentException('This product is not currently available for purchase.');
        }

        $availableStock = $variant->stock_on_hand;
        if ($availableStock <= 0) {
            throw new InvalidArgumentException("Item '{$variant->sku}' is currently out of stock.");
        }

        $rawCart = Session::get(self::SESSION_KEY, []);
        $currentQty = (int) ($rawCart[$variantId]['quantity'] ?? 0);
        $newQty = $currentQty + $quantity;

        if ($newQty > $availableStock) {
            $newQty = $availableStock;
        }

        $rawCart[$variantId] = [
            'variant_id' => $variantId,
            'quantity' => $newQty,
            'added_at' => now()->toISOString(),
        ];

        Session::put(self::SESSION_KEY, $rawCart);

        return $this->getCart();
    }

    /**
     * Update quantity of an item in the cart.
     */
    public function updateQuantity(int $variantId, int $quantity): array
    {
        $rawCart = Session::get(self::SESSION_KEY, []);

        if ($quantity <= 0) {
            unset($rawCart[$variantId]);
        } else {
            $variant = ProductVariant::with('inventoryLevels')->find($variantId);
            if ($variant) {
                $maxStock = $variant->stock_on_hand;
                if ($maxStock > 0 && $quantity > $maxStock) {
                    $quantity = $maxStock;
                }
            }
            $rawCart[$variantId] = [
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ];
        }

        Session::put(self::SESSION_KEY, $rawCart);

        return $this->getCart();
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $variantId): array
    {
        $rawCart = Session::get(self::SESSION_KEY, []);
        unset($rawCart[$variantId]);
        Session::put(self::SESSION_KEY, $rawCart);

        return $this->getCart();
    }

    /**
     * Empty the entire cart.
     */
    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Format of an empty cart.
     */
    private function formatEmptyCart(): array
    {
        $store = StoreSettings::current();
        $freeShippingThresholdPaise = (int) ($store->free_shipping_threshold_paise ?? 99900);

        return [
            'items' => [],
            'total_quantity' => 0,
            'subtotal_paise' => 0,
            'subtotal_rupees' => 0.0,
            'discount_paise' => 0,
            'discount_rupees' => 0.0,
            'effective_subtotal_paise' => 0,
            'effective_subtotal_rupees' => 0.0,
            'coupon_code' => null,
            'free_shipping_threshold_paise' => $freeShippingThresholdPaise,
            'free_shipping_threshold_rupees' => round($freeShippingThresholdPaise / 100, 2),
            'remaining_for_free_shipping_paise' => $freeShippingThresholdPaise,
            'remaining_for_free_shipping_rupees' => round($freeShippingThresholdPaise / 100, 2),
            'free_shipping_progress_percentage' => 0,
            'is_free_shipping' => false,
            'shipping_paise' => 0,
            'shipping_rupees' => 0.0,
            'total_paise' => 0,
            'total_rupees' => 0.0,
        ];
    }
}
