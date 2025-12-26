<?php

namespace App\Repositories;

use App\Interface\UserInterface;
use App\Models\Roles;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserRepository implements UserInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAllUsers()
    {
        return User::where('is_active',1)->with('roles')->get();
    }
    public function createUsers(array $data)
    {
        $user =  User::create($data);
        $user->roles()->sync([
            $data['roles'] => ['created_by' => JWTAuth::user()->id]
        ]);
        return $user;
    }
    public function updateUsers(int $userId, array $data)
    {
        // Implementation here
        $user = User::findOrFail($userId);
        $user->update($data);
        $user->roles()->sync([
            $data['roles'] => ['created_by' => JWTAuth::user()->id]
        ]);
        return $user;
    }
    public function deactivateUser(int $userId)
    {
        // Implementation here
        $user = User::findOrFail($userId);
        $user->is_active = 0;
        $user->save();
        return $user;
    }
    public function getUserById(int $userId): array
    {
        // Implementation here  
        $user = User::findOrFail($userId);
        return $user->toArray();
    }
    public function activateUser(int $userId)
    {
        $user = User::findOrFail($userId);
        $user->is_active = 1;
        $user->save();
    }

    public function getAllRoles(){
        return Roles::where('is_active',1)->pluck('name','id');
    }


}
