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

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
    public function teacher()
    {
        return $this->belongsTo(Teachers::class, 'teacher_id');
    }
    public function academicYear()
    {
        return $this->belongsTo(AcademicYears::class, 'academic_year_id');
    }

    public function scopeIsClassTeacher($query)
    {
        return $query->where('is_class_teacher', 1);
    }
}
