<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Authenticatable
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'status' => \App\Enums\CustomerStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id', 'id');
    }

    public function customerAddresses(): HasMany
    {
        return $this->addresses();
    }

    public function defaultShippingAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id', 'id')
            ->where('is_default_shipping', true);
    }

    public function defaultBillingAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id', 'id')
            ->where('is_default_billing', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'id')->orderBy('placed_at', 'desc');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id', 'id');
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class, 'customer_id', 'id');
    }

    public function customerSessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class, 'customer_id', 'id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_customer_id', 'id');
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class, 'customer_id', 'id');
    }

    public function emailMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'customer_id', 'id');
    }
}
