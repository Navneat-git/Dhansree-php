<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialReelTemplate extends Model
{
    protected $table = 'social_reel_templates';

    protected $fillable = [
        'code',
        'name',
        'tagline',
        'description',
        'default_transition',
        'default_duration_seconds',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'default_duration_seconds' => 'integer',
    ];
}
