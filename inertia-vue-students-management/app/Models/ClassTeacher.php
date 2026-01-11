<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassTeacher extends Model
{
    protected $table = 'tbl_class_teachers';

    protected $fillable = [
        'class_id',
        'section_id',
        'teacher_id',
        'academic_year_id',
        'is_class_teacher',
        'is_active',
        'created_by',
        'updated_by',
    ];
}
