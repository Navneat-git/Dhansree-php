<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPublishingAccount extends Model
{
    protected $table = 'social_publishing_accounts';

    protected $fillable = [
        'platform',
        'account_id',
        'account_name',
        'username',
        'profile_picture_url',
        'access_token',
        'token_expires_at',
        'is_connected',
        'settings',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'is_connected' => 'boolean',
        'token_expires_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $hidden = [
        'access_token',
    ];
}
