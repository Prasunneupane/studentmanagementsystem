<?php

use App\Http\Controllers\EventsController;

test('events controller index and create screens render inertia response', function () {
    $controller = app(EventsController::class);

    $indexResponse = $controller->index();
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
