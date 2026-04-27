<?php

namespace App\Services;

use App\Interface\ExamScheduleInterface;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


class ExamScheduleService implements ExamScheduleInterface
{
    public function getClassSectionByExamId($examId)
    {
        return ExamClass::where('exam_id', $examId)
            
            ->get(['class_id', 'section_id']);
            // ->map(fn($ec) => [
            //     'class_id' => (string) $ec->class_id,
            //     'section_id' => $ec->section_id ? (string) $ec->section_id : null,
            // ]);
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
        // dd(
        //     ClassSubject::whereIn('class_id', [1])
        //     ->where('academic_year_id', $exam->academic_year_id)
        //     ->where('is_active',1)
        //      ->with('subject')
        //     ->get()
        //      ->map(fn($cs) => [
        //             'id' => $cs->subject->id,
        //             'name' => $cs->subject->name,
        //             'code' => $cs->subject->code,
        //         ])
        //     ->toArray()
        // );
        return ClassSubject::whereIn('class_id', $classIds)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('is_active',1)
            ->with('subject')
            ->get()
            ->groupBy('class_id')
            ->map(function ($items) {
                return $items->map(fn($cs) => [
                    'id' => $cs->subject->id,
                    'name' => $cs->subject->name,
                    'code' => $cs->subject->code,
                    'section_id' => $cs->section_id,
                ]);
            });
    }



