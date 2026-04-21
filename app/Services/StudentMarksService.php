<?php

namespace App\Services;

use App\Interface\StudentMarksInterface;
use App\Models\Enrollments;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\StudentMark;
use App\Models\StudentResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentMarksService implements StudentMarksInterface
{
    /**
     * Nepal grading scale
     */
    private function getGrade(float $percentage): array
    {
        if ($percentage >= 90) return ['grade' => 'A+', 'gpa' => 4.00];
        if ($percentage >= 80) return ['grade' => 'A',  'gpa' => 3.60];
        if ($percentage >= 70) return ['grade' => 'B+', 'gpa' => 3.20];
        if ($percentage >= 60) return ['grade' => 'B',  'gpa' => 2.80];
        if ($percentage >= 50) return ['grade' => 'C+', 'gpa' => 2.40];
        if ($percentage >= 40) return ['grade' => 'C',  'gpa' => 2.00];
        if ($percentage >= 30) return ['grade' => 'D+', 'gpa' => 1.60];
        if ($percentage >= 20) return ['grade' => 'D',  'gpa' => 1.20];
        return ['grade' => 'NG', 'gpa' => 0.00]; // Not Graded (Fail)
    }

    /**
     * Get subject grade for individual subject (Nepal system)
     */
    private function getSubjectGrade(float $percentage): string
    {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B+';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C+';
        if ($percentage >= 40) return 'C';
        if ($percentage >= 30) return 'D+';
        if ($percentage >= 20) return 'D';
        return 'NG';
    }

    public function getStudentsForMarksEntry(int $examId, int $classId, int $sectionId, int $subjectId): array
    {
        $exam = Exam::findOrFail($examId);

        // Get enrolled students for this class/section/academic year
        $enrollments = Enrollments::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('is_active', true)
            ->with('student')
            ->orderBy('roll_no')
            ->get();

        // Get the exam schedule for max marks info
        $schedule = ExamSchedule::where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->first();

        // Get existing marks
        $existingMarks = StudentMark::where('exam_id', $examId)
            ->where('subject_id', $subjectId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->get()
            ->keyBy('student_id');

        $students = [];
        foreach ($enrollments as $enrollment) {
            $student = $enrollment->student;
            if (!$student) continue;

            $mark = $existingMarks->get($student->id);

            $students[] = [
                'student_id' => $student->id,
                'roll_no' => $enrollment->roll_no,
                'name' => $student->full_name,
                'photo_url' => $student->photo_url,
                'theory_marks' => $mark?->theory_marks ?? '',
                'practical_marks' => $mark?->practical_marks ?? '',
                'total_marks' => $mark?->total_marks ?? '',
                'is_absent' => $mark?->is_absent ?? false,
                'remarks' => $mark?->remarks ?? '',
            ];
        }

        return [
            'students' => $students,
            'schedule' => $schedule ? [
                'id' => $schedule->id,
                'max_theory_marks' => $schedule->max_theory_marks,
                'max_practical_marks' => $schedule->max_practical_marks,
                'max_total_marks' => $schedule->max_total_marks,
                'pass_marks' => $schedule->pass_marks,
                'exam_date' => $schedule->exam_date,
            ] : null,
        ];
    }

    public function saveMarks(int $examId, array $marks): void
    {
        try {
            DB::transaction(function () use ($examId, $marks) {
                $exam = Exam::findOrFail($examId);

                foreach ($marks as $mark) {
                    // Find the schedule for max marks reference
                    $schedule = ExamSchedule::where('exam_id', $examId)
                        ->where('class_id', $mark['class_id'])
                        ->where('section_id', $mark['section_id'])
                        ->where('subject_id', $mark['subject_id'])
                        ->first();

                    $theoryMarks = $mark['is_absent'] ? null : ($mark['theory_marks'] !== '' ? $mark['theory_marks'] : null);
                    $practicalMarks = $mark['is_absent'] ? null : ($mark['practical_marks'] !== '' ? $mark['practical_marks'] : null);

                    $totalMarks = null;
                    if (!$mark['is_absent'] && ($theoryMarks !== null || $practicalMarks !== null)) {
                        $totalMarks = ($theoryMarks ?? 0) + ($practicalMarks ?? 0);
                    }

                    StudentMark::updateOrCreate(
                        [
                            'exam_id' => $examId,
                            'student_id' => $mark['student_id'],
                            'subject_id' => $mark['subject_id'],
                        ],
                        [
                            'exam_schedule_id' => $schedule?->id,
                            'class_id' => $mark['class_id'],
                            'section_id' => $mark['section_id'],
                            'academic_year_id' => $exam->academic_year_id,
                            'theory_marks' => $theoryMarks,
                            'practical_marks' => $practicalMarks,
                            'total_marks' => $totalMarks,
                            'is_absent' => $mark['is_absent'] ?? false,
                            'remarks' => $mark['remarks'] ?? null,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]
                    );
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to save marks', [
                'exam_id' => $examId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function getMarksByExamAndClass(int $examId, int $classId, ?int $sectionId): Collection
    {
        return StudentMark::with(['student', 'subject'])
            ->where('exam_id', $examId)
            ->where('class_id', $classId)
            ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->get();
    }

    public function getMarksheet(int $examId, int $studentId): array
    {
        $exam = Exam::with(['academicYear', 'term'])->findOrFail($examId);

        $marks = StudentMark::with(['subject', 'examSchedule'])
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->get();

        $result = StudentResult::where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->first();

        // Get enrollment info
        $enrollment = Enrollments::with(['student', 'student.class', 'student.section'])
            ->where('student_id', $studentId)
            ->where('academic_year_id', $exam->academic_year_id)
            ->first();

        $subjectMarks = $marks->map(function ($mark) {
            $maxTotal = $mark->examSchedule
                ? ($mark->examSchedule->max_theory_marks + $mark->examSchedule->max_practical_marks)
                : 100;
            $pct = $maxTotal > 0 ? ($mark->total_marks / $maxTotal) * 100 : 0;

            return [
                'subject_name' => $mark->subject->name ?? 'N/A',
                'subject_code' => $mark->subject->code ?? '',
                'theory_marks' => $mark->theory_marks,
                'practical_marks' => $mark->practical_marks,
                'total_marks' => $mark->total_marks,
                'max_theory_marks' => $mark->examSchedule->max_theory_marks ?? 80,
                'max_practical_marks' => $mark->examSchedule->max_practical_marks ?? 20,
                'max_total_marks' => $mark->examSchedule->max_total_marks ?? 100,
                'pass_marks' => $mark->examSchedule->pass_marks ?? 40,
                'is_absent' => $mark->is_absent,
                'grade' => $mark->is_absent ? 'AB' : $this->getSubjectGrade($pct),
                'status' => $mark->is_absent
                    ? 'absent'
                    : ($mark->total_marks >= ($mark->examSchedule->pass_marks ?? 40) ? 'pass' : 'fail'),
                'remarks' => $mark->remarks,
            ];
        });

        return [
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'exam_type' => $exam->exam_type,
                'academic_year' => $exam->academicYear->name ?? 'N/A',
                'term' => $exam->term->name ?? null,
            ],
            'student' => $enrollment ? [
                'id' => $enrollment->student->id,
                'name' => $enrollment->student->full_name,
                'roll_no' => $enrollment->roll_no,
                'class_name' => $enrollment->student->class?->name ?? 'N/A',
                'section_name' => $enrollment->student->section?->name ?? 'N/A',
                'photo_url' => $enrollment->student->photo_url,
            ] : null,
            'subjects' => $subjectMarks,
            'result' => $result ? [
                'total_marks_obtained' => $result->total_marks_obtained,
                'total_max_marks' => $result->total_max_marks,
                'percentage' => $result->percentage,
                'grade' => $result->grade,
                'gpa' => $result->gpa,
                'rank' => $result->rank,
                'status' => $result->status,
                'is_finalized' => $result->is_finalized,
            ] : null,
        ];
    }

    public function calculateResults(int $examId, int $classId, ?int $sectionId): void
    {
        try {
            DB::transaction(function () use ($examId, $classId, $sectionId) {
                $exam = Exam::findOrFail($examId);

                // Get all students who have marks for this exam+class+section
                $studentIds = StudentMark::where('exam_id', $examId)
                    ->where('class_id', $classId)
                    ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                    ->distinct()
                    ->pluck('student_id');

                $resultsData = [];

                foreach ($studentIds as $studentId) {
                    $marks = StudentMark::with('examSchedule')
                        ->where('exam_id', $examId)
                        ->where('student_id', $studentId)
                        ->get();

                    $totalObtained = 0;
                    $totalMax = 0;
                    $allPassed = true;

                    foreach ($marks as $mark) {
                        $maxMarks = $mark->examSchedule
                            ? ($mark->examSchedule->max_theory_marks + $mark->examSchedule->max_practical_marks)
                            : 100;
                        $passMarks = $mark->examSchedule->pass_marks ?? 40;

                        $totalObtained += $mark->is_absent ? 0 : ($mark->total_marks ?? 0);
                        $totalMax += $maxMarks;

                        if ($mark->is_absent || ($mark->total_marks ?? 0) < $passMarks) {
                            $allPassed = false;
                        }
                    }

                    $percentage = $totalMax > 0 ? ($totalObtained / $totalMax) * 100 : 0;
                    $gradeInfo = $this->getGrade($percentage);

                    $resultsData[] = [
                        'student_id' => $studentId,
                        'total_obtained' => $totalObtained,
                        'total_max' => $totalMax,
                        'percentage' => round($percentage, 2),
                        'grade' => $gradeInfo['grade'],
                        'gpa' => $gradeInfo['gpa'],
                        'status' => $allPassed ? 'pass' : 'fail',
                    ];
                }

                // Sort by percentage desc for ranking
                usort($resultsData, fn($a, $b) => $b['percentage'] <=> $a['percentage']);

                $rank = 1;
                foreach ($resultsData as $i => $data) {
                    // Handle tied ranks
                    if ($i > 0 && $data['percentage'] < $resultsData[$i - 1]['percentage']) {
                        $rank = $i + 1;
                    }

                    StudentResult::updateOrCreate(
                        [
                            'exam_id' => $examId,
                            'student_id' => $data['student_id'],
                        ],
                        [
                            'class_id' => $classId,
                            'section_id' => $sectionId,
                            'academic_year_id' => $exam->academic_year_id,
                            'total_marks_obtained' => $data['total_obtained'],
                            'total_max_marks' => $data['total_max'],
                            'percentage' => $data['percentage'],
                            'grade' => $data['grade'],
                            'gpa' => $data['gpa'],
                            'rank' => $data['status'] === 'pass' ? $rank : null,
                            'status' => $data['status'],
                            'is_finalized' => false,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]
                    );

                    if ($data['status'] === 'pass') {
                        $rank++;
                    }
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to calculate results', [
                'exam_id' => $examId,
                'class_id' => $classId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function finalizeResults(int $examId, int $classId, ?int $sectionId): void
    {
        StudentResult::where('exam_id', $examId)
            ->where('class_id', $classId)
            ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->update([
                'is_finalized' => true,
                'finalized_by' => Auth::id(),
                'finalized_at' => now(),
            ]);
    }

    public function getResults(int $examId, int $classId, ?int $sectionId): Collection
    {
        return StudentResult::with(['student', 'class', 'section'])
            ->where('exam_id', $examId)
            ->where('class_id', $classId)
            ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->orderBy('rank')
            ->orderByDesc('percentage')
            ->get();
    }

    public function getStudentResult(int $examId, int $studentId): ?object
    {
        return StudentResult::with(['student', 'class', 'section'])
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->first();
    }

    public function getExamsWithSchedules(?int $academicYearId = null): Collection
    {
        return Exam::with(['academicYear', 'term'])
            ->has('examSchedules')
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->orderByDesc('start_date')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'exam_type' => $e->exam_type,
                'academic_year' => $e->academicYear->name ?? 'N/A',
                'academic_year_id' => $e->academic_year_id,
                'start_date' => $e->start_date,
                'end_date' => $e->end_date,
            ]);
    }

    public function getScheduleSubjects(int $examId, int $classId, int $sectionId): Collection
    {
        return ExamSchedule::with('subject')
            ->where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'subject_id' => $s->subject_id,
                'subject_name' => $s->subject->name ?? 'N/A',
                'subject_code' => $s->subject->code ?? '',
                'exam_date' => $s->exam_date,
                'max_theory_marks' => $s->max_theory_marks,
                'max_practical_marks' => $s->max_practical_marks,
                'max_total_marks' => $s->max_total_marks,
                'pass_marks' => $s->pass_marks,
            ]);
    }

    public function getStudentMarksHistory(int $studentId): array
    {
        // Get all results grouped by academic year → exam
        $results = StudentResult::with(['exam.academicYear', 'exam.term', 'class', 'section'])
            ->where('student_id', $studentId)
            ->orderByDesc('academic_year_id')
            ->get();

        $history = [];
        foreach ($results as $result) {
            $yearName = $result->exam?->academicYear?->name ?? 'N/A';
            if (!isset($history[$yearName])) {
                $history[$yearName] = [];
            }

            $history[$yearName][] = [
                'exam_id' => $result->exam_id,
                'exam_name' => $result->exam->name ?? 'N/A',
                'exam_type' => $result->exam->exam_type ?? 'N/A',
                'class_name' => $result->class->name ?? 'N/A',
                'section_name' => $result->section->name ?? 'N/A',
                'total_marks_obtained' => $result->total_marks_obtained,
                'total_max_marks' => $result->total_max_marks,
                'percentage' => $result->percentage,
                'grade' => $result->grade,
                'gpa' => $result->gpa,
                'rank' => $result->rank,
                'status' => $result->status,
                'is_finalized' => $result->is_finalized,
            ];
        }

        return $history;
    }
}
