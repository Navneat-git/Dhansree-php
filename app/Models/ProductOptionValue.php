<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductOptionValue extends Model
{
    protected $table = 'product_option_values';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'option_id', 'id');
    }

    public function productImageOptionValues(): HasMany
    {
        return $this->hasMany(ProductImageOptionValue::class, 'option_value_id', 'id');
    }

    public function productVariantOptionValues(): HasMany
    {
        return $this->hasMany(ProductVariantOptionValue::class, 'option_value_id', 'id');
    }
}
