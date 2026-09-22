<?php

use App\Http\Controllers\GuardianController;
use Illuminate\Http\Request;

test('guardian controller json endpoints return response objects', function () {
    $controller = app(GuardianController::class);

    $guardiansResponse = $controller->getGuardiansByStudentId(new Request(['student_id' => 1]), 1);
    $updateResponse = $controller->update_guardian_by_guardian_id(new Request(), 1);
    $deleteResponse = $controller->delete_guardian_by_guardian_id(new Request(), 1);

    expect($guardiansResponse)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($updateResponse)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
        ->and($deleteResponse)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
