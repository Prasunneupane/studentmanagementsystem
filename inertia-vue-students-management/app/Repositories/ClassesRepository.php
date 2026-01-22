<?php

namespace App\Repositories;

use App\Models\Classes;
use App\Models\Section;
use DB;
use Illuminate\Support\Collection;

class ClassesRepository implements ClassesInterface
{
    /**
     * Create a new class instance.
     */
    

    public function getAllClasses(): Collection{
         return Classes::select('id', 'name')->where('is_active',1)->orderBy('id')->get();
    }
    public function getAllSections(): Collection{
        return Section::select('id', 'name')->where('is_active',true)->orderBy('id')->get();    
    }

    public function getSectionList($classId): array{
        return DB::table('tbl_section as s')
        ->join('tbl_class_section as cs', 's.id', '=', 'cs.section_id')
        ->where(
            ['cs.class_id' => $classId,
             'cs.is_active' => 1,
              's.is_active' => 1]
        )->select('s.id as value','s.name as label')
        ->get()
        ->toArray();
        
    }
}
