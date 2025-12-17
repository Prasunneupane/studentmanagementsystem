<?php

namespace App\Repositories;

use App\Interface\PermissionInterface;
use App\Models\Permission;

class PermissionRepository implements PermissionInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

     public function getAllPermission()
    {
        return Permission::where('is_active', 1)->get();
    }

    public function createPermission(array $data)
    {
        // Implementation here 
        // dd($data);
        Permission::create($data);
    }
    public function updatePermission(int $PermissionId, array $data)
    {
        // Implementation here 

        $permission = Permission::findOrFail($PermissionId);
        $permission->update($data); 
    }
    public function deactivatePermission(int $PermissionId)
    {
        // Implementation here
        $permission = Permission::findOrFail($PermissionId);
        $permission->is_active = 0;
        $permission->save();
    }
    public function getPermissionById(int $PermissionId)
    {
        // Implementation here  
        return Permission::findOrFail($PermissionId);
    }
    public function activatePermission(int $PermissionId)
    {
        $permission = Permission::findOrFail($PermissionId);
        $permission->is_active = 1;
        $permission->save();
    }


}
