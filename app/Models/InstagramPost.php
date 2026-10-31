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

    protected $appends = [
        'display_media_url',
        'display_instagram_url',
        'formatted_likes',
        'formatted_views',
        'real_video_url',
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

        // Self-heal legacy path where /storage/video-studio/thumb_ lacked the /thumbnails/ subfolder
        if (str_contains($raw, '/storage/video-studio/thumb_') && !str_contains($raw, '/storage/video-studio/thumbnails/')) {
            $candidate = str_replace('/storage/video-studio/thumb_', '/storage/video-studio/thumbnails/thumb_', $raw);
            $parsed = parse_url($candidate, PHP_URL_PATH);
            if ($parsed && file_exists(public_path($parsed))) {
                return $candidate;
            }
        }

        // If media_url points to a local file that doesn't exist, fall back to tagged product
        if (!empty($raw) && (str_contains($raw, '/storage/') || str_contains($raw, '/media/'))) {
            $parsed = parse_url($raw, PHP_URL_PATH);
            if ($parsed && !file_exists(public_path($parsed))) {
                if ($this->taggedProduct) {
                    $video = $this->taggedProduct->approved_video ?? $this->taggedProduct->videos->first();
                    if ($video && !empty($video->thumbnail_url)) {
                        return $video->thumbnail_url;
                    }
                    if (!empty($this->taggedProduct->primary_image_url)) {
                        return $this->taggedProduct->primary_image_url;
                    }
                }
            }
        }

        if (empty($raw)) {
            if ($this->taggedProduct) {
                $video = $this->taggedProduct->approved_video ?? $this->taggedProduct->videos->first();
                if ($video && !empty($video->thumbnail_url)) {
                    return $video->thumbnail_url;
                }
                if (!empty($this->taggedProduct->primary_image_url)) {
                    return $this->taggedProduct->primary_image_url;
                }
            }
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

    /**
     * Resolves playable video stream URL: direct video_url, or the approved video from the tagged product.
     */
    public function getRealVideoUrlAttribute(): ?string
    {
        if (!empty($this->video_url)) {
            $raw = trim((string) $this->video_url);

            // If local storage file doesn't exist on disk, fallback to tagged product video
            if (str_contains($raw, '/storage/')) {
                $parsed = parse_url($raw, PHP_URL_PATH);
                if ($parsed && !file_exists(public_path($parsed)) && $this->taggedProduct) {
                    $prodVideo = $this->taggedProduct->approved_video ?? $this->taggedProduct->videos->first();
                    if ($prodVideo && !empty($prodVideo->video_url)) {
                        return $prodVideo->video_url;
                    }
                }
            }

            if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                return $raw;
            }
            return url('/media/' . ltrim($raw, '/'));
        }

        if ($this->taggedProduct) {
            $prodVideo = $this->taggedProduct->approved_video ?? $this->taggedProduct->videos->first();
            if ($prodVideo) {
                return $prodVideo->video_url;
            }
        }

        return null;
    }
}
