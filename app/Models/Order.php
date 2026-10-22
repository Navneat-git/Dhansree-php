<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public const CREATED_AT = 'placed_at';
    public const UPDATED_AT = 'updated_at';

    protected $guarded = [];

    protected $casts = [
        'status' => \App\Enums\OrderStatus::class,
        'payment_status' => \App\Enums\PaymentStatus::class,
        'payment_method' => \App\Enums\PaymentMethod::class,
        'prices_include_tax' => 'boolean',
        'gst_registration_type' => \App\Enums\GstRegistrationType::class,
        'supply_type' => \App\Enums\GstSupplyType::class,
        'item_count' => 'integer',
        'subtotal_paise' => 'integer',
        'discount_paise' => 'integer',
        'shipping_paise' => 'integer',
        'shipping_gst_rate_bps' => 'integer',
        'shipping_taxable_paise' => 'integer',
        'shipping_tax_paise' => 'integer',
        'taxable_paise' => 'integer',
        'cgst_paise' => 'integer',
        'sgst_paise' => 'integer',
        'igst_paise' => 'integer',
        'tax_paise' => 'integer',
        'total_paise' => 'integer',
        'version' => 'integer',
        'placed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'updated_at' => 'datetime',
        'expired_at' => 'datetime',
        'payment_due_at' => 'datetime',
        'shipping_quoted_cost_paise' => 'integer',
        'shipping_rate_source' => \App\Enums\ShippingRateSource::class,
        'shipping_weight_grams' => 'integer',
        'invoice_issued_at' => 'datetime',
        'coupon_type' => \App\Enums\CouponType::class,
    ];

    public function getTotalRupeesAttribute(): float
    {
        return $this->total_paise / 100;
    }

    public function getSubtotalRupeesAttribute(): float
    {
        return $this->subtotal_paise / 100;
    }

    public function getDiscountRupeesAttribute(): float
    {
        return $this->discount_paise / 100;
    }

    public function getShippingRupeesAttribute(): float
    {
        return $this->shipping_paise / 100;
    }

    public function getTaxRupeesAttribute(): float
    {
        return $this->tax_paise / 100;
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id')->orderBy('position', 'asc');
    }

    public function orderItems(): HasMany
    {
        return $this->items();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class, 'order_id', 'id');
    }

    public function orderAddresses(): HasMany
    {
        return $this->addresses();
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class, 'order_id', 'id')
            ->where('type', \App\Enums\OrderAddressType::SHIPPING);
    }

    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class, 'order_id', 'id')
            ->where('type', \App\Enums\OrderAddressType::BILLING);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id', 'id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'order_id', 'id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class, 'order_id', 'id')->orderBy('id', 'asc');
    }

    public function orderNotes(): HasMany
    {
        return $this->notes();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id', 'id')->orderBy('id', 'asc');
    }

    public function orderStatusHistorys(): HasMany
    {
        return $this->statusHistory();
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'order_id', 'id');
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'order_id', 'id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'order_id', 'id');
    }
}
