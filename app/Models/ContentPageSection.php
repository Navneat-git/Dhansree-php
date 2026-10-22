<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContentPageSection extends Model
{
    protected $table = 'content_page_sections';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'position' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(ContentPage::class, 'page_id', 'id');
    }
}
