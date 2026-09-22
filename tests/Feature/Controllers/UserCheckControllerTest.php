<?php

use App\Http\Controllers\UserCheckController;

test('user check controller index and create screens render inertia response', function () {
    $controller = app(UserCheckController::class);

    $indexResponse = $controller->index();
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
