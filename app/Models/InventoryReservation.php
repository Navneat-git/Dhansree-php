<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryReservation extends Model
{
    protected $table = 'inventory_reservations';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'status' => \App\Enums\InventoryReservationStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLevel::class, 'location_id', 'location_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(InventoryLevel::class, 'variant_id', 'variant_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'reservation_id', 'id');
    }
}
