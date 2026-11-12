<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPublishingQueue extends Model
{
    protected $table = 'social_publishing_queue';

    protected $fillable = [
        'social_reel_id',
        'media_asset_id',
        'title',
        'content_type',
        'caption_instagram',
        'caption_facebook',
        'platforms',
        'status',
        'scheduled_at',
        'published_at',
        'error_message',
        'created_by_admin_user_id',
    ];

    protected $casts = [
        'platforms' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(SocialReel::class, 'social_reel_id');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(SocialMediaAsset::class, 'media_asset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SocialPublishingLog::class, 'queue_item_id');
    }
}
