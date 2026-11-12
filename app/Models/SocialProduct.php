<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialProduct extends Model
{
    use SoftDeletes;

    protected $table = 'social_products';

    protected $fillable = [
        'product_code',
        'name',
        'category_name',
        'supplier_id',
        'supplier_product_code',
        'fabric',
        'available_colors',
        'available_sizes',
        'purchase_price_paise',
        'suggested_selling_price_paise',
        'special_offer_price_paise',
        'description',
        'notes',
        'availability_status',
        'last_availability_confirmed_at',
        'primary_media_asset_id',
        'is_promoted_to_web',
        'web_product_id',
        'created_by_admin_user_id',
    ];

    protected $casts = [
        'available_colors' => 'array',
        'available_sizes' => 'array',
        'purchase_price_paise' => 'integer',
        'suggested_selling_price_paise' => 'integer',
        'special_offer_price_paise' => 'integer',
        'is_promoted_to_web' => 'boolean',
        'last_availability_confirmed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'purchase_price_rupees',
        'suggested_selling_price_rupees',
        'special_offer_price_rupees',
        'primary_image_url',
    ];

    public function getPurchasePriceRupeesAttribute(): ?float
    {
        return $this->purchase_price_paise ? round($this->purchase_price_paise / 100, 2) : null;
    }

    public function getSuggestedSellingPriceRupeesAttribute(): ?float
    {
        return $this->suggested_selling_price_paise ? round($this->suggested_selling_price_paise / 100, 2) : null;
    }

    public function getSpecialOfferPriceRupeesAttribute(): ?float
    {
        return $this->special_offer_price_paise ? round($this->special_offer_price_paise / 100, 2) : null;
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        if ($this->primaryMedia) {
            return $this->primaryMedia->url;
        }

        $first = $this->mediaAssets()->first();
        if ($first) {
            return $first->url;
        }

        return asset('images/placeholder.png');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SocialSupplier::class, 'supplier_id');
    }

    public function primaryMedia(): BelongsTo
    {
        return $this->belongsTo(SocialMediaAsset::class, 'primary_media_asset_id');
    }

    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(SocialMediaAsset::class, 'social_product_media', 'social_product_id', 'media_asset_id')
            ->withPivot(['id', 'position', 'is_primary', 'color_variant'])
            ->orderByPivot('position', 'asc')
            ->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(SocialProductVariant::class, 'social_product_id');
    }

    public function webProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'web_product_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_user_id');
    }

    public function captions(): HasMany
    {
        return $this->hasMany(SocialContentCaption::class, 'social_product_id');
    }
}
