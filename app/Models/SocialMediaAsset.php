<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SocialMediaAsset extends Model
{
    use SoftDeletes;

    protected $table = 'social_media_assets';

    protected $fillable = [
        'supplier_id',
        'supplier_group_name',
        'file_path',
        'file_name',
        'file_size_bytes',
        'mime_type',
        'media_type',
        'width',
        'height',
        'duration_seconds',
        'file_hash',
        'perceptual_hash',
        'status',
        'category_name',
        'tags',
        'metadata',
        'created_by_admin_user_id',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration_seconds' => 'decimal:2',
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'url',
        'formatted_size',
    ];

    public function getUrlAttribute(): string
    {
        if (empty($this->file_path)) {
            return asset('images/placeholder.png');
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }
        return $bytes . ' B';
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SocialSupplier::class, 'supplier_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_user_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(SocialProduct::class, 'social_product_media', 'media_asset_id', 'social_product_id')
            ->withPivot(['position', 'is_primary', 'color_variant'])
            ->withTimestamps();
    }
}
