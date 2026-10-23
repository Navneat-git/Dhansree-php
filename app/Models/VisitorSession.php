<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorSession extends Model
{
    protected $table = 'visitor_sessions';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'page_views_count' => 'integer',
    ];

    public function pageViews(): HasMany
    {
        return $this->hasMany(VisitorPageView::class, 'session_id', 'session_id')
            ->orderBy('step_number', 'asc');
    }
}
