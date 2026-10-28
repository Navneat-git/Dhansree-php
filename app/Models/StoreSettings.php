<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSettings extends Model
{
    protected $table = 'store_settings';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'gst_registration_type' => \App\Enums\GstRegistrationType::class,
        'prices_include_tax' => 'boolean',
        'default_gst_rate_bps' => 'integer',
        'whatsapp_enabled' => 'boolean',
        'cod_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'cod_max_order_paise' => 'integer',
        'cod_min_order_paise' => 'integer',
        'free_shipping_threshold_paise' => 'integer',
        'shipping_flat_rate_paise' => 'integer',
        'razorpay_enabled' => 'boolean',
        'package_breadth_cm' => 'integer',
        'package_height_cm' => 'integer',
        'package_length_cm' => 'integer',
        'packaging_weight_grams' => 'integer',
        'shipping_mode' => \App\Enums\ShippingMode::class,
        'shipping_rate_rule' => \App\Enums\ShippingRateRule::class,
        'shiprocket_enabled' => 'boolean',
        'delivery_estimate_enabled' => 'boolean',
        'faq_help_enabled' => 'boolean',
        'newsletter_enabled' => 'boolean',
        'newsletter_min_order_paise' => 'integer',
        'newsletter_popup_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::findOr(1, function () {
            return static::first() ?? new static(['id' => 1]);
        });
    }

    public function getFeaturedCouponCodeAttribute(): string
    {
        return !empty($this->attributes['featured_coupon_code'])
            ? $this->attributes['featured_coupon_code']
            : 'FESTIVE10';
    }

    protected static ?\App\Models\Coupon $cachedDefaultCoupon = null;
    protected static bool $couponResolved = false;

    public static function clearCouponCache(): void
    {
        static::$cachedDefaultCoupon = null;
        static::$couponResolved = false;
    }

    public function getDefaultCoupon(): ?\App\Models\Coupon
    {
        if (static::$couponResolved) {
            return static::$cachedDefaultCoupon;
        }

        $code = strtoupper(trim((string) $this->featured_coupon_code));
        if ($code === '') {
            static::$couponResolved = true;
            static::$cachedDefaultCoupon = null;
            return null;
        }

        $coupon = \App\Models\Coupon::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            static::$couponResolved = true;
            static::$cachedDefaultCoupon = null;
            return null;
        }

        $now = now();
        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            static::$couponResolved = true;
            static::$cachedDefaultCoupon = null;
            return null;
        }
        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            static::$couponResolved = true;
            static::$cachedDefaultCoupon = null;
            return null;
        }
        if ($coupon->usage_limit !== null && $coupon->times_redeemed >= $coupon->usage_limit) {
            static::$couponResolved = true;
            static::$cachedDefaultCoupon = null;
            return null;
        }

        static::$couponResolved = true;
        static::$cachedDefaultCoupon = $coupon;
        return $coupon;
    }

    /**
     * Calculate discounted price, formatted prices, and total discount percentage for storefront display.
     *
     * @return array{
     *     discounted_price: float,
     *     formatted_price: string,
     *     mrp: float|null,
     *     formatted_mrp: string|null,
     *     discount_percentage: int|null,
     *     coupon: \App\Models\Coupon|null
     * }
     */
    public function calculateDiscountedPrice(float|int $sellingPriceRupees, ?float $mrpRupees = null): array
    {
        $sellingPriceRupees = (float) $sellingPriceRupees;
        $mrpRupees = ($mrpRupees !== null && $mrpRupees > 0) ? (float) $mrpRupees : null;

        $coupon = $this->getDefaultCoupon();

        if (!$coupon || $sellingPriceRupees <= 0) {
            $discountPct = ($mrpRupees !== null && $mrpRupees > $sellingPriceRupees)
                ? (int) round((($mrpRupees - $sellingPriceRupees) / $mrpRupees) * 100)
                : null;

            return [
                'discounted_price' => $sellingPriceRupees,
                'formatted_price' => $sellingPriceRupees > 0 ? '₹' . number_format($sellingPriceRupees, 0) : '₹0',
                'mrp' => $mrpRupees,
                'formatted_mrp' => ($mrpRupees !== null && $mrpRupees > $sellingPriceRupees) ? '₹' . number_format($mrpRupees, 0) : null,
                'discount_percentage' => $discountPct,
                'coupon' => null,
            ];
        }

        $discountAmount = 0.0;
        if ($coupon->type === \App\Enums\CouponType::PERCENTAGE) {
            // value is in basis points: e.g. 1000 = 10%
            $pct = (float) $coupon->value / 10000;
            $discountAmount = $sellingPriceRupees * $pct;
        } elseif ($coupon->type === \App\Enums\CouponType::FIXED_AMOUNT) {
            $discountAmount = (float) $coupon->value / 100;
        }

        $discountedPrice = max(1.0, round($sellingPriceRupees - $discountAmount));
        $effectiveMrp = ($mrpRupees !== null && $mrpRupees > $discountedPrice) ? $mrpRupees : null;
        $totalDiscountPct = ($effectiveMrp !== null && $effectiveMrp > 0)
            ? (int) round((($effectiveMrp - $discountedPrice) / $effectiveMrp) * 100)
            : ($coupon->type === \App\Enums\CouponType::PERCENTAGE ? (int) round($coupon->value / 100) : null);

        return [
            'discounted_price' => $discountedPrice,
            'formatted_price' => '₹' . number_format($discountedPrice, 0),
            'mrp' => $mrpRupees,
            'formatted_mrp' => ($mrpRupees !== null && $mrpRupees > $discountedPrice) ? '₹' . number_format($mrpRupees, 0) : null,
            'discount_percentage' => $totalDiscountPct,
            'coupon' => $coupon,
        ];
    }

    public function getPaymentStripUrlAttribute(): ?string
    {
        if (!$this->payment_strip_storage_key) {
            return null;
        }

        if (str_starts_with($this->payment_strip_storage_key, 'http://') || str_starts_with($this->payment_strip_storage_key, 'https://')) {
            return $this->payment_strip_storage_key;
        }

        $base = config('store.media_base_url');
        if ($base) {
            $key = ltrim($this->payment_strip_storage_key, '/');
            if (str_ends_with($base, '/ds-web') && str_starts_with($key, 'ds-web/')) {
                $key = substr($key, 7);
            }
            return rtrim($base, '/') . '/' . ltrim($key, '/');
        }

        return asset('storage/media/' . ltrim($this->payment_strip_storage_key, '/'));
    }

    public function homeHeroProduct(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'home_hero_product_id', 'id');
    }

    public function smartLivingPromoProduct(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'smart_living_promo_product_id', 'id');
    }
}
