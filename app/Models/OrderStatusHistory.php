<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderStatusHistory extends Model
{
    protected $table = 'order_status_history';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'from_status' => \App\Enums\OrderStatus::class,
        'to_status' => \App\Enums\OrderStatus::class,
        'actor_type' => \App\Enums\OrderActorType::class,
        'created_at' => 'datetime',
    ];

    public function actorAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'actor_admin_user_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
