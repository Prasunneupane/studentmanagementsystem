<?php
namespace App\Interface;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;



interface ExamScheduleInterface
{
    public function getClassSectionByExamId($examId);

    public function getUniqueClassIds(Collection $examClasses);
    public function getSubjectsByClass(Collection $classIds, $exam);

    public function createExam(array $data);

    public function saveExamSchedule($exam, array $schedules);
}