<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialReelItem extends Model
{
    protected $table = 'social_reel_items';

    protected $fillable = [
        'social_reel_id',
        'social_product_id',
        'media_asset_id',
        'position',
        'headline',
        'price_text',
        'display_seconds',
    ];

    protected $casts = [
        'position' => 'integer',
        'display_seconds' => 'decimal:2',
    ];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(SocialReel::class, 'social_reel_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SocialProduct::class, 'social_product_id');
    }

    public function socialProduct(): BelongsTo
    {
        return $this->belongsTo(SocialProduct::class, 'social_product_id');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(SocialMediaAsset::class, 'media_asset_id');
    }
}
