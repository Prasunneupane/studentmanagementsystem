<?php

namespace App\Services;

use App\Interface\CommonServiceInterface;
use App\Models\AcademicYears;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\Teachers;

class CommonServices implements CommonServiceInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function academicYearById($academicYearId = null){
        if($academicYearId){
            return $academicYearId;
        }else{
            $activeAcademicYear = \DB::table('tbl_academic_years')->where('is_active', 1)->first();
            return $activeAcademicYear ? $activeAcademicYear->id : null;
        }
    }

    public function getClassList(){
        return Classes::select('id as value', 'name as label')->get()->toArray();
    }

    public function getSubjectList(){
        return Subject::select('id as value', 'name as label')->get()->toArray();
    }

    public function getTeacherList(){
        return Teachers::select('id as value', 'name as label')->get()->toArray();
    }

    public function getClassTeacherForAcademicYear($academicYearId){
        return \DB::table('tbl_class_teachers')
            ->where('academic_year_id', $academicYearId);
    }

    public function getAcademicYearList(){
        return \DB::table('tbl_academic_years')
            ->where('is_active',1)
            ->select('id as value','academic_year as label')
            ->orderBy('start_date','desc')
            ->get()
            ->toArray();
    }   

    public function getCurrentAcademicYear(){
        return AcademicYears::select('id as value','academic_year as label')
             ->where('is_active', 1)->first();
    } 

    // public function getClassList(): array{
    //     return Classes::where('is_active',true)
    //     ->select('name as label','id as value')
    //     ->get()
    //     ->toArray();
    // }

    public function getSectionList($classId): array{
        return \DB::table('tbl_section as s')
        ->join('tbl_class_section as cs', 's.id', '=', 'cs.section_id')
        ->where(
            ['cs.class_id' => $classId,
             'cs.is_active' => 1,
              's.is_active' => 1]
        )->select('s.id as value','s.name as label')
        ->get()
        ->toArray();
        
    }

    public function getClassTeacherById($id): ?array{
        $classTeacher = \DB::table('tbl_class_teachers')
            ->where('id', $id)
            ->first();

        if ($classTeacher) {
            return [
                'id' => $classTeacher->id,
                'class_id' => $classTeacher->class_id,
                'section_id' => $classTeacher->section_id,
                'teacher_id' => $classTeacher->teacher_id,
                'academic_year_id' => $classTeacher->academic_year_id,
                'is_class_teacher' => $classTeacher->is_class_teacher,
                'is_active' => $classTeacher->is_active,
            ];
        }
        return null;
    }

}
