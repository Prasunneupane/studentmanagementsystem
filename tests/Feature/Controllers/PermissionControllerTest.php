<?php

use App\Http\Controllers\PermissionController;

test('permission controller index and create screens render inertia response', function () {
    $controller = app(PermissionController::class);

    $indexResponse = $controller->index();
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
