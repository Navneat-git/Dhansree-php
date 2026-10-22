<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductImageOptionValue extends Model
{
    protected $table = 'product_image_option_values';
    protected $primaryKey = ['image_id', 'option_value_id'];
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [];

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'image_id', 'id');
    }

    public function optionValue(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class, 'option_value_id', 'id');
    }
}
