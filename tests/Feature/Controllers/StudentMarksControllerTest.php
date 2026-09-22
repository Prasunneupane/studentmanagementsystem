<?php

use App\Http\Controllers\StudentMarksController;
use Illuminate\Http\Request;

test('student marks controller index and history screens render inertia response', function () {
    $controller = app(StudentMarksController::class);

    $indexResponse = $controller->index(new Request());
    $historyResponse = $controller->studentHistory(1);

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($historyResponse)->toBeInstanceOf(\Inertia\Response::class);
});
