<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductVideo extends Model
{
    protected $table = 'product_videos';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'duration_seconds' => 'integer',
        'progress' => 'integer',
        'selected_image_ids' => 'array',
        'render_settings' => 'array',
        'quality_checklist' => 'array',
        'file_size_bytes' => 'integer',
        'is_primary' => 'boolean',
        'position' => 'integer',
        'pinned_option_value_ids' => 'array',
        'pinned_variant_ids' => 'array',
        'estimated_cost' => 'float',
        'actual_cost' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'video_url',
        'webm_url',
        'branded_video_url',
        'thumbnail_url',
        'master_video_url',
        'status_badge',
        'approval_badge',
        'video_type_badge',
        'aspect_ratio_label',
        'template_label',
        'formatted_duration',
        'formatted_file_size',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by', 'id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by', 'id');
    }

    public function primaryImage(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'primary_image_id', 'id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->whereIn('status', ['queued', 'processing']);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('approval_status', 'pending_review');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereIn('video_type', ['slideshow', 'imported_branded'])
              ->orWhere('approval_status', 'approved');
        });
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function getPinnedOptionValueIds(): array
    {
        return is_array($this->pinned_option_value_ids) ? $this->pinned_option_value_ids : [];
    }

    public function getPinnedVariantIds(): array
    {
        return is_array($this->pinned_variant_ids) ? $this->pinned_variant_ids : [];
    }

    public function isPinnedToOptionValue(int $optionValueId): bool
    {
        return in_array($optionValueId, $this->getPinnedOptionValueIds(), true);
    }

    public function isPinnedToVariant(int $variantId): bool
    {
        return in_array($variantId, $this->getPinnedVariantIds(), true);
    }

    public function scopeAiVideos(Builder $query): Builder
    {
        return $query->whereIn('video_type', ['ai_fashion', 'ai_fashion_branded', 'ai_motion', 'imported_branded']);
    }

    public function isAi(): bool
    {
        return in_array($this->video_type, ['ai_fashion', 'ai_fashion_branded', 'ai_motion', 'imported_branded'], true);
    }

    /**
     * Determines whether the video was AI-generated (e.g. AI Fashion Shoot, AI Motion)
     * as opposed to a standard FFmpeg slideshow of actual product photos or direct video uploads.
     */
    public function isAiGenerated(): bool
    {
        $settings = $this->render_settings ?? [];
        if (isset($settings['is_ai_generated'])) {
            return (bool) $settings['is_ai_generated'];
        }

        // Standard FFmpeg slideshows made exclusively from actual product photos are NOT AI
        if ($this->video_type === 'slideshow') {
            return false;
        }

        // Pure AI shoots, AI motion, AI branded, and uploaded showcase reels
        if (in_array($this->video_type, ['ai_fashion', 'ai_fashion_branded', 'ai_motion', 'imported_branded'], true)) {
            return true;
        }

        if (!empty($this->ai_provider) || !empty($this->ai_model)) {
            return true;
        }

        return false;
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending_review';
    }

    public function isApproved(): bool
    {
        if (in_array($this->video_type, ['slideshow', 'imported_branded'], true)) {
            return $this->approval_status !== 'rejected';
        }
        return $this->approval_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['queued', 'processing'], true);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->video_path) {
            return null;
        }

        $disk = config('video_studio.disk', 'public');
        if (Storage::disk($disk)->exists($this->video_path)) {
            return Storage::disk($disk)->url($this->video_path);
        }

        // Fallback relative url
        return url('/storage/' . ltrim($this->video_path, '/'));
    }

    /**
     * Resolves high-efficiency WebM URL for lightning-fast web streaming.
     */
    public function getWebmUrlAttribute(): ?string
    {
        $settings = $this->render_settings ?? [];
        $webmPath = $settings['webm_path'] ?? null;

        if ($webmPath) {
            $disk = config('video_studio.disk', 'public');
            if (Storage::disk($disk)->exists($webmPath)) {
                return Storage::disk($disk)->url($webmPath);
            }
            return url('/storage/' . ltrim($webmPath, '/'));
        }

        if ($this->video_path && str_ends_with(strtolower($this->video_path), '.webm')) {
            return $this->video_url;
        }

        return null;
    }

    /**
     * Resolves branded social media MP4 (with logo, price & audio) for Instagram / WhatsApp downloads.
     */
    public function getBrandedVideoUrlAttribute(): ?string
    {
        $settings = $this->render_settings ?? [];
        $brandedPath = $settings['branded_video_path'] ?? null;

        if ($brandedPath) {
            $disk = config('video_studio.disk', 'public');
            if (Storage::disk($disk)->exists($brandedPath)) {
                return Storage::disk($disk)->url($brandedPath);
            }
            return url('/storage/' . ltrim($brandedPath, '/'));
        }

        return $this->video_url;
    }

    public function isCleanStorefront(): bool
    {
        $settings = $this->render_settings ?? [];
        $preset = $settings['export_preset'] ?? null;
        if ($preset === 'clean_storefront' || $preset === 'dual') {
            return true;
        }
        return !($settings['text_overlay'] ?? true);
    }

    public function getMasterVideoUrlAttribute(): ?string
    {
        if (!$this->ai_master_video_path) {
            return null;
        }

        $disk = config('video_studio.disk', 'public');
        if (Storage::disk($disk)->exists($this->ai_master_video_path)) {
            return Storage::disk($disk)->url($this->ai_master_video_path);
        }

        return url('/storage/' . ltrim($this->ai_master_video_path, '/'));
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return $this->product?->primary_image_url;
        }

        $disk = config('video_studio.disk', 'public');
        if (Storage::disk($disk)->exists($this->thumbnail_path)) {
            return Storage::disk($disk)->url($this->thumbnail_path);
        }

        return url('/storage/' . ltrim($this->thumbnail_path, '/'));
    }

    public function getApprovalBadgeAttribute(): array
    {
        return match ($this->approval_status) {
            'approved' => [
                'text' => 'Approved',
                'class' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                'dot' => 'bg-emerald-400',
            ],
            'pending_review' => [
                'text' => 'Ready for Review',
                'class' => 'bg-amber-500/10 text-amber-300 border border-amber-500/30 font-medium',
                'dot' => 'bg-amber-400',
            ],
            'rejected' => [
                'text' => 'Rejected',
                'class' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
                'dot' => 'bg-rose-400',
            ],
            default => [
                'text' => 'Not Required',
                'class' => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
                'dot' => 'bg-slate-400',
            ],
        };
    }

    public function getVideoTypeBadgeAttribute(): array
    {
        return match ($this->video_type) {
            'ai_fashion' => [
                'text' => 'AI Fashion Shoot',
                'class' => 'bg-purple-500/15 text-purple-300 border border-purple-500/30',
                'icon' => 'sparkles',
            ],
            'ai_fashion_branded' => [
                'text' => 'AI Shoot + Branded',
                'class' => 'bg-indigo-500/15 text-indigo-300 border border-indigo-500/30',
                'icon' => 'sparkles-badge',
            ],
            'imported_branded' => [
                'text' => 'Imported + Branded Reel',
                'class' => 'bg-amber-500/15 text-amber-300 border border-amber-500/30',
                'icon' => 'sparkles-badge',
            ],
            default => [
                'text' => 'FFmpeg Slideshow',
                'class' => 'bg-slate-500/15 text-slate-300 border border-slate-500/30',
                'icon' => 'film',
            ],
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'completed' => [
                'text' => 'Completed',
                'class' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                'dot' => 'bg-emerald-400',
            ],
            'processing' => [
                'text' => 'Processing',
                'class' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse',
                'dot' => 'bg-blue-400',
            ],
            'queued' => [
                'text' => 'Queued',
                'class' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
                'dot' => 'bg-amber-400',
            ],
            'failed' => [
                'text' => 'Failed',
                'class' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
                'dot' => 'bg-rose-400',
            ],
            default => [
                'text' => ucfirst($this->status),
                'class' => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
                'dot' => 'bg-slate-400',
            ],
        };
    }

    public function getAspectRatioLabelAttribute(): string
    {
        $presets = config('video_studio.aspect_ratios', []);
        return $presets[$this->aspect_ratio]['badge'] ?? $this->aspect_ratio;
    }

    public function getTemplateLabelAttribute(): string
    {
        $templates = config('video_studio.templates', []);
        return $templates[$this->template]['name'] ?? ucwords(str_replace('_', ' ', $this->template));
    }

    public function getFormattedDurationAttribute(): string
    {
        return ($this->duration_seconds ?? 10) . 's';
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size_bytes) {
            return '-';
        }

        $bytes = $this->file_size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        return number_format($bytes / 1024, 0) . ' KB';
    }
}
