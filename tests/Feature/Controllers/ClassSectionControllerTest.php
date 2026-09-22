<?php

use App\Http\Controllers\ClassSectionController;
use Illuminate\Http\Request;

test('class section controller returns json payloads', function () {
    $controller = app(ClassSectionController::class);

    $classesResponse = $controller->getAllClasses();
    $sectionsResponse = $controller->getAllSection();
    $byClassResponse = $controller->get_sections_by_class_id(new Request(['class_id' => 1]));

    expect($classesResponse)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($sectionsResponse)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($byClassResponse)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
