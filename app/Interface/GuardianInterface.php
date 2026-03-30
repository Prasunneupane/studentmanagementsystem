<?php
namespace App\Interface;

use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;



interface GuardianInterface
{
    public function getGuardiansByStudentId(int $studentId): Collection;

public function updateGuardianByGuardianId(int $guardianId, array $data): int;
public function deleteGuardianByGuardianId(int $guardianId): int;}

