<?php

namespace App\Repositories;

use App\Interface\UserInterface;
use App\Models\Roles;
use App\Models\Teachers;
use App\Models\TeacherUserMapping;
use App\Models\User;
use DB;
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
        return User::where('is_active', 1)->with('roles')->get();
    }
    public function createUsers(array $data)
    {
        $user = User::create($data);
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

    public function getAllRoles()
    {
        return Roles::where('is_active', 1)->pluck('name', 'id');
    }

    public function getTeacherById(int $teacherId): array
    {
        // Implementation here  
        return Teachers::findOrFail($teacherId)->toArray();
    }

    public function CheckTeacherUserNameExist($request, $teacherName): bool
    {
        if ($request->name === $teacherName) {
            return true;
        }
        return false;
    }

    public function checkIfUserExist($teacher): bool
    {
        $user = TeacherUserMapping::where('teacher_id', $teacher['id'])->first();
        return $user ? true : false;
    }

    public function createTeacherUser($teacher, $data)
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'is_active' => 1,
            'created_by' => auth()->id(),
        ];
        // Begin a transaction to ensure data integrity
        try {
            DB::beginTransaction();
            $user = User::create($userData);
            $user->roles()->sync([
                $data['roles'] => ['created_by' => auth()->id()]
            ]);
            TeacherUserMapping::create([
                'teacher_id' => $teacher['id'],
                'user_id' => $user->id,
                'created_by' => auth()->id(),
                'is_active' => 1,
            ]);
            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


}
