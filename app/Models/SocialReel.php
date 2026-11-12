<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SocialReel extends Model
{
    use SoftDeletes;

    protected $table = 'social_reels';

    protected $fillable = [
        'reel_code',
        'title',
        'template_code',
        'aspect_ratio',
        'width',
        'height',
        'duration_seconds',
        'status',
        'progress',
        'video_path',
        'thumbnail_path',
        'caption',
        'hashtags',
        'audio_track',
        'render_settings',
        'error_message',
        'approved_by_admin_user_id',
        'created_by_admin_user_id',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'duration_seconds' => 'integer',
        'progress' => 'integer',
        'render_settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'video_url',
        'thumbnail_url',
    ];

    public function getVideoUrlAttribute(): ?string
    {
        if (empty($this->video_path)) {
            return null;
        }
        if (str_starts_with($this->video_path, 'http://') || str_starts_with($this->video_path, 'https://')) {
            return $this->video_path;
        }
        return Storage::disk('public')->url($this->video_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (empty($this->thumbnail_path)) {
            return null;
        }
        if (str_starts_with($this->thumbnail_path, 'http://') || str_starts_with($this->thumbnail_path, 'https://')) {
            return $this->thumbnail_path;
        }
        return Storage::disk('public')->url($this->thumbnail_path);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SocialReelTemplate::class, 'template_code', 'code');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SocialReelItem::class, 'social_reel_id')->orderBy('position', 'asc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'approved_by_admin_user_id');
    }
}
