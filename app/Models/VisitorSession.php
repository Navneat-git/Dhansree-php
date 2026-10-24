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
        'is_repeat_visitor' => 'boolean',
        'visit_number' => 'integer',
        'customer_id' => 'integer',
    ];

    public function pageViews(): HasMany
    {
        return $this->hasMany(VisitorPageView::class, 'session_id', 'session_id')
            ->orderBy('step_number', 'asc');
    }

    public function trackedVisitor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TrackedVisitor::class, 'customer_code', 'customer_code');
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function getDisplayLocationAttribute(): string
    {
        $parts = array_filter([$this->city, $this->region, $this->country]);
        return !empty($parts) ? implode(', ', $parts) : 'Location Unknown';
    }
}
