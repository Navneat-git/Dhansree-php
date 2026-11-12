<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPublishingLog extends Model
{
    protected $table = 'social_publishing_logs';

    protected $fillable = [
        'queue_item_id',
        'platform',
        'status',
        'post_id',
        'post_url',
        'response_payload',
        'error_message',
        'published_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'published_at' => 'datetime',
    ];

    public function queueItem(): BelongsTo
    {
        return $this->belongsTo(SocialPublishingQueue::class, 'queue_item_id');
    }
}
