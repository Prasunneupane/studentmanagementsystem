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

    public function updateGuardianByGuardianId(int $guardianId, array $data): int
    {
        // dump($guardianId);
        $data =[
            'student_id' => $data['student_id'] ?? null,
            'name' => $data['name'] ?? null,
            'relation' => $data['relation'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'is_primary_contact' => $data['is_primary_contact'] ?? null,
            
        ];
        // dd($data);
        return $this->guardianRepository->update($guardianId,$data);;
    }

    public function deleteGuardianByGuardianId(int $guardianId): int
    {
        return $this->guardianRepository->delete($guardianId);;
    }
}