<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketplaceTemplate extends Model
{
    protected $table = 'marketplace_templates';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function marketplaceTemplateColumns(): HasMany
    {
        return $this->hasMany(MarketplaceTemplateColumn::class, 'template_id', 'id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(MarketplaceTemplateColumn::class, 'template_id', 'id')->orderBy('position');
    }
}
