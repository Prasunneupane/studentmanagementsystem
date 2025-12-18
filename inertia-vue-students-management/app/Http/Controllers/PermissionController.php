<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Roles;
use App\Repositories\Validation;
use App\Services\PermissionServices;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $permissionServices;
    private $dataValidation;
    public function __construct(
        PermissionServices $permissionServices,
        Validation $validation
    )
    {
        $this->permissionServices = $permissionServices;  
        $this->dataValidation = $validation;
    }
    public function index()
    {
        return Inertia::render('permission/PermissionList', [
            'permissions' => $this->permissionServices->getAllPermission()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('permission/AddUpdatePermission');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->dataValidation->permissionValidationRules($request);
        $createPermission = $this->permissionServices->createPermission($request->all());
        return redirect()->route('permissions.index')->with('success', 'Permission created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {

        return Inertia::render('permission/AddUpdatePermission',[
            'permission'=>$permission
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $this->dataValidation->permissionUpdateValidationRules($request, $permission->id);
        $updatePermission = $this->permissionServices->updatePermission($permission->id,$request->all());
        return redirect()->route('permissions.index')->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        //
    }

    public function deactivate(Permission $permission)
    {
        $this->permissionServices->deletePermission($permission->id);
        return redirect()->route('permissions.index')->with('success', 'Permission deactivated successfully.');
    }

    public function assign_permission()
    {
        
        return Inertia::render('permission/AssignPermissionsToRole',[
            'roles' => Roles::where('is_active', 1)->get(),
            'permissions' => $this->permissionServices->getAllPermission(),
        ]);
    }
}
