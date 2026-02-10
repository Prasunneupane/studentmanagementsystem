<?php

namespace App\Services;

use App\Interface\CommonServiceInterface;
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

}
