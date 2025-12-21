<?php

namespace App\Services;

use App\Interface\RoleInterface;

class RoleServices 
{
    /**
     * Create a new class instance.
     */
    private $roleService;
    public function __construct( RoleInterface $roleInterface)
    {
        //
        $this->roleService = $roleInterface;    
    }

    public function getAllRoles()
    {
        return $this->roleService->getAllRoles();
    }

    public function createRole(array $data)
    {
        return $this->roleService->createRole($data);
    }
    public function getRoleById(int $id)
    {
        return $this->roleService->getRoleById($id);    
    }
    public function updateRole(int $id, array $data)
    {
        return $this->roleService->updateRole($id, $data);
    }
    public function deleteRole(int $id)
    {
        return $this->roleService->deactivateRole($id);
    }
    public function getRolePermissions(int $roleId)
    {
        return $this->roleService->getRolePermissions($roleId);
    }   

}
