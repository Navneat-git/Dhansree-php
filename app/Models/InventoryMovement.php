<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'type' => \App\Enums\InventoryMovementType::class,
        'reason' => \App\Enums\StockAdjustmentReason::class,
        'actor_type' => \App\Enums\AuditActorType::class,
        'created_at' => 'datetime',
    ];

    public function actorAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'actor_admin_user_id', 'id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLevel::class, 'location_id', 'location_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(InventoryReservation::class, 'reservation_id', 'id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(InventoryLevel::class, 'variant_id', 'variant_id');
    }
}
