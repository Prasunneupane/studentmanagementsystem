<?php

namespace App\Repositories;

use App\Models\Classes;
use Illuminate\Support\Collection;

class ClassesRepository implements ClassesInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAllClasses(): Collection{
         return Classes::select('id', 'name')->where('is_active',true)->orderBy('id')->get();
    }
}
