<?php

namespace App\Repositories;

use Illuminate\Support\Collection;



interface LocationInterface
{
    public function getAllStates():Collection;
    public function getDistrictsByStateId(int $stateId): Collection;
    public function getMunicipalitiesByDistrictId(int $districtId): Collection;
}
