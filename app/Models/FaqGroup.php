<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FaqGroup extends Model
{
    protected $table = 'faq_groups';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
        'is_visible' => 'boolean',
        'show_on_faq_page' => 'boolean',
        'show_on_listing' => 'boolean',
        'show_on_product' => 'boolean',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function faqEntries(): HasMany
    {
        return $this->hasMany(FaqEntry::class, 'group_id', 'id');
    }
}
