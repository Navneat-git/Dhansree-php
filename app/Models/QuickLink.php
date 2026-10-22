<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuickLink extends Model
{
    protected $table = 'quick_links';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(QuickLinkGroup::class, 'group_id', 'id');
    }
}
