<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    protected $table = 'shipments';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'status' => \App\Enums\ShipmentStatus::class,
        'payment_mode' => \App\Enums\ShipmentPaymentMode::class,
        'cod_amount_paise' => 'integer',
        'declared_value_paise' => 'integer',
        'shipping_cost_paise' => 'integer',
        'weight_grams' => 'integer',
        'estimated_delivery_at' => 'datetime',
        'pickup_scheduled_at' => 'datetime',
        'label_generated_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancel_requested_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'stock_deducted_at' => 'datetime',
        'needs_review' => 'boolean',
        'last_synced_at' => 'datetime',
        'claimed_at' => 'datetime',
        'awb_requested_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function createdByAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_user_id', 'id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id', 'id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class, 'shipment_id', 'id');
    }

    public function shipmentItems(): HasMany
    {
        return $this->items();
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(ShipmentTrackingEvent::class, 'shipment_id', 'id')->orderBy('occurred_at', 'desc');
    }

    public function shipmentTrackingEvents(): HasMany
    {
        return $this->trackingEvents();
    }

    public function shippingWebhookEvents(): HasMany
    {
        return $this->hasMany(ShippingWebhookEvent::class, 'shipment_id', 'id');
    }
}
