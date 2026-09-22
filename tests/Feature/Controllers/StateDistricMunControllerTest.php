<?php

use App\Http\Controllers\StateDistricMunController;
use Illuminate\Http\Request;

test('state district municipality controller json endpoints return response objects', function () {
    $controller = app(StateDistricMunController::class);

    $statesResponse = $controller->getAllStates();
    $districtsResponse = $controller->getDistrictsByStateId(new Request(['state_id' => 1]));
    $municipalitiesResponse = $controller->getMunicipalitiesByDistrictId(new Request(['district_id' => 1]));

    expect($statesResponse)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($districtsResponse)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($municipalitiesResponse)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
