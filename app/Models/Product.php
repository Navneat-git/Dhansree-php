<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'status' => \App\Enums\ProductStatus::class,
        'gst_rate_bps' => 'integer',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'primary_image_url',
        'formatted_price',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', \App\Enums\ProductStatus::ACTIVE);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id')->orderBy('position');
    }

    public function productVariants(): HasMany
    {
        return $this->variants();
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class, 'product_id', 'id')->orderBy('position');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class, 'product_id', 'id')->orderBy('position');
    }

    public function productOptions(): HasMany
    {
        return $this->options();
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id')->orderBy('position');
    }

    public function productImages(): HasMany
    {
        return $this->images();
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_id', 'id')->where('position', 0);
    }

    public function bestSellerPins(): HasMany
    {
        return $this->hasMany(BestSellerPin::class, 'product_id', 'id');
    }

    public function faqEntries(): HasMany
    {
        return $this->hasMany(FaqEntry::class, 'product_id', 'id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id', 'id');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'product_id', 'id');
    }

    public function productContents(): HasMany
    {
        return $this->hasMany(ProductContent::class, 'product_id', 'id');
    }

    public function productMarketplaceValues(): HasMany
    {
        return $this->hasMany(ProductMarketplaceValue::class, 'product_id', 'id');
    }

    public function productSalesEntries(): HasMany
    {
        return $this->hasMany(ProductSalesEntry::class, 'product_id', 'id');
    }

    public function productSalesStats(): HasMany
    {
        return $this->hasMany(ProductSalesStats::class, 'product_id', 'id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id', 'id');
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', \App\Enums\ReviewStatus::APPROVED);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class, 'product_id', 'id');
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $img = $this->primaryImage ?? $this->images->first();
        return $img?->url;
    }

    public function getMinPriceRupeesAttribute(): ?float
    {
        $min = $this->variants->whereNull('archived_at')->min('price_paise');
        return $min !== null ? $min / 100 : null;
    }

    public function getMaxPriceRupeesAttribute(): ?float
    {
        $max = $this->variants->whereNull('archived_at')->max('price_paise');
        return $max !== null ? $max / 100 : null;
    }

    public function getMrpRupeesAttribute(): ?float
    {
        $mrp = $this->variants->whereNull('archived_at')->max('mrp_paise');
        return $mrp !== null ? $mrp / 100 : null;
    }

    public function getFormattedPriceAttribute(): string
    {
        $min = $this->min_price_rupees;
        $max = $this->max_price_rupees;

        if ($min === null) {
            return '₹0';
        }
        if ($min === $max || $max === null) {
            return '₹' . number_format($min, 0);
        }

        return '₹' . number_format($min, 0) . ' - ₹' . number_format($max, 0);
    }

    public function getFormattedMrpAttribute(): ?string
    {
        $mrp = $this->mrp_rupees;
        $price = $this->min_price_rupees;

        if ($mrp !== null && $price !== null && $mrp > $price) {
            return '₹' . number_format($mrp, 0);
        }

        return null;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        $mrp = $this->mrp_rupees;
        $price = $this->min_price_rupees;

        if ($mrp !== null && $price !== null && $mrp > $price) {
            return (int) round((($mrp - $price) / $mrp) * 100);
        }

        return null;
    }
}
