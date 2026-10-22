<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentRefund extends Model
{
    protected $table = 'payment_refunds';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'amount_paise' => 'integer',
        'status' => \App\Enums\RefundStatus::class,
        'origin' => \App\Enums\RefundOrigin::class,
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function initiatedByAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'initiated_by_admin_user_id', 'id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'id');
    }
}
