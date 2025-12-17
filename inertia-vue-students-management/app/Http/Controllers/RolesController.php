<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Repositories\Validation;
use App\Services\RoleServices;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $roleServices;
    private $dataValidation;
    public function __construct(RoleServices $roleServices,Validation $validation)
    {
        $this->roleServices = $roleServices;
        $this->dataValidation = $validation;
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
}
