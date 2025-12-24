<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    //
    protected $table = 'tbl_permissions';
    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
        'is_active',
        'created_by',
    ];

    public function roles()
    {
        return $this->belongsToMany(Roles::class, 'tbl_role_permission', 'permission_id', 'role_id')
                    ->withTimestamps();
    }
}
