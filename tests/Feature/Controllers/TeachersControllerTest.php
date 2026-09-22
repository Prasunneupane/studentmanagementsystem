<?php

use App\Http\Controllers\TeachersController;

test('teachers controller index and create screens render inertia response', function () {
    $controller = app(TeachersController::class);

    $indexResponse = $controller->index();
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
