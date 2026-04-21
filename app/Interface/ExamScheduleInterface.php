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
     public function getScheduleByExam(int $examId): array;
    public function getScheduleGroupedByClass(int $examId): array;
    public function getExamWithDetails(int $examId): ?object;
    public function getAllSchedulesForIndex(): \Illuminate\Pagination\LengthAwarePaginator;
    public function deleteSchedule(int $scheduleId): bool;
    public function toggleActive(int $scheduleId): object;
    public function getExistingScheduleMap(int $examId): array;
    public function updateExamSchedule($exam, array $schedules): void;
}