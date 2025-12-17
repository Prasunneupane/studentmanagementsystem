<?php

namespace App\Interface;

interface PermissionInterface
{
    public function getAllPermission();
    public function createPermission(array $data);
    public function updatePermission(int $PermissionId, array $data);
    public function deactivatePermission(int $PermissionId);    
    public function getPermissionById(int $PermissionId);
    public function activatePermission(int $PermissionId);
}
