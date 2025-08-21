<?php

namespace App\Transformers;

use Illuminate\Support\Collection;

class ClassesTransformer 
{
    public function transformClasses(Collection $classes){
        return $classes->map(function($class){
            return [
                "id"=> $class->id,
                "name"=> $class->name,
            ];
        })->toArray();
    }
   
}
