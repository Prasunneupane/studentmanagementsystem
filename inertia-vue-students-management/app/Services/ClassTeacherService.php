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
}
