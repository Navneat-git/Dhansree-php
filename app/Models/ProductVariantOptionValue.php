<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariantOptionValue extends Model
{
    protected $table = 'product_variant_option_values';
    protected $primaryKey = ['variant_id', 'option_id'];
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [];

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'option_id', 'id');
    }

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class, 'option_value_id', 'id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }
}
