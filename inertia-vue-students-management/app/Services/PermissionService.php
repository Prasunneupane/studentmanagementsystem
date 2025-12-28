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
                        'view_student', 'create_student', 'edit_student', 'delete_student'
                    ]),
                    'canView'   => in_array('view_student', $permissions),
                    'canCreate' => in_array('create_student', $permissions),
                    'canEdit'   => in_array('edit_student', $permissions),
                    'canDelete' => in_array('delete_student', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Subjects
                |--------------------------------------------------------------------------
                */
                'subjects' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_subject', 'create_subject', 'edit_subject', 'delete_subject'
                    ]),
                    'canView'   => in_array('view_subject', $permissions),
                    'canCreate' => in_array('create_subject', $permissions),
                    'canEdit'   => in_array('edit_subject', $permissions),
                    'canDelete' => in_array('delete_subject', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Teachers
                |--------------------------------------------------------------------------
                */
                'teachers' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_teacher', 'create_teacher', 'edit_teacher', 'delete_teacher'
                    ]),
                    'canView'   => in_array('view_teacher', $permissions),
                    'canCreate' => in_array('create_teacher', $permissions),
                    'canEdit'   => in_array('edit_teacher', $permissions),
                    'canDelete' => in_array('delete_teacher', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Users
                |--------------------------------------------------------------------------
                */
                'users' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_user', 'create_user', 'edit_user', 'delete_user'
                    ]),
                    'canView'   => in_array('view_user', $permissions),
                    'canCreate' => in_array('create_user', $permissions),
                    'canEdit'   => in_array('edit_user', $permissions),
                    'canDelete' => in_array('delete_user', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Roles
                |--------------------------------------------------------------------------
                */
                'roles' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_role', 'create_role', 'edit_role', 'delete_role'
                    ]),
                    'canView'   => in_array('view_role', $permissions),
                    'canCreate' => in_array('create_role', $permissions),
                    'canEdit'   => in_array('edit_role', $permissions),
                    'canDelete' => in_array('delete_role', $permissions),
                ],

                /*
                |--------------------------------------------------------------------------
                | Permissions
                |--------------------------------------------------------------------------
                */
                'permissions' => [
                    'canManage' => $this->hasAny($permissions, [
                        'view_permission', 'create_permission', 'edit_permission', 'delete_permission'
                    ]),
                    'canView'   => in_array('view_permission', $permissions),
                    'canCreate' => in_array('create_permission', $permissions),
                    'canEdit'   => in_array('edit_permission', $permissions),
                    'canDelete' => in_array('delete_permission', $permissions),
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
                    'view_role', 'create_role', 'edit_role', 'delete_role',
                    'view_permission', 'create_permission', 'edit_permission', 'delete_permission',
                    'view_user', 'create_user', 'edit_user', 'delete_user',
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