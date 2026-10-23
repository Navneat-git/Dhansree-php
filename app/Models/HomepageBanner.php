<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HomepageBanner extends Model
{
    protected $table = 'homepage_banners';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'position' => 'integer',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function createdByAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_user_id', 'id');
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
            $key = $cleanKey;
            if (str_ends_with($base, '/ds-web') && str_starts_with($key, 'ds-web/')) {
                $key = substr($key, 7);
            }
            return rtrim($base, '/') . '/' . ltrim($key, '/');
        }

        return url('/media/' . $cleanKey);
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        if (!$this->mobile_storage_key) {
            return null;
        }

        if (str_starts_with($this->mobile_storage_key, 'http://') || str_starts_with($this->mobile_storage_key, 'https://')) {
            return $this->mobile_storage_key;
        }

        $cleanKey = ltrim($this->mobile_storage_key, '/');

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
            $key = $cleanKey;
            if (str_ends_with($base, '/ds-web') && str_starts_with($key, 'ds-web/')) {
                $key = substr($key, 7);
            }
            return rtrim($base, '/') . '/' . ltrim($key, '/');
        }

        return url('/media/' . $cleanKey);
    }
}
