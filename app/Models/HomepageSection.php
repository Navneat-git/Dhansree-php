<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HomepageSection extends Model
{
    protected $table = 'homepage_sections';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'key' => \App\Enums\HomepageSectionKey::class,
        'is_visible' => 'boolean',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function homepageSectionItems(): HasMany
    {
        return $this->hasMany(HomepageSectionItem::class, 'section_id', 'id');
    }
}
