<?php

namespace App\Contracts;

use App\Models\Students;

interface StudentServiceInterface
{
    public function createStudent(array $data, int $userId): Students;

    public function getStudentsByDateRange(string $fromDate, string $toDate);

    public function updateStudentById(int $studentId, array $data): Students;

    public function getClassList():array;

    public function getStateList():array;
    public function getDistrictList(int $stateId): array;

    public function getMunicipalityList(int $districtId): array;
    public function getDefaultValue();
    public function getDefaultStates();

    public function getDefaultDistricts();

    public function getDefaultMunicipalities();
}