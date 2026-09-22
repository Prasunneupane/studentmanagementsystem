<?php

use App\Http\Controllers\RolesController;

test('roles controller index and create screens render inertia response', function () {
    $controller = app(RolesController::class);

    $indexResponse = $controller->index();
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
