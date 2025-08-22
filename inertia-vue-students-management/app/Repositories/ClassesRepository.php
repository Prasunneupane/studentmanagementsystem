<?php

namespace App\Repositories;

use App\Models\Classes;
use App\Models\Section;
use Illuminate\Support\Collection;

class ClassesRepository implements ClassesInterface
{
    /**
     * Create a new class instance.
     */
    

    public function getAllClasses(): Collection{
         return Classes::select('id', 'name')->where('is_active',true)->orderBy('id')->get();
    }
    public function getAllSections(): Collection{
        return Section::select('id', 'name')->where('is_active',true)->orderBy('id')->get();    
    }
}
