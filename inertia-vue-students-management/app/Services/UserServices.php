<?php

namespace App\Services;

use App\Interface\UserInterface;
use App\Transformers\UserTransformer;
use Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserServices
{
    /**
     * Create a new class instance.
     */
    private $userService;
    private $userTransformer;
    public function __construct(
        UserInterface $userInterface,
        UserTransformer $userTransformer
        )
    {
        $this->userService = $userInterface;
        $this->userTransformer = $userTransformer;
    }

    public function getAllUsers()
    {
        $data =  $this->userService->getAllUsers();
        $data = $this->userTransformer->transformUser($data);
        return $data;
    }
    public function createUsers(array $data)
    {
        $data =[...$data,
            'password' => Hash::make($data['password']),
            'is_active'=>1,
            'created_by'=>JWTAuth::user()->id,
        ];
        $user = $this->userService->createUsers($data);
        return $user;
    }

    public function updateUsers(int $userId, array $data)
    {
        $data = [
            ...$data,
            'created_by'=>JWTAuth::user()->id,
        ];
        // dd( $data);
        return $this->userService->updateUsers($userId, $data);
    }

    public function deactivateUser(int $userId)
    {
        return $this->userService->deactivateUser($userId);
    }

    public function getUserById(int $userId): array
    {
        return $this->userService->getUserById($userId);
    }

    public function activateUser(int $userId)
    {
        return $this->userService->activateUser($userId);
    }

    public function getAllRoles(){
        return $this->userService->getAllRoles();
    }

    public function transformRoles($roles){
        return $this->userTransformer->transformRoles($roles);
    }

    public function getUserWithRoles($user){
        $userWithRoles = $user->load('roles');
        $userData = $userWithRoles->toArray();
        $userData['roleId'] = $user->roles->pluck('id')->first();
        return $userData;
    }


}
