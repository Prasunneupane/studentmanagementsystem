<?php

namespace App\Services;

use App\Interface\ExamScheduleInterface;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamSchedule;
use App\Models\Subject;
use DB;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Eloquent\Collection;
use Log;


class ExamScheduleService implements ExamScheduleInterface
{
    public function getClassSectionByExamId($examId)
    {
        return ExamClass::where('exam_id', $examId)
            ->get(['class_id', 'section_id'])
            ->map(fn($ec) => [
                'class_id' => (string) $ec->class_id,
                'section_id' => $ec->section_id ? (string) $ec->section_id : null,
            ]);
    }

    public function getUniqueClassIds($examClasses)
    {
        return $examClasses->pluck('class_id')->unique()->values();
    }

    // public function getSubjectsByClass($classIds, $exam)
    // {
    //     $subjectsByClass = [];
    //     foreach ($classIds as $classId) {
    //         $subjectsByClass[(string) $classId] = ClassSubject::whereHas('classSubjects', function ($q) use ($classId, $exam) {
    //             $q->where('class_id', $classId)
    //                 ->where('academic_year_id', $exam->academic_year_id);
    //         })
    //             ->get(['id', 'name', 'code'])
    //             ->map(fn($s) => [
    //                 'id' => $s->id,
    //                 'name' => $s->name,
    //                 'code' => $s->code,
    //             ]);
    //     }
    //     return $subjectsByClass;
    // }

    public function getSubjectsByClass($classIds, $exam)
    {
        return ClassSubject::whereIn('class_id', $classIds)
            ->where('academic_year_id', $exam->academic_year_id)
            ->with('subject')
            ->get()
            ->groupBy('class_id')
            ->map(function ($items) {
                return $items->map(fn($cs) => [
                    'id' => $cs->subject->id,
                    'name' => $cs->subject->name,
                    'code' => $cs->subject->code,
                ]);
            });
    }



    public function createExam(array $data)
    {
        try {

            $exam = DB::transaction(function () use ($data) {

                $exam = Exam::create([
                    'name' => $data['name'],
                    'exam_type' => $data['exam_type'],
                    'academic_year_id' => $data['academic_year_id'],
                    'term_id' => $data['term_id'] ?? null,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'weightage' => $data['weightage'] ?? 100,
                    'created_by' => auth()->id(),
                    'is_published' => $data['is_published'] ?? false,
                ]);

                $insertRows = collect($data['exam_classes'])->map(fn($ec) => [
                    'exam_id' => $exam->id,
                    'class_id' => $ec['class_id'],
                    'section_id' => $ec['section_id'] ?? null,
                ])->toArray();

                ExamClass::insert($insertRows);

                return $exam;
            });

            return $exam;

        } catch (\Exception $e) {

            // ✅ Log to Laravel log file
            Log::error('Exam Creation Failed', [
                'message' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);

            // ✅ OPTIONAL: Save to DB log table
            // DB::table('error_logs')->insert([
            //     'message' => $e->getMessage(),
            //     'context' => json_encode($data),
            //     'created_at' => now(),
            // ]);

            throw $e;
        }
    }

    public function saveExamSchedule($exam, array $schedules)
    {
        try {
            $examSchedule = DB::transaction(function () use ($exam, $schedules) {

                // Delete existing schedules for this exam
                ExamClass::where('exam_id', $exam->id)->delete();
                // dd($schedules);
                // Insert new schedules
                $insertRows = collect($schedules)->map(fn($s) => [
                    'exam_id' => $exam->id,
                    'class_id' => $s['class_id'],
                    'section_id' => $s['section_id'] ?? null,
                    'subject_id' => $s['subject_id']?? null,
                    'exam_date' => $s['exam_date']?? null,
                    'start_time' => $s['start_time']?? null,
                    'end_time' => $s['end_time']?? null,
                    'room_no' => $s['room_no'] ?? null,
                    'max_theory_marks' => $s['max_theory_marks'] ?? null,
                    'max_practical_marks' => $s['max_practical_marks'] ?? null, 
                    'max_total_marks' => $s['max_total_marks'] ?? null,
                    'pass_marks' => $s['pass_marks'] ?? null,

                ])->toArray();
                // dd($insertRows);
                ExamSchedule::insert($insertRows);
            });
            return $examSchedule;
        } catch (\Exception $e) {
            Log::error('Failed to save exam schedule', [
                'message' => $e->getMessage(),
                'exam_id' => $exam->id,
                'schedules' => $schedules,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}