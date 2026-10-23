<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
        'is_visible' => 'boolean',
        'show_in_browse' => 'boolean',
        'archived_at' => 'datetime',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true)->whereNull('archived_at');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }

    public function categoryContents(): HasMany
    {
        return $this->hasMany(CategoryContent::class, 'category_id', 'id');
    }

    public function categoryContent(): HasOne
    {
        return $this->hasOne(CategoryContent::class, 'category_id', 'id');
    }

    public function collectionCategories(): HasMany
    {
        return $this->hasMany(CollectionCategory::class, 'category_id', 'id');
    }

    public function faqEntries(): HasMany
    {
        return $this->hasMany(FaqEntry::class, 'category_id', 'id');
    }

    public function homeBlockItemCategories(): HasMany
    {
        return $this->hasMany(HomeBlockItemCategory::class, 'category_id', 'id');
    }

    public function homeBlockItems(): HasMany
    {
        return $this->hasMany(HomeBlockItem::class, 'category_id', 'id');
    }

    public function homeBlocks(): HasMany
    {
        return $this->hasMany(HomeBlock::class, 'rail_category_id', 'id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'id');
    }

    public function sizeCharts(): HasMany
    {
        return $this->hasMany(SizeChart::class, 'category_id', 'id');
    }

    public function sizeChart(): HasOne
    {
        return $this->hasOne(SizeChart::class, 'category_id', 'id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_storage_key) {
            return null;
        }

        if (str_starts_with($this->image_storage_key, 'http://') || str_starts_with($this->image_storage_key, 'https://')) {
            return $this->image_storage_key;
        }

        $cleanKey = ltrim($this->image_storage_key, '/');

        // Check if file exists in local public storage
        $localFile = storage_path('app/public/media/' . $cleanKey);
        if (file_exists($localFile)) {
            return url('/media/' . $cleanKey);
        }

        try {
            if (request()->hasHeader('host')) {
                $host = request()->getHost();
                if (filter_var($host, FILTER_VALIDATE_IP) || in_array($host, ['localhost', '127.0.0.1'], true)) {
                    return url('/media/' . $cleanKey);
                }
            }
        } catch (\Throwable $e) {
            // CLI or testing without HTTP request
        }

        $base = config('store.media_base_url');
        if ($base) {
            if (str_ends_with($base, '/ds-web') && str_starts_with($cleanKey, 'ds-web/')) {
                $cleanKey = substr($cleanKey, 7);
            }
            return rtrim($base, '/') . '/' . ltrim($cleanKey, '/');
        }

        return url('/media/' . $cleanKey);
    }
}
