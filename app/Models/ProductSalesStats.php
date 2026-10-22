<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductSalesStats extends Model
{
    protected $table = 'product_sales_stats';
    protected $primaryKey = 'product_id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'is_excluded' => 'boolean',
        'last_sold_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
