<?php

namespace App\Services;

use App\Interface\ClassTeacherInterface;
use App\Models\ClassTeacher;

class ClassTeacherService implements ClassTeacherInterface
{
    
    

    public function create($data){
        $data['created_by'] = auth()->id();
        return ClassTeacher::create($data);
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

    public function unsetExistingClassTeacher($classId, $sectionId, $academicYearId )
    {
        ClassTeacher::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('academic_year_id', $academicYearId)
            ->update(['is_class_teacher' => false]);
    }

    public function checkDuplicateAssignment($data)
    {
        $exist =  ClassTeacher::where('class_id', $data['class_id'])
            ->where('section_id', $data['section_id'])
            ->where('teacher_id', $data['teacher_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->exists();
        
        if ($exist) {
            return back()->withErrors(provider: [
                'teacher_id' => 'This teacher is already assigned to this class-section for the selected academic year.'
            ]);
       
        }
    }
}
