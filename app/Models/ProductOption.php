<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductOption extends Model
{
    protected $table = 'product_options';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function productOptionValues(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class, 'option_id', 'id');
    }

    public function values(): HasMany
    {
        return $this->productOptionValues();
    }

    public function productVariantOptionValues(): HasMany
    {
        return $this->hasMany(ProductVariantOptionValue::class, 'option_id', 'id');
    }
}
