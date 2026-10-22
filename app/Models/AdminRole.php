<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    protected $table = 'admin_roles';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'is_system' => 'boolean',
        'grants_all_permissions' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_role_permissions', 'role_id', 'permission_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_roles', 'role_id', 'admin_user_id');
    }

    public function adminRolePermissions(): HasMany
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id', 'id');
    }

    public function adminUserRoles(): HasMany
    {
        return $this->hasMany(AdminUserRole::class, 'role_id', 'id');
    }
}
