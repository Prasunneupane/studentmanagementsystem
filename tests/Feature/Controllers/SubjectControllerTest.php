<?php

use App\Http\Controllers\SubjectController;

test('subject controller index and create screens render inertia response', function () {
    $controller = app(SubjectController::class);

    $indexResponse = $controller->index();
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
