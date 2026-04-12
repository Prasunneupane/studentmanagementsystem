<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    /**
     * Route usage examples:
     *   ->middleware('permission:create_students')
     *   ->middleware('permission:view_roles,edit_roles')   // any ONE is enough
     *
     * Your web.php uses dot notation like 'students.create' in some places
     * and underscore like 'view_roles' in others.
     * This middleware normalises both to underscore format automatically.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            return $this->unauthenticated($request);
        }

        $user = Auth::user();

        // Normalise slugs: "students.create" → "create_students"
        // "view_roles" stays as "view_roles" (already correct)
        $normalised = array_map(
            fn(string $p) => $this->normaliseSlug($p),
            $permissions
        );

        Log::debug('[CheckPermission] Checking', [
            'user_id'    => $user->id,
            'route'      => $request->path(),
            'raw'        => $permissions,
            'normalised' => $normalised,
        ]);
        // dd($user);
        if ($this->permissionService->isSuperAdmin($user)) {
            return $next($request);
        }

        if (!$this->permissionService->hasAny($user, $normalised)) {
            Log::warning('[CheckPermission] Unauthorized', [
                'user_id'         => $user->id,
                'email'           => $user->email,
                'route'           => $request->path(),
                'required'        => $normalised,
                'user_has'        => $this->permissionService->getUserPermissions($user),
            ]);

            return $this->forbidden($request, $normalised);
        }

        return $next($request);
    }

    /**
     * Normalise slug format so routes and DB slugs always match.
     *
     * "students.create"   → "create_students"
     * "subjects.edit"     → "edit_subjects"
     * "view_roles"        → "view_roles"        (already normalised, unchanged)
     * "create_students"   → "create_students"   (already normalised, unchanged)
     */
    private function normaliseSlug(string $slug): string
    {
        // Already in underscore format (e.g. "view_roles", "create_students")
        if (str_contains($slug, '_')) {
            return $slug;
        }

        // Dot notation: "resource.action" → "action_resource"
        if (str_contains($slug, '.')) {
            [$resource, $action] = explode('.', $slug, 2);
            return "{$action}_{$resource}";
        }

        return $slug;
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson() || $request->inertia()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }

    private function forbidden(Request $request, array $permissions): Response
    {
        if ($request->inertia()) {
            return inertia('errors/Forbidden', [
                'message'              => 'You do not have permission to access this page.',
                'required_permissions' => $permissions,
                'previous'             => url()->previous(),
            ])->toResponse($request)->setStatusCode(403);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message'              => 'Forbidden.',
                'required_permissions' => $permissions,
            ], 403);
        }

        abort(403, 'Unauthorized. Required: ' . implode(', ', $permissions));
    }
}