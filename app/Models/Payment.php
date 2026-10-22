<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'method' => \App\Enums\PaymentMethod::class,
        'status' => \App\Enums\PaymentStatus::class,
        'amount_paise' => 'integer',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'confirmed_via' => \App\Enums\PaymentConfirmationSource::class,
        'last_synced_at' => 'datetime',
        'refunded_paise' => 'integer',
        'signature_verified_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function recordedByAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'recorded_by_admin_user_id', 'id');
    }

    public function paymentEvents(): HasMany
    {
        return $this->hasMany(PaymentEvent::class, 'payment_id', 'id');
    }

    public function paymentRefunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class, 'payment_id', 'id');
    }
}
