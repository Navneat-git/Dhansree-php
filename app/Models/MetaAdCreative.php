<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAdCreative extends Model
{
    protected $table = 'meta_ad_creatives';

    protected $fillable = [
        'product_id',
        'campaign_name',
        'ad_name',
        'angle',
        'primary_text',
        'headline',
        'description',
        'cta',
        'destination_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'creative_image_url',
        'ai_provider',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Get pre-formatted copy bundle for quick pasting into Meta Ads Manager.
     */
    public function getFullCopyBundleAttribute(): string
    {
        $productName = $this->product?->name ?? 'Dhansree Collection';
        $campaign = $this->campaign_name ?: 'Meta Ads Campaign';
        $angle = ucfirst($this->angle);

        return "=== META ADS MANAGER COPY PACKAGE ===\n" .
            "Campaign: {$campaign}\n" .
            "Product: {$productName}\n" .
            "Ad Angle: {$angle}\n" .
            "Call To Action (CTA): {$this->cta}\n" .
            "Destination Website URL:\n{$this->destination_url}\n\n" .
            "--- [PRIMARY TEXT (Ad Caption)] ---\n" .
            "{$this->primary_text}\n\n" .
            "--- [HEADLINE] ---\n" .
            "{$this->headline}\n\n" .
            "--- [DESCRIPTION] ---\n" .
            "{$this->description}\n" .
            "=====================================";
    }
}
