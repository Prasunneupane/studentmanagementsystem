<?php

namespace App\Transformers;

use Illuminate\Support\Collection;

class SectionTransformer 
{
    public function transformSection(Collection $sections){
        return $sections->map(function($section){
            return [
                "id"=> $section->id,
                "name"=> $section->name,
            ];
        })->toArray();
    }
   
}
