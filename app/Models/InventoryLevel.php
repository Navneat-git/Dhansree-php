<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLevel extends Model
{
    protected $table = 'inventory_levels';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'on_hand' => 'integer',
        'reserved' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getAvailableAttribute(): int
    {
        return max(0, (int) ($this->on_hand - $this->reserved));
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id', 'id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'variant_id', 'variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'variant_id', 'variant_id')
            ->where('location_id', $this->location_id);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'variant_id', 'variant_id')
            ->where('location_id', $this->location_id);
    }
}
