<?php

namespace App\Services;

use App\Contracts\StudentRepositoryInterface;
use App\Contracts\StudentServiceInterface;
use App\Models\Students;
use App\Interface\GuardianInterface;
use App\Repositories\GuardianRepository;
use DB;
use Illuminate\Validation\ValidationException;

class GuardianService implements GuardianInterface
{
    protected $guardianRepository;
    public function __construct(GuardianRepository $guardianRepository)
    {
        $this->guardianRepository = $guardianRepository;
    }
    public function getGuardiansByStudentId(int $studentId): \Illuminate\Support\Collection
    {
        return $this->guardianRepository->getByStudentId($studentId);;
    }
}