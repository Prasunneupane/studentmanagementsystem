<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PermissionService
{
    private const CACHE_TTL  = 3600;
    private const VERSION_KEY = 'permissions_version';

    /* ======================================================
     | VERSION MANAGEMENT
     ====================================================== */

    public function getVersion(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public function bumpVersion(): void
    {
        if (!Cache::has(self::VERSION_KEY)) {
            Cache::forever(self::VERSION_KEY, 1);
        } else {
            Cache::increment(self::VERSION_KEY);
        }
    }

    public function clearUserCache(int $userId): void
    {
        $version = $this->getVersion();
        Cache::forget("user_permissions_{$userId}_v{$version}");
        Cache::forget("user_is_super_admin_{$userId}_v{$version}");
    }

    /* ======================================================
     | CORE: PERMISSION RESOLUTION
     | Works with YOUR existing User/Roles/Permission models
     | as-is — no model changes needed
     ====================================================== */

    public function getUserPermissions(User $user): array
    {
        $version = $this->getVersion();

        return Cache::remember(
            "user_permissions_{$user->id}_v{$version}",
            self::CACHE_TTL,
            function () use ($user) {
                // Uses User::roles() which is tbl_user_roles pivot
                // Uses Roles::permissions() which is tbl_role_permission pivot
                $slugs = $user->roles()
                    ->wherePivot('is_active', true)        // tbl_user_roles.is_active
                    ->where('tbl_roles.is_active', true)   // active roles only
                    ->with(['permissions' => function ($q) {
                        $q->where('tbl_permissions.is_active', true); // active perms only
                    }])
                    ->get()
                    ->pluck('permissions')
                    ->flatten()
                    ->pluck('slug')
                    ->unique()
                    ->values()
                    ->toArray();

                Log::debug('[PermissionService] Resolved permissions', [
                    'user_id' => $user->id,
                    'count'   => count($slugs),
                    'slugs'   => $slugs,
                ]);

                return $slugs;
            }
        );
    }

    /* ======================================================
     | SUPER ADMIN CHECK (versioned cache)
     ====================================================== */

    public function isSuperAdmin(User $user): bool
    {
        $version = $this->getVersion();

        return Cache::remember(
            "user_is_super_admin_{$user->id}_v{$version}",
            self::CACHE_TTL,
            fn () => $user->roles()
                ->wherePivot('is_active', true)
                ->where('tbl_roles.name', 'Super Admin')
                ->exists()
        );
    }

    /* ======================================================
     | PERMISSION CHECKS
     ====================================================== */

    /**
     * User has at least one of the given permission slugs.
     * Super admin always returns true.
     */
    public function hasAny(User $user, array $requiredSlugs): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if (empty($requiredSlugs)) {
            return true;
        }

        $userPermissions = $this->getUserPermissions($user);

        return !empty(array_intersect($requiredSlugs, $userPermissions));
    }

    /**
     * User has ALL of the given permission slugs.
     * Super admin always returns true.
     */
    public function hasAll(User $user, array $requiredSlugs): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $userPermissions = $this->getUserPermissions($user);

        return empty(array_diff($requiredSlugs, $userPermissions));
    }

    /* ======================================================
     | NAVIGATION PERMISSIONS (Inertia shared data)
     ====================================================== */

    public function getNavigationPermissions(User $user): array
    {
        $isSuperAdmin = $this->isSuperAdmin($user);
        $permissions  = $isSuperAdmin ? [] : $this->getUserPermissions($user);

        $can = fn(string $slug): bool =>
            $isSuperAdmin || in_array($slug, $permissions, true);

        $crud = fn(string $resource): array => [
            'canManage' => $can("view_{$resource}") || $can("create_{$resource}")
                        || $can("edit_{$resource}") || $can("delete_{$resource}"),
            'canView'   => $can("view_{$resource}"),
            'canCreate' => $can("create_{$resource}"),
            'canEdit'   => $can("edit_{$resource}"),
            'canDelete' => $can("delete_{$resource}"),
        ];

        return [
            'canViewDashboard'        => true,
            'students'                => $crud('students'),
            'guardians'               => $crud('guardians'),
            'subjects'                => $crud('subjects'),
            'teachers'                => $crud('teachers'),
            'terms'                   => $crud('terms'),
            'users'                   => $crud('users'),
            'classSubjects'           => $crud('class_subjects'),
            'classTeachers'           => $crud('class_teachers'),
            'exams'                   => $crud('exams'),
            'roles'                   => array_merge($crud('roles'), [
                'canAssignPermissions' => $can('assign_permissions'),
            ]),
            'permissions'             => $crud('permissions'),
            'settings'                => [
                'canView' => $can('view_settings'),
                'canEdit' => $can('edit_settings'),
            ],
            'canManageMasterSettings' => $isSuperAdmin || $this->hasAny($user, [
                'view_roles', 'create_roles', 'edit_roles', 'delete_roles',
                'view_permissions', 'create_permissions', 'edit_permissions', 'delete_permissions',
                'view_users', 'create_users', 'edit_users', 'delete_users',
            ]),
            'auth' => [
                'isSuperAdmin' => $isSuperAdmin,
                'user'         => $user->only(['id', 'name', 'email']),
                'roles'        => $user->roles->pluck('name'),
                'permissions'  => $isSuperAdmin ? ['*'] : $permissions,
            ],
        ];
    }
}