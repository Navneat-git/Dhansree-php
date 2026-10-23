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

    public function variants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_image_variants', 'image_id', 'variant_id');
    }

    public function optionValues(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'product_image_option_values', 'image_id', 'option_value_id');
    }

    public function getUrlAttribute(): string
    {
        if (!$this->storage_key) {
            return '';
        }

        if (str_starts_with($this->storage_key, 'http://') || str_starts_with($this->storage_key, 'https://')) {
            return $this->storage_key;
        }

        $cleanKey = ltrim($this->storage_key, '/');

        // Check if file exists in public/media or storage/app/public/media
        if (file_exists(public_path('media/' . $cleanKey)) || file_exists(storage_path('app/public/media/' . $cleanKey))) {
            return url('/media/' . $cleanKey);
        }

        $extraDir = config('store.media_local_directory') ?: (is_dir('/home/dhansree/files/ds-assets/ds-web') ? '/home/dhansree/files/ds-assets/ds-web' : null);
        if ($extraDir && file_exists(rtrim($extraDir, '/') . '/' . $cleanKey)) {
            return url('/media/' . $cleanKey);
        }

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
            if (str_ends_with($base, '/ds-web') && str_starts_with($cleanKey, 'ds-web/')) {
                $cleanKey = substr($cleanKey, 7);
            }
            return rtrim($base, '/') . '/' . ltrim($cleanKey, '/');
        }

        return url('/media/' . $cleanKey);
    }

    public function getIsPrimaryAttribute(): bool
    {
        return (int) $this->position === 0;
    }
}
