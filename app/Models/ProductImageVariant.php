<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductImageVariant extends Model
{
    protected $table = 'product_image_variants';
    protected $primaryKey = ['image_id', 'variant_id'];
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [];

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'image_id', 'id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }
}
