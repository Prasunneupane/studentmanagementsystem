<?php

namespace App\Interface;

interface RoleInterface
{
    public function getAllRoles();
    public function createRole(array $data);
    public function updateRole(int $roleId, array $data);
    public function deactivateRole(int $roleId);    
    public function getRoleById(int $roleId);
    public function activateRole(int $roleId);
    public function getRolePermissions(int $roleId);
}
