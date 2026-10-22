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

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class, 'product_id', 'id');
    }
}
