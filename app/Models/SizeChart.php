<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SizeChart extends Model
{
    protected $table = 'size_charts';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'columns' => 'array',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function sizeChartRows(): HasMany
    {
        return $this->hasMany(SizeChartRow::class, 'chart_id', 'id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(SizeChartRow::class, 'chart_id', 'id')->orderBy('position')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_storage_key) {
            return null;
        }

        if (str_starts_with($this->image_storage_key, 'http://') || str_starts_with($this->image_storage_key, 'https://')) {
            return $this->image_storage_key;
        }

        $base = config('store.media_base_url');
        if ($base) {
            $key = ltrim($this->image_storage_key, '/');
            if (str_ends_with($base, '/ds-web') && str_starts_with($key, 'ds-web/')) {
                $key = substr($key, 7);
            }
            return rtrim($base, '/') . '/' . ltrim($key, '/');
        }

        return asset('storage/media/' . ltrim($this->image_storage_key, '/'));
    }
}
