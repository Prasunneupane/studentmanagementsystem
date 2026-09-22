<?php

use App\Http\Controllers\ExamController;
use Illuminate\Http\Request;

test('exam controller index and schedule screens render inertia response', function () {
    $controller = app(ExamController::class);

    $indexResponse = $controller->index(new Request());
    $scheduleIndexResponse = $controller->scheduleIndex(new Request());

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($scheduleIndexResponse)->toBeInstanceOf(\Inertia\Response::class);
});
