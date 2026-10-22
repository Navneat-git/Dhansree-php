<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuickLinkGroup extends Model
{
    protected $table = 'quick_link_groups';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
        'is_visible' => 'boolean',
        'show_on_home' => 'boolean',
        'show_on_listing' => 'boolean',
        'show_on_product' => 'boolean',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function categoryContents(): HasMany
    {
        return $this->hasMany(CategoryContent::class, 'quick_link_group_id', 'id');
    }

    public function productContents(): HasMany
    {
        return $this->hasMany(ProductContent::class, 'quick_link_group_id', 'id');
    }

    public function quickLinks(): HasMany
    {
        return $this->hasMany(QuickLink::class, 'group_id', 'id');
    }
}
