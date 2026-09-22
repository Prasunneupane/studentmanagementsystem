<?php

use App\Http\Controllers\ExamScheduleController;

test('exam schedule controller index method can be invoked without crashing', function () {
    $controller = app(ExamScheduleController::class);

    expect($controller->index())->toBeNull();
});
