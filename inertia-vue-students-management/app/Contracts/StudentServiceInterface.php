<?php

namespace App\Contracts;

use App\Models\Students;

interface StudentServiceInterface
{
    public function createStudent(array $data, int $userId): Students;

    public function getStudentsByDateRange(string $fromDate, string $toDate): array;

    public function updateStudentById(int $studentId, array $data): Students;
}