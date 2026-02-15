<?php
namespace App\Interface;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;



interface CommonServiceInterface
{
    public function getClassList();
    public function getSubjectList();
    public function getTeacherList();
    public function getAcademicYearList();
    public function academicYearById($academicYearId = null);
    public function getClassTeacherForAcademicYear($academicYearId);
    public function getCurrentAcademicYear();
    public function getSectionList($classId);
    
}

