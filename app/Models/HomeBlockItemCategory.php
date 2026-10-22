<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HomeBlockItemCategory extends Model
{
    protected $table = 'home_block_item_categories';
    protected $primaryKey = ['item_id', 'category_id'];
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(HomeBlockItem::class, 'item_id', 'id');
    }
}
