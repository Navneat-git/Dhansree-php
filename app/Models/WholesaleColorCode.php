<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WholesaleColorCode extends Model
{
    protected $table = 'wholesale_color_codes';

    protected $fillable = [
        'color_name',
        'color_code',
        'hex_code',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
