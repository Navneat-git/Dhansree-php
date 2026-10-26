<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContentPage extends Model
{
    protected $table = 'content_pages';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'key' => \App\Enums\ContentPageKey::class,
        'is_visible' => 'boolean',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contentPageSections(): HasMany
    {
        return $this->hasMany(ContentPageSection::class, 'page_id', 'id');
    }

    public function getStorefrontPathAttribute(): string
    {
        $keyValue = $this->key instanceof \App\Enums\ContentPageKey ? $this->key->value : (string) $this->key;
        return match ($keyValue) {
            'ABOUT' => '/about',
            'CONTACT' => '/contact',
            'FAQ' => '/faq',
            'SHIPPING_POLICY' => '/shipping-policy',
            'RETURN_POLICY' => '/return-policy',
            'PRIVACY_POLICY' => '/privacy-policy',
            'TERMS' => '/terms',
            default => '/',
        };
    }

    public function getStorefrontUrlAttribute(): string
    {
        return url($this->storefront_path);
    }

    public function getCategoryNameAttribute(): string
    {
        $keyValue = $this->key instanceof \App\Enums\ContentPageKey ? $this->key->value : (string) $this->key;
        return match ($keyValue) {
            'PRIVACY_POLICY', 'SHIPPING_POLICY', 'RETURN_POLICY', 'TERMS' => 'Legal Policy',
            'ABOUT', 'CONTACT', 'FAQ' => 'Company Info',
            default => 'General Page',
        };
    }
}
