<?php
// app/Services/PermissionService_php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Get user permissions with caching
     */
    public function getUserPermissions(User $user): array
    {
        return Cache::remember("user_permissions_{$user->id}", 3600, function () use ($user) {
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
                ->toArray();
        });
    }

    /**
     * Get navigation items based on user permissions
     */
    public function getNavigationItems(User $user): array
    {
        $permissions = $this->getUserPermissions($user);
        // dd($permissions);
       return [

                /*
                |--------------------------------------------------------------------------
                | Dashboard
                |--------------------------------------------------------------------------
                */
                'canViewDashboard' => true,

                /*
                |--------------------------------------------------------------------------
                | Students
                |--------------------------------------------------------------------------
                */
                'students' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_students', 'create_students', 'edit_students', 'delete_students'
                    ]),
                    'canView'   => in_array('view_students', $permissions),
                    'canCreate' => in_array('create_students', $permissions),
                    'canEdit'   => in_array('edit_students', $permissions),
                    'canDelete' => in_array('delete_students', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Subjects
                |--------------------------------------------------------------------------
                */
                'subjects' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_subjects', 'create_subjects', 'edit_subjects', 'delete_subjects'
                    ]),
                    'canView'   => in_array('view_subjects', $permissions),
                    'canCreate' => in_array('create_subjects', $permissions),
                    'canEdit'   => in_array('edit_subjects', $permissions),
                    'canDelete' => in_array('delete_subjects', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Teachers
                |--------------------------------------------------------------------------
                */
                'teachers' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_teachers', 'create_teachers', 'edit_teachers', 'delete_teachers'
                    ]),
                    'canView'   => in_array('view_teachers', $permissions),
                    'canCreate' => in_array('create_teachers', $permissions),
                    'canEdit'   => in_array('edit_teachers', $permissions),
                    'canDelete' => in_array('delete_teachers', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Users
                |--------------------------------------------------------------------------
                */
                'users' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_users', 'create_users', 'edit_users', 'delete_users'
                    ]),
                    'canView'   => in_array('view_users', $permissions),
                    'canCreate' => in_array('create_users', $permissions),
                    'canEdit'   => in_array('edit_users', $permissions),
                    'canDelete' => in_array('delete_users', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Roles
                |--------------------------------------------------------------------------
                */
                'roles' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_roles', 'create_roles', 'edit_roles', 'delete_roles'
                    ]),
                    'canView'   => in_array('view_roles', $permissions),
                    'canCreate' => in_array('create_roles', $permissions),
                    'canEdit'   => in_array('edit_roles', $permissions),
                    'canDelete' => in_array('delete_roles', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Permissions
                |--------------------------------------------------------------------------
                */
                'permissions' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_permissions', 'create_permissions', 'edit_permissions', 'delete_permissions'
                    ]),
                    'canView'   => in_array('view_permissions', $permissions),
                    'canCreate' => in_array('create_permissions', $permissions),
                    'canEdit'   => in_array('edit_permissions', $permissions),
                    'canDelete' => in_array('delete_permissions', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Settings
                |--------------------------------------------------------------------------
                */
                'settings' => [
                    'canView' => in_array('view_settings', $permissions),
                    'canEdit' => in_array('edit_settings', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Master Settings (Roles + Permissions + Users)
                |--------------------------------------------------------------------------
                */
                'canManageMasterSettings' => $this->hasAny($permissions, [
                    'view_roles', 'create_roles', 'edit_roles', 'delete_roles',
                    'view_permissions', 'create_permissions', 'edit_permissions', 'delete_permissions',
                    'view_users', 'create_users', 'edit_users', 'delete_users',
                ]),

                /*
                |--------------------------------------------------------------------------
                | META INFORMATION
                |--------------------------------------------------------------------------
                */
                'auth' => [
                    'isSuperAdmin' => $this->isSuperAdmin($user),

                    // User basic info
                    'user' => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                    ],

                    // Roles assigned to user
                    'roles' => $user->roles->pluck('name'),

                    // All permissions resolved from roles
                    'permissions' => $permissions,
                ],
            ];
    }

    /**
     * Check if user has any of the specified permissions
     */
    public function hasAny(array $userPermissions, array $requiredPermissions): bool
    {
        return !empty(array_intersect($userPermissions, $requiredPermissions));
    }

    /**
     * Check if user has all of the specified permissions
     */
    public function hasAll(array $userPermissions, array $requiredPermissions): bool
    {
        return empty(array_diff($requiredPermissions, $userPermissions));
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(User $user): bool
    {
        return Cache::remember("user_is_super_admin_{$user->id}", 3600, function () use ($user) {
            return $user->roles()->where('name', 'Super Admin')->exists();
        });
    }

    /**
     * Clear all permission caches for a user
     */
    public function clearUserCache(User $user): void
    {
        Cache::forget("user_permissions_{$user->id}");
        Cache::forget("user_is_super_admin_{$user->id}");
    }

    /**
     * Clear permission cache for all users
     * Call this when permissions or roles are updated
     */
    public function clearAllCache(): void
    {
        // This is a simple approach - for production, consider using Cache tags
        Cache::flush(); // Only use if you're not caching other important data
        
        // Or use a more targeted approach:
        // User::chunk(100, function ($users) {
        //     foreach ($users as $user) {
        //         $this->clearUserCache($user);
        //     }
        // });
    }

    /**
     * Refresh permissions for a specific user
     */
    public function refreshUserPermissions(User $user): array
    {
        $this->clearUserCache($user);
        return $this->getUserPermissions($user);
    }
}