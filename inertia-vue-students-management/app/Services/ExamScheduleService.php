<?php

namespace App\Services;

use App\Interface\ExamScheduleInterface;
use App\Models\ExamClass;


class ExamScheduleService implements ExamScheduleInterface
{
    public function getClassSectionByExamId($examId)
    {
        return  ExamClass::where('exam_id', $examId)
            ->get(['class_id', 'section_id'])
            ->map(fn($ec) => [
                'class_id'   => (string) $ec->class_id,
                'section_id' => $ec->section_id ? (string) $ec->section_id : null,
            ]);
    }
}