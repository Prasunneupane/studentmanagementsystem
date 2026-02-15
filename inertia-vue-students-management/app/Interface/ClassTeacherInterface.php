<?php
namespace App\Interface;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;



interface ClassTeacherInterface
{
    
    public function create($data);
    // public function getClassSubjectById($id);

    public function getClassTeacherDataWithFilters($query);

    public function unsetExistingClassTeacher($classId, $sectionId, $academicYearId );

    public function checkDuplicateAssignment($data);
}

