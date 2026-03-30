<?php

namespace App\Transformers;

use Illuminate\Support\Collection;

class LocationTransformer
{
    public function transformState(Collection $states){
        return $states->map(function($state){
            return [
                "id"=> $state->id,
                "name"=> $state->name,
            ];
        })->toArray();
    }
    public function transformDistrict(Collection $districts){
        return $districts->map(function($district){
            return [
                "id"=> $district->id,
                "name"=> $district->name,
            ];
        })->toArray();
    }

    public function transformMunicipality(Collection $municipalities){
        return $municipalities->map(function($municipality){
            return [
                "id"=> $municipality->id,
                "name"=> $municipality->name,
            ];
        })->toArray();
    }
}
