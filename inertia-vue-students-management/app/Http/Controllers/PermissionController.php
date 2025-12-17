<?php

namespace App\Http\Controllers;

use App\Models\Permission;
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
    public function __construct(PermissionServices $permissionServices,Validation $validation)
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        //
    }
}
