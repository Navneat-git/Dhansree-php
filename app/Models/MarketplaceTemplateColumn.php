<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketplaceTemplateColumn extends Model
{
    protected $table = 'marketplace_template_columns';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
        'allowed_values' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(MarketplaceTemplate::class, 'template_id', 'id');
    }

    public function productMarketplaceValues(): HasMany
    {
        return $this->hasMany(ProductMarketplaceValue::class, 'column_id', 'id');
    }

    public function variantMarketplaceValues(): HasMany
    {
        return $this->hasMany(VariantMarketplaceValue::class, 'column_id', 'id');
    }
}
