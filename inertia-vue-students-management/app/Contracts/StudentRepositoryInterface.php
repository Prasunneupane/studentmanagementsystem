<?php

namespace App\Contracts;

use App\Models\Students;

interface StudentRepositoryInterface
{
    public function create(array $data): Students;
}

