<?php

namespace App\Repositories;

use App\Interface\RoleInterface;
use App\Models\Roles;

class RoleRepository implements RoleInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAllRoles()
    {
        return Roles::where('is_active', 1)->get();
    }

    public function createRole(array $data)
    {
        // Implementation here 
        // dd($data);
        Roles::create($data);
    }
    public function updateRole(int $roleId, array $data)
    {
        // Implementation here 

        $role = Roles::findOrFail($roleId);
        $role->update($data);
    }
    public function deactivateRole(int $roleId)
    {
        // Implementation here
        $role = Roles::findOrFail($roleId);
        $role->is_active = 0;
        $role->save();
    }
    public function getRoleById(int $roleId)
    {
        // Implementation here  
        return Roles::findOrFail($roleId);
    }
    public function activateRole(int $roleId)
    {
        $role = Roles::findOrFail($roleId);
        $role->is_active = 1;
        $role->save();
        
    }

    public function getRolePermissions(int $roleId)
    {
        $role = Roles::with('permissions')->findOrFail($roleId);
        return $role->permissions;
    }


}
