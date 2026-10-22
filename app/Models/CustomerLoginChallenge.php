<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerLoginChallenge extends Model
{
    protected $table = 'customer_login_challenges';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts' => 'integer',
        'consumed_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'window_start' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
