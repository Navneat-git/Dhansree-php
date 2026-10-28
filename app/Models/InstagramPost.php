<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramPost extends Model
{
    use HasFactory;

    protected $table = 'instagram_posts';

    protected $fillable = [
        'type',
        'caption',
        'media_url',
        'video_url',
        'instagram_url',
        'likes_count',
        'views_count',
        'comments_count',
        'tagged_product_id',
        'position',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'position' => 'integer',
        'tagged_product_id' => 'integer',
    ];

    /**
     * Optional product featured in this reel or post.
     */
    public function taggedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'tagged_product_id', 'id');
    }

    /**
     * Scope: only visible items.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope: reels only.
     */
    public function scopeReels(Builder $query): Builder
    {
        return $query->where('type', 'reel');
    }

    /**
     * Scope: regular posts only.
     */
    public function scopePosts(Builder $query): Builder
    {
        return $query->where('type', 'post');
    }

    /**
     * Clean media URL resolving relative storage keys or external CDN URLs.
     */
    public function getDisplayMediaUrlAttribute(): string
    {
        $raw = trim((string) $this->media_url);
        if (empty($raw)) {
            return 'https://assets.dhansree.com/ds-web/products/1/d875fada-3006-41f8-91ed-257d4d47077e.webp';
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        return url('/media/' . ltrim($raw, '/'));
    }

    /**
     * Direct link to Instagram reel/post or brand profile.
     */
    public function getDisplayInstagramUrlAttribute(): string
    {
        return !empty($this->instagram_url)
            ? $this->instagram_url
            : 'https://www.instagram.com/dhansreetrends';
    }

    /**
     * Formatted likes (e.g. 1,420).
     */
    public function getFormattedLikesAttribute(): string
    {
        return number_format((int) ($this->likes_count ?: 0));
    }

    /**
     * Formatted views (e.g. 24.5K).
     */
    public function getFormattedViewsAttribute(): string
    {
        if (!empty($this->views_count)) {
            return $this->views_count;
        }

        $likes = (int) ($this->likes_count ?: 1200);
        $views = round(($likes * 14.5) / 1000, 1);
        return "{$views}K";
    }
}
