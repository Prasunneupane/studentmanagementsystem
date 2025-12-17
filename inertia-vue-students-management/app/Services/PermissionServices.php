<?php

namespace App\Services;

use App\Interface\PermissionInterface;
use App\Repositories\PermissionRepository;

class PermissionServices
{
    /**
     * Create a new class instance.
     */
    private $permissionRepository;
    public function __construct(PermissionInterface $permissionInterface)
    {
        //
        $this->permissionRepository = $permissionInterface;
    }

    public function getAllPermission()
    {
        return $this->permissionRepository->getAllPermission();
    }   
    public function createPermission(array $data)
    {
        $createdData = [
            ...$data,
            'slug' => \Str::slug($data['name'], '_'),

        ];
        return $this->permissionRepository->createPermission($createdData);
    }
    public function getPermissionById(int $id)
    {
        return $this->permissionRepository->getPermissionById($id);
    }

    public function updatePermission(int $id, array $data)
    {
        return $this->permissionRepository->updatePermission($id, $data);
    }
    public function deletePermission(int $id)
    {
        return $this->permissionRepository->deactivatePermission($id);
    }



}
