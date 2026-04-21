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

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYears::class, 'academic_year_id');
    }
}
