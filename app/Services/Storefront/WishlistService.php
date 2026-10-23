<?php

declare(strict_types=1);

namespace App\Services\Storefront;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class WishlistService
{
    public const SESSION_KEY = 'dhansree_wishlist';

    /**
     * Get wishlisted products for customer or guest session.
     *
     * @return Collection<int, Product>
     */
    public function getWishlistProducts(?int $customerId): Collection
    {
        if ($customerId) {
            $productIds = WishlistItem::where('customer_id', $customerId)
                ->orderByDesc('id')
                ->pluck('product_id')
                ->all();
        } else {
            $productIds = Session::get(self::SESSION_KEY, []);
        }

        if (empty($productIds)) {
            return collect();
        }

        return Product::with(['primaryImage', 'variants.inventoryLevels', 'category'])
            ->whereIn('id', $productIds)
            ->where('status', ProductStatus::ACTIVE)
            ->get();
    }

    /**
     * Get list of wishlisted product IDs.
     *
     * @return array<int>
     */
    public function getWishlistIds(?int $customerId): array
    {
        if ($customerId) {
            return WishlistItem::where('customer_id', $customerId)
                ->pluck('product_id')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        $raw = Session::get(self::SESSION_KEY, []);
        return array_values(array_map('intval', (array) $raw));
    }

    /**
     * Toggle a product in wishlist (add if missing, remove if present).
     *
     * @return array{added: bool, count: int, ids: array<int>}
     */
    public function toggleItem(int $productId, ?int $customerId): array
    {
        $added = false;

        if ($customerId) {
            $existing = WishlistItem::where('customer_id', $customerId)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                $existing->delete();
                $added = false;
            } else {
                WishlistItem::create([
                    'customer_id' => $customerId,
                    'product_id' => $productId,
                ]);
                $added = true;
            }

            // Sync session
            $ids = WishlistItem::where('customer_id', $customerId)->pluck('product_id')->map(fn($id) => (int) $id)->all();
            Session::put(self::SESSION_KEY, $ids);
        } else {
            $ids = $this->getWishlistIds(null);
            if (in_array($productId, $ids, true)) {
                $ids = array_values(array_diff($ids, [$productId]));
                $added = false;
            } else {
                $ids[] = $productId;
                $added = true;
            }
            $ids = array_values(array_unique(array_map('intval', $ids)));
            Session::put(self::SESSION_KEY, $ids);
        }

        return [
            'added' => $added,
            'count' => count($ids),
            'ids' => $ids,
        ];
    }

    /**
     * Explicitly remove a product from wishlist.
     *
     * @return array{count: int, ids: array<int>}
     */
    public function removeItem(int $productId, ?int $customerId): array
    {
        if ($customerId) {
            WishlistItem::where('customer_id', $customerId)
                ->where('product_id', $productId)
                ->delete();

            $ids = WishlistItem::where('customer_id', $customerId)->pluck('product_id')->map(fn($id) => (int) $id)->all();
            Session::put(self::SESSION_KEY, $ids);
        } else {
            $ids = $this->getWishlistIds(null);
            $ids = array_values(array_diff($ids, [$productId]));
            Session::put(self::SESSION_KEY, $ids);
        }

        return [
            'count' => count($ids),
            'ids' => $ids,
        ];
    }

    /**
     * Merge guest session wishlist into customer database records upon login.
     */
    public function mergeSessionToCustomer(int $customerId): void
    {
        $sessionIds = Session::get(self::SESSION_KEY, []);
        if (empty($sessionIds) || !is_array($sessionIds)) {
            return;
        }

        foreach ($sessionIds as $pId) {
            $pId = (int) $pId;
            if ($pId > 0 && Product::where('id', $pId)->exists()) {
                WishlistItem::firstOrCreate([
                    'customer_id' => $customerId,
                    'product_id' => $pId,
                ]);
            }
        }

        $allIds = WishlistItem::where('customer_id', $customerId)->pluck('product_id')->map(fn($id) => (int) $id)->all();
        Session::put(self::SESSION_KEY, $allIds);
    }
}
