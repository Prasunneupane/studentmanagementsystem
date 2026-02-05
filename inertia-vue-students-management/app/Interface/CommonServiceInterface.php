<?php
namespace App\Interface;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;



interface CommonServiceInterface
{
    public function getClassList();
    public function getSubjectList();
    public function getTeacherList();

    public function academicYearById($academicYearId = null);

    public function getClassTeacherForAcademicYear($academicYearId);
    
}

