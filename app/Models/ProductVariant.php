<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class ProductVariant extends Model
{
    protected $table = 'product_variants';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'price_paise' => 'integer',
        'mrp_paise' => 'integer',
        'cost_paise' => 'integer',
        'position' => 'integer',
        'archived_at' => 'datetime',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'weight_grams' => 'integer',
    ];

    protected $appends = [
        'price_rupees',
        'mrp_rupees',
        'discount_percentage',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'variant_id', 'id');
    }

    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class, 'variant_id', 'id');
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_option_values',
            'variant_id',
            'option_value_id'
        );
    }

    public function getPriceRupeesAttribute(): float
    {
        return $this->price_paise / 100;
    }

    public function getMrpRupeesAttribute(): ?float
    {
        return $this->mrp_paise ? $this->mrp_paise / 100 : null;
    }

    public function getCostRupeesAttribute(): ?float
    {
        return $this->cost_paise !== null ? $this->cost_paise / 100 : null;
    }

    public function getFormattedPriceAttribute(): string
    {
        $price = $this->price_rupees;
        $isAdmin = request()?->is('admin*') ?? false;

        if (!$isAdmin) {
            $store = StoreSettings::current();
            $calc = $store->calculateDiscountedPrice($price, $this->mrp_rupees);
            return $calc['formatted_price'];
        }

        return '₹' . number_format($price, 0);
    }

    public function getFormattedMrpAttribute(): ?string
    {
        $price = $this->price_rupees;
        $mrp = $this->mrp_rupees;

        if ($mrp === null || $price === null) {
            return null;
        }

        $isAdmin = request()?->is('admin*') ?? false;

        if (!$isAdmin) {
            $store = StoreSettings::current();
            $calc = $store->calculateDiscountedPrice($price, $mrp);
            return $calc['formatted_mrp'];
        }

        if ($mrp > $price) {
            return '₹' . number_format($mrp, 0);
        }

        return null;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (!$this->mrp_paise || $this->mrp_paise <= $this->price_paise) {
            return null;
        }

        return (int) round((($this->mrp_paise - $this->price_paise) / $this->mrp_paise) * 100);
    }

    public function getStockOnHandAttribute(): int
    {
        if ($this->relationLoaded('inventoryLevels')) {
            return (int) $this->inventoryLevels->sum(fn($l) => $l->on_hand - $l->reserved);
        }

        return (int) $this->inventoryLevels()->sum(DB::raw('on_hand - reserved'));
    }

    public function getSizeOptionAttribute(): ?string
    {
        $this->loadMissing('optionValues.option');

        $sizeOption = $this->optionValues->first(function ($ov) {
            $name = strtolower($ov->option?->name ?? '');
            return in_array($name, ['size', 'sizes', 'apparel size', 'fit']);
        });

        return $sizeOption?->value;
    }

    public function getDisplayOptionLabelAttribute(): string
    {
        if ($this->size_option) {
            return $this->size_option;
        }

        $this->loadMissing('optionValues');
        $values = $this->optionValues->pluck('value')->filter()->values();
        if ($values->isEmpty()) {
            return 'Standard';
        }

        return (string) $values->last();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id', 'id');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'variant_id', 'id');
    }

    public function productVariantOptionValues(): HasMany
    {
        return $this->hasMany(ProductVariantOptionValue::class, 'variant_id', 'id');
    }

    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class, 'variant_id', 'id');
    }

    public function variantMarketplaceValues(): HasMany
    {
        return $this->hasMany(VariantMarketplaceValue::class, 'variant_id', 'id');
    }
}
