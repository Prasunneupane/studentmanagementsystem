<?php

namespace App\Repositories;

use App\Models\District;
use App\Models\State;
use Illuminate\Support\Collection;

class LocationRepository implements LocationInterface
{
    /**
     * Create a new class instance.
     */
    public function getAllStates(): Collection
    {
        return State::select('id', 'name')->orderBy('id')->get();

    }
    public function getDistrictsByStateId(int $stateId): Collection
    {
        return State::findOrFail($stateId)->districts()->select('id', 'name')->orderBy('id')->get();
    }

    public function getMunicipalitiesByDistrictId(int $districtId): Collection
    {
        return District::findOrFail($districtId)->municipalities()->select('id', 'name')->orderBy('id')->get();
    }
    
}
