<?php

namespace App\Services;

use App\Interface\ExamScheduleInterface;
use App\Models\ClassSubject;
use App\Models\ExamClass;
use App\Models\Subject;


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

    public function getUniqueClassIds($examClasses)
    {
        return $examClasses->pluck('class_id')->unique()->values();
    }

    public function getSubjectsByClass($classIds, $exam)
    {
        $subjectsByClass = [];
        foreach ($classIds as $classId) {
            $subjectsByClass[(string) $classId] = ClassSubject::whereHas('classSubjects', function ($q) use ($classId, $exam) {
                    $q->where('class_id', $classId)
                      ->where('academic_year_id', $exam->academic_year_id);
                })
                ->get(['id', 'name', 'code'])
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                ]);
        }
        return $subjectsByClass;
    }
}