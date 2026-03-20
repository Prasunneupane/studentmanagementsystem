<?php
namespace App\Interface;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;



interface ExamScheduleInterface
{
    public function getClassSectionByExamId($examId);

    public function getUniqueClassIds(Collection $examClasses): Collection;
    public function getSubjectsByClass(Collection $classIds, $exam);
}