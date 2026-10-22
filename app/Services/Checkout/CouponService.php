<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use InvalidArgumentException;

class CouponService
{
    /**
     * Validate a coupon against cart subtotal and customer eligibility, returning discount in paise.
     *
     * @return array{
     *     coupon: Coupon,
     *     discount_paise: int,
     *     discount_rupees: float
     * }
     */
    public function validateAndCalculate(
        string $code,
        int $subtotalPaise,
        ?int $customerId = null,
        ?string $customerEmail = null
    ): array {
        $cleanCode = strtoupper(trim($code));

        $coupon = Coupon::where('code', $cleanCode)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            throw new InvalidArgumentException("Coupon code '{$cleanCode}' is invalid or has expired.");
        }

        $now = now();

        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            throw new InvalidArgumentException("This coupon offer has not started yet.");
        }

        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            throw new InvalidArgumentException("Coupon code '{$cleanCode}' has expired.");
        }

        if ($coupon->usage_limit !== null && $coupon->times_redeemed >= $coupon->usage_limit) {
            throw new InvalidArgumentException("Coupon '{$cleanCode}' has reached its maximum total usage limit.");
        }

        if ($coupon->min_order_paise !== null && $subtotalPaise < $coupon->min_order_paise) {
            $minRupees = number_format($coupon->min_order_paise / 100, 0);
            throw new InvalidArgumentException("Minimum order amount of ₹{$minRupees} required for this coupon.");
        }

        if ($coupon->per_customer_limit !== null) {
            $customerRedemptions = CouponRedemption::where('coupon_id', $coupon->id)
                ->where(function ($query) use ($customerId, $customerEmail) {
                    if ($customerId) {
                        $query->where('customer_id', $customerId);
                    }
                    if ($customerEmail) {
                        $query->orWhere('email', strtolower($customerEmail));
                    }
                })
                ->count();

            if ($customerRedemptions >= $coupon->per_customer_limit) {
                throw new InvalidArgumentException("You have already used this coupon code.");
            }
        }

        // Calculate discount
        $discountPaise = 0;

        if ($coupon->type === CouponType::PERCENTAGE) {
            // value is in basis points: e.g. 1000 = 10%
            $discountPaise = (int) round(($subtotalPaise * (int) $coupon->value) / 10000);
            if ($coupon->max_discount_paise !== null && $discountPaise > $coupon->max_discount_paise) {
                $discountPaise = $coupon->max_discount_paise;
            }
        } elseif ($coupon->type === CouponType::FIXED_AMOUNT) {
            // value is in paise
            $discountPaise = min((int) $coupon->value, $subtotalPaise);
        }

        return [
            'coupon' => $coupon,
            'discount_paise' => $discountPaise,
            'discount_rupees' => round($discountPaise / 100, 2),
        ];
    }

    /**
     * Record coupon redemption for an order.
     */
    public function redeem(
        Coupon $coupon,
        int $orderId,
        int $discountPaise,
        ?int $customerId = null,
        ?string $customerEmail = null
    ): CouponRedemption {
        $redemption = CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'email' => strtolower($customerEmail ?? 'guest@dhansree.com'),
            'discount_paise' => $discountPaise,
            'created_at' => now(),
        ]);

        $coupon->increment('times_redeemed');

        return $redemption;
    }
}
