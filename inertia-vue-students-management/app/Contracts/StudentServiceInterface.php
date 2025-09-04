<?php

namespace App\Contracts;

use App\Models\Students;

interface StudentServiceInterface
{
    public function createStudent(array $data, int $userId): Students;
}