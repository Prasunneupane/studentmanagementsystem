<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
     protected $table = 'tbl_roles';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'tbl_role_permission', 'role_id', 'permission_id')
                    ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tbl_user_roles', 'role_id', 'user_id')
                    ->withTimestamps();
    }
    public function parent()
    {
        return $this->belongsTo(Roles::class, 'parent_id');
    }

}
