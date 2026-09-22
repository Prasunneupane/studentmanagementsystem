<?php

use App\Http\Controllers\DashboardController;

test('dashboard controller index renders inertia response', function () {
    $controller = app(DashboardController::class);

    $response = $controller->index();

    expect($response)->toBeInstanceOf(\Inertia\Response::class);
});
