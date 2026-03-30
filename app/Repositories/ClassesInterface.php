<?php

namespace App\Repositories;

use Illuminate\Support\Collection;

interface ClassesInterface
{
    public function getAllClasses():Collection;

    public function getAllSections():Collection;
}
