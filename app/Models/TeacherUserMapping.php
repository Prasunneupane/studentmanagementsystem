<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherUserMapping extends Model
{
    protected $table = 'tbl_teacher_user';

    protected $fillable = [
        'teacher_id',
        'user_id',
        'is_active',
        'created_by',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teachers::class, 'teacher_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    
}
