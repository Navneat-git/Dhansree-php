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
}
