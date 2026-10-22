<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SizeChartRow extends Model
{
    protected $table = 'size_chart_rows';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
        'values' => 'array',
    ];

    public function chart(): BelongsTo
    {
        return $this->belongsTo(SizeChart::class, 'chart_id', 'id');
    }
}
