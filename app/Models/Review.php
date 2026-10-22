<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'rating' => 'integer',
        'status' => \App\Enums\ReviewStatus::class,
        'published_at' => 'datetime',
        'moderated_at' => 'datetime',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function moderatedByAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'moderated_by_admin_user_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', \App\Enums\ReviewStatus::APPROVED);
    }

    public function getAuthorNameAttribute(): string
    {
        $name = $this->customer?->name;
        if (!$name) {
            return 'Verified Buyer';
        }
        $parts = preg_split('/\s+/', trim($name));
        $first = $parts[0] ?? 'Verified';
        $second = $parts[1] ?? '';
        return $second !== '' ? "{$first} " . strtoupper(mb_substr($second, 0, 1)) . '.' : $first;
    }

    public function isEditable(): bool
    {
        if (!$this->created_at) {
            return false;
        }
        return $this->created_at->diffInDays(now()) <= 30;
    }
}
