<?php
namespace App\Interface;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;



interface ClassSubjectInterface
{
    public function academicYearById($request);
    
    public function getClassSubjectForAcademicYear($academicYearId);

    public function getClassSubjectDataWithFilters($query);
}

