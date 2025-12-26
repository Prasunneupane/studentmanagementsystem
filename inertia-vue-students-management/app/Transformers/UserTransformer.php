<?php

namespace App\Transformers;

use Illuminate\Database\Eloquent\Collection;

class UserTransformer
{
    /**
     * Create a new class instance.
     */
     public function transformUser(Collection $users){
        return $users->map(function($user){
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roleName' => $user->roles->pluck('name')->join(', '),
                'is_active' => $user->is_active,
            ];
        })->toArray();
    }

    public function transformRoles($roles){
         $mappedRoles = $roles->map(function($item, $key){
            return ['value'=>$key,'label'=>$item];
        })->values();
         $allRoles = $mappedRoles->prepend([
            'value' => '',
            'label' => 'Select Role',
        ])->values();
        return $allRoles;
    }
}
