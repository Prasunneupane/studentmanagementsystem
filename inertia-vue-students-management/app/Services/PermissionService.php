<?php
// app/Services/PermissionService_php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /* ======================================================
     | CORE: VERSIONED PERMISSION RESOLUTION
     ====================================================== */

    public function getUserPermissions(User $user): array
    {
        $version = Cache::get('permissions_version', 1);

        return Cache::remember(
            "user_permissions_{$user->id}_v{$version}",
            3600,
            function () use ($user) {
                return $user->roles()
                    ->where('tbl_roles.is_active', true)
                    ->with(['permissions' => function ($query) {
                        $query->where('tbl_permissions.is_active', true);
                    }])
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->pluck('slug')
                    ->unique()
                    ->values()
                    ->toArray();
            }
        );
    }

    /* ======================================================
     | NAVIGATION / UI PERMISSIONS
     ====================================================== */

    public function getNavigationItems(User $user): array
    {
        $permissions = $this->getUserPermissions($user);

        return [
            'canViewDashboard' => true,

            'students' => $this->crudPermissions($permissions, 'students'),
            'guardians' => $this->crudPermissions($permissions, 'guardians'),
            'subjects' => $this->crudPermissions($permissions, 'subjects'),
            'teachers' => $this->crudPermissions($permissions, 'teachers'),
            'users' => $this->crudPermissions($permissions, 'users'),
            'roles' => array_merge(
                $this->crudPermissions($permissions, 'roles'),
                ['canAssignPermissions' => in_array('assign_permissions', $permissions)]
            ),
            'permissions' => $this->crudPermissions($permissions, 'permissions'),

            'settings' => [
                'canView' => in_array('view_settings', $permissions),
                'canEdit' => in_array('edit_settings', $permissions),
            ],

            'canManageMasterSettings' => $this->hasAny($permissions, [
                'view_roles', 'create_roles', 'edit_roles', 'delete_roles',
                'view_permissions', 'create_permissions', 'edit_permissions', 'delete_permissions',
                'view_users', 'create_users', 'edit_users', 'delete_users',
            ]),

            'auth' => [
                'isSuperAdmin' => $this->isSuperAdmin($user),
                'user' => $user->only(['id', 'name', 'email']),
                'roles' => $user->roles->pluck('name'),
                'permissions' => $permissions,
            ],
        ];
    }

    /* ======================================================
     | HELPERS
     ====================================================== */

    private function crudPermissions(array $permissions, string $resource): array
    {
        return [
            'canManage' => $this->hasAny($permissions, [
                "view_{$resource}",
                "create_{$resource}",
                "edit_{$resource}",
                "delete_{$resource}",
            ]),
            'canView' => in_array("view_{$resource}", $permissions),
            'canCreate' => in_array("create_{$resource}", $permissions),
            'canEdit' => in_array("edit_{$resource}", $permissions),
            'canDelete' => in_array("delete_{$resource}", $permissions),
        ];
    }

    public function hasAny(array $userPermissions, array $required): bool
    {
        return !empty(array_intersect($userPermissions, $required));
    }

    public function hasAll(array $userPermissions, array $required): bool
    {
        return empty(array_diff($required, $userPermissions));
    }

    /* ======================================================
     | SUPER ADMIN (VERSIONED)
     ====================================================== */

    public function isSuperAdmin(User $user): bool
    {
        $version = Cache::get('permissions_version', 1);

        return Cache::remember(
            "user_is_super_admin_{$user->id}_v{$version}",
            3600,
            fn () => $user->roles()->where('name', 'Super Admin')->exists()
        );
    }

    /* ======================================================
     | GLOBAL INVALIDATION (🔥 THIS IS THE KEY)
     ====================================================== */

    public function bumpPermissionsVersion(): void
    {
        Cache::increment('permissions_version');
    }
}
