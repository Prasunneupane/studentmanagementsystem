<?php

namespace App\Interface;

use Illuminate\Support\Collection;

interface StudentMarksInterface
{
    /**
     * Get enrolled students for marks entry (for a specific exam+class+section+subject)
     */
    public function getStudentsForMarksEntry(int $examId, int $classId, int $sectionId, int $subjectId): array;

    /**
     * Save or update marks for multiple students
     */
    public function saveMarks(int $examId, array $marks): void;

    /**
     * Get marks for an exam, grouped by class/section
     */
    public function getMarksByExamAndClass(int $examId, int $classId, ?int $sectionId): Collection;

    /**
     * Get individual student marksheet
     */
    public function getMarksheet(int $examId, int $studentId): array;

    /**
     * Calculate results (total, percentage, grade, rank) for all students in a class/section
     */
    public function calculateResults(int $examId, int $classId, ?int $sectionId): void;

    /**
     * Finalize results (lock from further edits)
     */
    public function finalizeResults(int $examId, int $classId, ?int $sectionId): void;

    /**
     * Get results for an exam+class+section
     */
    public function getResults(int $examId, int $classId, ?int $sectionId): Collection;

    /**
     * Get single student result
     */
    public function getStudentResult(int $examId, int $studentId): ?object;

    /**
     * Get exams that have schedules (for marks entry selection)
     */
    public function getExamsWithSchedules(?int $academicYearId = null): Collection;

    /**
     * Get schedule subjects for an exam+class+section
     */
    public function getScheduleSubjects(int $examId, int $classId, int $sectionId): Collection;

    /**
     * Get student marks history across years
     */
    public function getStudentMarksHistory(int $studentId): array;
}
