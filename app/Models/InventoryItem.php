<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';
    protected $primaryKey = 'variant_id';
    public $incrementing = false;
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'backorder_policy' => \App\Enums\BackorderPolicy::class,
        'low_stock_threshold' => 'integer',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class, 'variant_id', 'variant_id');
    }
}
