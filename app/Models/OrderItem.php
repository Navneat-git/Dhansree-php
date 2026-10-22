<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'option_values' => 'array',
        'quantity' => 'integer',
        'unit_price_paise' => 'integer',
        'mrp_paise' => 'integer',
        'discount_paise' => 'integer',
        'gst_rate_bps' => 'integer',
        'taxable_paise' => 'integer',
        'cgst_paise' => 'integer',
        'sgst_paise' => 'integer',
        'igst_paise' => 'integer',
        'tax_paise' => 'integer',
        'line_total_paise' => 'integer',
        'position' => 'integer',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'order_item_id', 'id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'order_item_id', 'id');
    }

    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class, 'order_item_id', 'id');
    }
}
