<?php

namespace App\Support\Rbac;

use App\Models\AdminUser;

class AdminAuthorizer
{
    /**
     * Resolve effective permissions for an administrator.
     * Returns a sorted list of permission codes.
     *
     * @return string[]
     */
    public static function resolveEffectivePermissions(AdminUser $admin): array
    {
        // Fast-path: if roles are already loaded in-memory and any is super-admin, skip querying
        if ($admin->relationLoaded('roles')) {
            foreach ($admin->roles as $role) {
                if ($role->grants_all_permissions) {
                    return Permissions::all();
                }
            }
        }

        $admin->loadMissing('roles.permissions');

        foreach ($admin->roles as $role) {
            if ($role->grants_all_permissions) {
                return Permissions::all();
            }
        }

        $granted = [];
        foreach ($admin->roles as $role) {
            foreach ($role->permissions as $permission) {
                if (Permissions::isValid($permission->code)) {
                    $granted[$permission->code] = true;
                }
            }
        }

        // Return sorted stable array matching Permissions registry
        $effective = [];
        foreach (Permissions::all() as $code) {
            if (isset($granted[$code])) {
                $effective[] = $code;
            }
        }

        return $effective;
    }

    /**
     * Check if the administrator holds ALL required permissions (AND semantics).
     *
     * @param string|string[] $permissions
     */
    public static function hasAccess(AdminUser $admin, string|array $permissions): bool
    {
        $required = is_array($permissions) ? $permissions : [$permissions];

        if (empty($required)) {
            return true;
        }

        $effective = self::resolveEffectivePermissions($admin);
        $effectiveSet = array_flip($effective);

        foreach ($required as $code) {
            if (!isset($effectiveSet[$code])) {
                return false;
            }
        }

        return true;
    }
}
