<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialSupplier extends Model
{
    use SoftDeletes;

    protected $table = 'social_suppliers';

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'whatsapp_group_name',
        'city',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(SocialMediaAsset::class, 'supplier_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(SocialProduct::class, 'supplier_id');
    }
}
