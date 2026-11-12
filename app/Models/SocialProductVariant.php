<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialProductVariant extends Model
{
    protected $table = 'social_product_variants';

    protected $fillable = [
        'social_product_id',
        'variant_code',
        'color',
        'size',
        'design_name',
        'purchase_price_paise',
        'selling_price_paise',
        'stock_status',
        'media_asset_id',
    ];

    protected $casts = [
        'purchase_price_paise' => 'integer',
        'selling_price_paise' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(SocialProduct::class, 'social_product_id');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(SocialMediaAsset::class, 'media_asset_id');
    }
}
