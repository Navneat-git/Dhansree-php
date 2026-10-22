<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdminUser extends Authenticatable
{
    protected $table = 'admin_users';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'status' => \App\Enums\AdminUserStatus::class,
        'failed_login_attempts' => 'integer',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'password_change_required' => 'boolean',
        'password_changed_at' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_user_roles', 'admin_user_id', 'role_id');
    }

    public function permissions()
    {
        // Resolve union of permissions across all assigned roles
        if ($this->hasSuperAdmin()) {
            return AdminPermission::all();
        }

        return AdminPermission::whereHas('roles', function ($q) {
            $q->whereIn('admin_roles.id', $this->roles->pluck('id'));
        })->get();
    }

    public function hasSuperAdmin(): bool
    {
        return $this->roles()->where('grants_all_permissions', true)->exists();
    }

    public function hasPermission(string $permissionCode): bool
    {
        if ($this->hasSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('code', $permissionCode))
            ->exists();
    }

    public function toProfile(): array
    {
        $this->loadMissing('roles.permissions');
        $hasSuperAdmin = $this->hasSuperAdmin();

        $twoFactorService = app(\App\Services\Admin\AdminTwoFactorService::class);
        $twoFactorEnabled = $twoFactorService->isEnrolled($this->id);
        $twoFactorSetupRequired = $twoFactorService->isRequired($this) && !$twoFactorEnabled;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roles->map(fn ($r) => ['code' => $r->code, 'name' => $r->name])->values()->all(),
            'permissions' => \App\Support\Rbac\AdminAuthorizer::resolveEffectivePermissions($this),
            'hasFullAccess' => $hasSuperAdmin,
            'passwordChangeRequired' => (bool) $this->password_change_required,
            'twoFactorSetupRequired' => $twoFactorSetupRequired,
            'twoFactorEnabled' => $twoFactorEnabled,
            'lastLoginAt' => $this->last_login_at?->toISOString(),
        ];
    }

    public function adminLoginChallenges(): HasMany
    {
        return $this->hasMany(AdminLoginChallenge::class, 'admin_user_id', 'id');
    }

    public function adminSessions(): HasMany
    {
        return $this->hasMany(AdminSession::class, 'admin_user_id', 'id');
    }

    public function adminTotpCredentials(): HasMany
    {
        return $this->hasMany(AdminTotpCredential::class, 'admin_user_id', 'id');
    }

    public function totpCredential(): HasOne
    {
        return $this->hasOne(AdminTotpCredential::class, 'admin_user_id', 'id');
    }

    public function adminUserRoles(): HasMany
    {
        return $this->hasMany(AdminUserRole::class, 'admin_user_id', 'id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_admin_user_id', 'id');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'created_by_admin_user_id', 'id');
    }

    public function homepageBanners(): HasMany
    {
        return $this->hasMany(HomepageBanner::class, 'created_by_admin_user_id', 'id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'actor_admin_user_id', 'id');
    }

    public function orderNotes(): HasMany
    {
        return $this->hasMany(OrderNote::class, 'author_admin_user_id', 'id');
    }

    public function orderStatusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'actor_admin_user_id', 'id');
    }

    public function paymentRefunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class, 'initiated_by_admin_user_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by_admin_user_id', 'id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'moderated_by_admin_user_id', 'id');
    }

    public function seoRedirects(): HasMany
    {
        return $this->hasMany(SeoRedirect::class, 'created_by_admin_user_id', 'id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'created_by_admin_user_id', 'id');
    }
}
