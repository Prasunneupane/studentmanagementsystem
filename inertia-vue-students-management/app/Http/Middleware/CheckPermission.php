<?php
// app/Http/Middleware/CheckPermission.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return $this->redirectToLogin($request);
        }

        $user = Auth::user();

        // Super admin bypass (optional - if you want super admins to have all permissions)
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // Get user permissions (with caching for performance)
        $userPermissions = $this->getUserPermissions($user);

        // Check if user has any of the required permissions
        $hasPermission = $this->checkPermissions($userPermissions, $permissions);

        if (!$hasPermission) {
            // Log unauthorized access attempt
            Log::warning('Unauthorized access attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'route' => $request->path(),
                'required_permissions' => $permissions,
                'user_permissions' => $userPermissions,
            ]);

            return $this->handleUnauthorized($request, $permissions);
        }

        return $next($request);
    }

    /**
     * Check if user is a super admin
     */
    private function isSuperAdmin($user): bool
    {
        // Check if user has super admin role
        return $user->roles()->where('name', 'Super Admin')->exists();
    }

    /**
     * Get user permissions with caching
     */
    private function getUserPermissions($user): array
    {
        // Cache permissions for 60 minutes per user
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
     * Check if user has any of the required permissions
     */
    private function checkPermissions(array $userPermissions, array $requiredPermissions): bool
    {
        // User needs at least one of the required permissions
        foreach ($requiredPermissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect to login if not authenticated
     */
    private function redirectToLogin(Request $request): Response
    {
        if ($request->expectsJson() || $request->inertia()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return redirect()->guest(route('login'));
    }

    /**
     * Handle unauthorized access
     */
    private function handleUnauthorized(Request $request, array $permissions): Response
    {
        // For Inertia requests, render the forbidden page
        if ($request->inertia()) {
            return inertia('errors/Forbidden', [
                'message' => 'You do not have permission to access this page.',
                'required_permissions' => $permissions,
                'previous' => url()->previous(),
            ])->toResponse($request)->setStatusCode(403);
        }

        // For API requests
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
                'required_permissions' => $permissions,
            ], 403);
        }

        // For regular web requests
        abort(403, 'Unauthorized access. Required permissions: ' . implode(', ', $permissions));
    }

    /**
     * Clear user permissions cache (call this when permissions are updated)
     */
    public static function clearUserPermissionsCache(int $userId): void
    {
        Cache::forget("user_permissions_{$userId}");
    }
}