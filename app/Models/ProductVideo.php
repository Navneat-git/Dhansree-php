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
        'file_size_bytes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'video_url',
        'thumbnail_url',
        'status_badge',
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
