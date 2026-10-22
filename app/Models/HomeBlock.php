<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HomeBlock extends Model
{
    protected $table = 'home_blocks';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'type' => \App\Enums\HomeBlockType::class,
        'position' => 'integer',
        'is_visible' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function railCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'rail_category_id', 'id');
    }

    public function homeBlockItems(): HasMany
    {
        return $this->hasMany(HomeBlockItem::class, 'block_id', 'id');
    }
}