    public function createExam(array $data)
    {
        try {
            // dd([
            //         'name' => $data['name'],
            //         'exam_type' => $data['exam_type'],
            //         'academic_year_id' => $data['academic_year_id'],
            //         'term_id' => $data['term_id'] ?? null,
            //         'start_date' => $data['start_date'],
            //         'end_date' => $data['end_date'],
            //         'weightage' => $data['weightage'] ?? 100,
            //         'created_by' => Auth::id(),
            //         'is_published' => $data['is_published'] ?? false,
            //     ]);
            $exam = DB::transaction(function () use ($data) {

                $exam = Exam::create([
                    'name' => $data['name'],
                    'exam_type' => $data['exam_type'],
                    'academic_year_id' => $data['academic_year_id'],
                    'term_id' => $data['term_id'] ?? null,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'weightage' => $data['weightage'] ?? 100,
                    'created_by' => Auth::id(),
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
                 ExamSchedule::where('exam_id', $exam->id)->delete();
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
     public function getScheduleByExam(int $examId): array
    {
        return ExamSchedule::with(['class', 'section', 'subject'])
            ->where('exam_id', $examId)
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get()
            ->toArray();
    }

    // public function getScheduleGroupedByClass(int $examId): array
    // {
    //     $schedules = ExamSchedule::with(['class', 'section', 'subject'])
    //         ->where('exam_id', $examId)
    //         ->orderBy('exam_date')
    //         ->orderBy('start_time')
    //         ->get();

    //     $grouped = [];
    //     foreach ($schedules as $schedule) {
    //         $classId  = $schedule->class_id;
    //         $sectionId = $schedule->section_id;
    //         $key = "{$classId}_{$sectionId}";

    //         if (!isset($grouped[$key])) {
    //             $grouped[$key] = [
    //                 'class_id'     => $classId,
    //                 'class_name'   => $schedule->class->name ?? 'N/A',
    //                 'section_id'   => $sectionId,
    //                 'section_name' => $schedule->section->name ?? 'N/A',
    //                 'schedules'    => [],
    //             ];
    //         }

    //         $grouped[$key]['schedules'][] = [
    //             'id'                  => $schedule->id,
    //             'subject_name'        => $schedule->subject->name ?? 'N/A',
    //             'exam_date'           => $schedule->exam_date,
    //             'start_time'          => $schedule->start_time,
    //             'end_time'            => $schedule->end_time,
    //             'room_no'             => $schedule->room_no,
    //             'max_theory_marks'    => $schedule->max_theory_marks,
    //             'max_practical_marks' => $schedule->max_practical_marks,
    //             'max_total_marks'     => $schedule->max_total_marks,
    //             'pass_marks'          => $schedule->pass_marks,
    //         ];
    //     }

    //     return array_values($grouped);
    // }

    // public function getExamWithDetails(int $examId): ?object
    // {
    //     return Exam::with(['academicYear', 'term', 'examClasses.class', 'examClasses.section'])
    //         ->find($examId);
    // }

     public function getAllSchedulesForIndex(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Exam::with(['academicYear', 'term'])
            ->withCount('examSchedules')
            ->withCount('examClasses')
            ->when(request('academic_year_id'), fn($q) => $q->where('academic_year_id', request('academic_year_id')))
            ->when(request('exam_type'),        fn($q) => $q->where('exam_type', request('exam_type')))
            ->when(request('status') && request('status') !== 'all',
                fn($q) => $q->where('status', request('status'))
            )
            ->when(request('search'),
                fn($q) => $q->where('name', 'like', '%' . request('search') . '%')
            )
            ->orderBy('start_date', 'desc')
            ->paginate(12)
            ->withQueryString();
    }

    // public function getScheduleByExam(int $examId): array
    // {
    //     return ExamSchedule::with(['class', 'section', 'subject'])
    //         ->where('exam_id', $examId)
    //         ->orderBy('exam_date')
    //         ->orderBy('start_time')
    //         ->get()
    //         ->toArray();
    // }

    public function getScheduleGroupedByClass(int $examId): array
    {
        $schedules = ExamSchedule::with(['class', 'section', 'subject'])
            ->where('exam_id', $examId)
            ->orderBy('class_id')
            ->orderBy('section_id')
            ->orderBy('exam_date')
            ->get();
        $grouped = [];
        foreach ($schedules as $schedule) {
            $key = "{$schedule->class_id}_{$schedule->section_id}";
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'class_id'     => $schedule->class_id,
                    'class_name'   => $schedule->class->name  ?? 'N/A',
                    'section_id'   => $schedule->section_id,
                    'section_name' => $schedule->section->name ?? 'N/A',
                    'schedules'    => [],
                ];
            }
            $grouped[$key]['schedules'][] = [
                'id'                  => $schedule->id,
                'subject_name'        => $schedule->subject->name ?? 'N/A',
                'exam_date'           => $schedule->exam_date,
                'start_time'          => $schedule->start_time,
                'end_time'            => $schedule->end_time,
                'room_no'             => $schedule->room_no,
                'max_theory_marks'    => $schedule->max_theory_marks,
                'max_practical_marks' => $schedule->max_practical_marks,
                'max_total_marks'     => $schedule->max_total_marks,
                'pass_marks'          => $schedule->pass_marks,
            ];
        }
        return array_values($grouped);
    }

    public function getExamWithDetails(int $examId): ?object
    {
        return Exam::with(['academicYear', 'term', 'examClasses.class', 'examClasses.section'])
            ->find($examId);
    }

    public function deleteSchedule(int $scheduleId): bool
    {
        $schedule = ExamSchedule::findOrFail($scheduleId);
        return $schedule->delete();
    }

    public function toggleActive(int $scheduleId): object
    {
        $schedule = ExamSchedule::findOrFail($scheduleId);
        // Toggles is_active on the parent Exam
        $exam = Exam::findOrFail($schedule->exam_id);
        $exam->is_active = !$exam->is_active;
        $exam->save();
        return $exam;
    }

    public function getExistingScheduleMap(int $examId): array
    {
        $rows = ExamSchedule::where('exam_id', $examId)->get();
 
        $map = [];
        foreach ($rows as $row) {
            $key        = "{$row->class_id}_{$row->section_id}_{$row->subject_id}";
            $map[$key]  = [
                'id'                  => $row->id,
                'subject_id'          => (string) $row->subject_id,
                'exam_date'           => $row->exam_date ?? '',
                'start_time'          => $row->start_time ?? '',
                'end_time'            => $row->end_time ?? '',
                'room_no'             => $row->room_no ?? '',
                'max_theory_marks'    => (string) ($row->max_theory_marks ?? '80'),
                'max_practical_marks' => (string) ($row->max_practical_marks ?? '20'),
                'max_total_marks'     => (string) ($row->max_total_marks ?? '100'),
                'pass_marks'          => (string) ($row->pass_marks ?? '40'),
            ];
        }
 
        return $map;
    }
    public function updateExamSchedule($exam, array $schedules): void
    {
        try {
            DB::transaction(function () use ($exam, $schedules) {
                // Only delete schedule rows, NOT the exam-class mappings
                ExamSchedule::where('exam_id', $exam->id)->delete();
 
                $insertRows = collect($schedules)->map(fn($s) => [
                    'exam_id'              => $exam->id,
                    'class_id'             => $s['class_id'],
                    'section_id'           => $s['section_id'] ?? null,
                    'subject_id'           => $s['subject_id'] ?? null,
                    'exam_date'            => $s['exam_date'] ?? null,
                    'start_time'           => $s['start_time'] ?? null,
                    'end_time'             => $s['end_time'] ?? null,
                    'room_no'              => $s['room_no'] ?? null,
                    'max_theory_marks'     => $s['max_theory_marks'] ?? null,
                    'max_practical_marks'  => $s['max_practical_marks'] ?? null,
                    'max_total_marks'      => $s['max_total_marks'] ?? null,
                    'pass_marks'           => $s['pass_marks'] ?? null,
                ])->toArray();
 
                if (!empty($insertRows)) {
                    ExamSchedule::insert($insertRows);
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to update exam schedule', [
                'message'   => $e->getMessage(),
                'exam_id'   => $exam->id,
                'schedules' => $schedules,
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}