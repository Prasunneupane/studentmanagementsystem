<?php

namespace App\Services;

use App\Interface\ClassSubjectInterface;
use App\Models\AcademicYears;
use App\Models\ClassSubject;
use App\Models\Subject;
use App\Models\Teachers;

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

    public function getSubjectList(){
        return Subject::select('id as value', 'name as label')->get()->toArray();
    }

    public function getTeacherList(){
        return Teachers::select('id as value', 'name as label')->get()->toArray();
    }

    public function getCurrentAcademicYear(){
        return AcademicYears::select('id as value','academic_year as label')
             ->where('is_active', 1)->first();
    }   

    public function getClassSubjectById($id){
        return ClassSubject::find($id);
    }

    public function create($data){
        $data['created_by'] = auth()->user()->id;
        return ClassSubject::create($data);
    }

    public function update($id, $data){
        $classSubject = $this->getClassSubjectById($id);
        if($classSubject){
            $data['updated_by'] = auth()->user()->id;
            $classSubject->update($data);
            return $classSubject;
        }
        return null;
    }

    public function checkIfDuplicateRecordExistById($id){
        $classSubject = $this->getClassSubjectById($id);
        if($classSubject){
            return ClassSubject::where('class_id', $classSubject->class_id)
                ->where('section_id', $classSubject->section_id)
                ->where('subject_id', $classSubject->subject_id)
                ->where('academic_year_id', $classSubject->academic_year_id)
                ->where('id', '!=', $id)
                ->exists();
        }
        return false;
    }
}
