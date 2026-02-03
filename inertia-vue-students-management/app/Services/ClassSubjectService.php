<?php

namespace App\Services;

use App\Interface\ClassSubjectInterface;
use App\Models\ClassSubject;

class ClassSubjectService implements ClassSubjectInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function academicYearById($request)
    {
        return $request->input('academic_year_id', \DB::table('tbl_academic_years')->where('is_active', 1)->first()?->id);
    }

    public function getClassSubjectForAcademicYear($academicYearId)
    {
        return ClassSubject::with(['class', 'section', 'subject', 'teacher', 'academicYear'])
            ->forAcademicYear($academicYearId);
    }

    public function getClassSubjectDataWithFilters($query)
    {
        // Implement filtering logic here if needed
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
