<?php

namespace App\Services;

use App\Models\ClassTeacher;

class ClassTeacherService 
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getClassTeacherForAcademicYear($academicYearId)
    {
        return ClassTeacher ::where('academic_year_id', $academicYearId)->where('is_active', true);
    }

    public function getClassTeacherDataWithFilters($query)
    {
       $assignments = $query->select([
                        'id',
                        'class_id',
                        'section_id',
                        'subject_id',
                        'teacher_id',
                        'academic_year_id',
                        'is_optional',
                        'periods_per_week',
                        'max_marks',
                        'pass_marks',
                       
                    ]);
        return $assignments;
    }
}
