<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HomeBlockItem extends Model
{
    protected $table = 'home_block_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(HomeBlock::class, 'block_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function homeBlockItemCategories(): HasMany
    {
        return $this->hasMany(HomeBlockItemCategory::class, 'item_id', 'id');
    }
}
