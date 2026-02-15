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
        

        return ClassTeacher::with(['class', 'section', 'teacher', 'academicYear'])
            ->forAcademicYear($academicYearId);
    }

    public function getClassTeacherDataWithFilters($query)
    {
       $assignments = $query->select([
                        'id',
                        'class_id',
                        'section_id',
                        'teacher_id',
                        'academic_year_id',
                        'is_class_teacher',
                        'is_active',
                       
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

    public function unsetExistingClassTeacherForUpdate($validated,$currentId)
    {
        ClassTeacher::where('class_id', $validated['class_id'])
                ->where('section_id', $validated['section_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('id', '!=', $currentId)
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

    public function getClassTeacherByAcademicYear($academicYearId)
    {
        return ClassTeacher::where('academic_year_id', $academicYearId)->get();
    }

    public function checkDuplicateAssignmentForUpdate($data, $id)
    {
        $exists= ClassTeacher::where('class_id', $data['class_id'])
            ->where('section_id', $data['section_id'])
            ->where('teacher_id', $data['teacher_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return back()->withErrors([
                'teacher_id' => 'This teacher is already assigned to this class-section for the selected academic year.'
            ]);
        }
    }

    public function update($id, $data)
    {
        $classTeacher = ClassTeacher::find($id);
        if ($classTeacher) {
            $data['updated_by'] = auth()->id();
            $classTeacher->update($data);
            return $classTeacher;
        }
        return null;
    }
}
