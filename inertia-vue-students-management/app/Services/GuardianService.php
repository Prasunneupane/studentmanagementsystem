<?php

namespace App\Services;

use App\Contracts\StudentRepositoryInterface;
use App\Contracts\StudentServiceInterface;
use App\Models\Students;
use App\Repositories\GuardianInterface;
use App\Repositories\GuardianRepository;
use DB;
use Illuminate\Validation\ValidationException;

class GuardianService implements GuardianInterface
{
    public function getGuardiansByStudentId(int $studentId): \Illuminate\Support\Collection
    {
        return GuardianRepository::where('student_id', $studentId)->get();
    }
}