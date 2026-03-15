<?php
namespace App\Interface;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;



interface ExamScheduleInterface
{
    public function getClassSectionByExamId($examId);
}