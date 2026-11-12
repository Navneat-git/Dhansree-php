<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialContentCaption extends Model
{
    protected $table = 'social_content_captions';

    protected $fillable = [
        'social_product_id',
        'tone',
        'platform',
        'caption_text',
        'hashtags',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(SocialProduct::class, 'social_product_id');
    }
}
