<?php

namespace App\Services;

use App\Interface\CommonServiceInterface;
use App\Models\Classes;
use App\Models\Subject;
use App\Models\Teachers;

class CommonServices implements CommonServiceInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getClassList(){
        return Classes::select('id as value', 'name as label')->get()->toArray();
    }

    public function getSubjectList(){
        return Subject::select('id as value', 'name as label')->get()->toArray();
    }

    public function getTeacherList(){
        return Teachers::select('id as value', 'name as label')->get()->toArray();
    }

}
