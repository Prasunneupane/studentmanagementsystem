<?php
namespace App\Interface;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;



interface ClassSubjectInterface
{
    public function academicYearById($request);
    
    public function getClassSubjectForAcademicYear($academicYearId);

    public function getClassSubjectDataWithFilters($query);

    public function getSubjectList();
    public function getTeacherList();

    public function create($data);

    public function getClassSubjectById($id);

    public function update($id, $data);

    public function checkIfDuplicateRecordExistById($id);
}

