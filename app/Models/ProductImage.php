<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductImage extends Model
{
    protected $table = 'product_images';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'byte_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'url',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    public function productImageOptionValues(): HasMany
    {
        return $this->hasMany(ProductImageOptionValue::class, 'image_id', 'id');
    }

    public function productImageVariants(): HasMany
    {
        return $this->hasMany(ProductImageVariant::class, 'image_id', 'id');
    }

    public function getUrlAttribute(): string
    {
        if (!$this->storage_key) {
            return '';
        }

        $cleanKey = ltrim($this->storage_key, '/');

        // When accessed via IP address or localhost, use /media proxy route so image loads reliably
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
            return rtrim($base, '/') . '/' . $cleanKey;
        }

        return url('/media/' . $cleanKey);
    }

    public function getIsPrimaryAttribute(): bool
    {
        return (int) $this->position === 0;
    }
}
