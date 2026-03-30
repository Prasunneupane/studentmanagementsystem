<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollments extends Model
{
    protected $table = 'tbl_enrollments';

    protected $fillable = [
        'student_id',
        'class_id',
        'section_id',
        'academic_year_id',
        'roll_no',
        'admission_date',
        'status',
        'remarks',
        'is_active',
        'created_by',
        'updated_by',
    ];
}
