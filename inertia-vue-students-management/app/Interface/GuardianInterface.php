<?php
namespace App\Interface;

use Illuminate\Support\Collection;



interface GuardianInterface
{
    public function getGuardiansByStudentId(int $studentId): Collection;
}