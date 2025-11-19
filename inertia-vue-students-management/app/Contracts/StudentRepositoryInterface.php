<?php

namespace App\Contracts;

use App\Models\Students;

interface StudentRepositoryInterface
{
    public function create(array $data): Students;

    public function getStudentsByDateRange(string $fromDate, string $toDate): array;

    public function updateStudentById(int $studentId, array $data): Students;
}

