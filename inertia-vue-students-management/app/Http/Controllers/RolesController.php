<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Roles;
use App\Repositories\Validation;
use App\Services\PermissionService;
use App\Services\RoleServices;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $roleServices;
    private $dataValidation;
    protected $permissionService;
    public function __construct(RoleServices $roleServices,Validation $validation,PermissionService $permissionService)
    {
        $this->roleServices = $roleServices;
        $this->dataValidation = $validation;
        $this->permissionService = $permissionService;
    }
    public function index()
    {
        $roleList = $this->roleServices->getAllRoles();
        return Inertia::render('roles/RoleList', [
            'roles' => $roleList
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('roles/AddUpdateRole');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->dataValidation->roleValidationRules($request);
        $createRole = $this->roleServices->createRole($request->all());
        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Roles $roles)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Roles $role)
    {
        return Inertia::render('roles/AddUpdateRole', [
            'role' => $role
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Roles $role)
    {
        // dd($request->all());
        $this->dataValidation->roleUpdateValidationRules($request, $role->id);
        $updateRole = $this->roleServices->updateRole($role->id, $request->all());
        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Roles $roles)
    {
        //
    }

    public function deactivate(Roles $role)
    {
        $this->roleServices->deleteRole($role->id);
        return redirect()->route('roles.index')->with('success', 'Role deactivated successfully.');
    }

    // public function assign_permission(Roles $role)
    // {
    //     $allPermissions = Permission::where('is_active', 1)->get();
    //     $rolePermissions = $this->roleServices->getRolePermissions($role->id);
    //     $allRoles = $this->roleServices->getAllRoles();
    //     return Inertia::render('roles/AssignPermissionsToRole', [
    //         'role' => $role,
    //         'allPermissions' => $allPermissions,
    //         'rolePermissions' => $rolePermissions,
    //         'roles' => $allRoles
    //     ]);
    // }

    public function assign_permission(Roles $role)
    {
        $allPermissions = Permission::where('is_active', 1)
            ->select('id', 'name', 'module')
            ->orderBy('module')
            ->orderBy('name')
            ->get();
        
        $rolePermissions = $role->permissions()->pluck('permission_id')->toArray();
        $allRoles = Roles::where('is_active', 1)->select('id', 'name')->get();
        // dd($allRoles);
        return Inertia::render('roles/AssignPermissionsToRole', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
            'allPermissions' => $allPermissions,
            'rolePermissions' => $rolePermissions,
            'roles' => $allRoles,
        ]);
    }

    /**
     * Get role permissions (AJAX endpoint for dynamic loading)
     */
    public function getRolePermissions(Roles $role)
    {
        
        $allPermissions = Permission::where('is_active', 1)
            ->select('id', 'name', 'module')
            ->orderBy('module')
            ->orderBy('name')
            ->get();
        
        $rolePermissions = $role->permissions()->pluck('permission_id')->toArray();
        $allRoles = Roles::where('is_active', 1)->select('id', 'name')->get();
        return Inertia::render('roles/AssignPermissionsToRole', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
            ],
            'allPermissions' => $allPermissions,
            'rolePermissions' => $rolePermissions,
            'roles' => $allRoles,
        ]);
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(Request $request)
{
        $validated = $request->validate([
            'role_id' => 'required|exists:tbl_roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:tbl_permissions,id',
        ]);
        // dd($request->all());
        try {
            DB::beginTransaction();

            $role = Roles::findOrFail($validated['role_id']);

            $userId = auth()->id(); // 👈 current user

            // Build pivot data
            $syncData = [];

            foreach ($validated['permissions'] ?? [] as $permissionId) {
                $syncData[$permissionId] = [
                    'created_by' => $userId,
                ];
            }

            // Sync with pivot data
            $role->permissions()->sync($syncData);
            
            DB::commit();
            $this->invalidatePermissionsCache();
            
            return redirect()
                ->route('roles.index')
                ->with('success', 'Permissions assigned successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withErrors([
                    'error' => 'Failed to assign permissions: ' . $e->getMessage()
                ]);
        }
    }

    private function invalidatePermissionsCache(): void
    {
        // $users = $role->users;
        // foreach ($users as $user) {
            $this->permissionService->bumpPermissionsVersion();
        // }
    }

}
