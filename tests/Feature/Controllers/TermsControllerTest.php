<?php

use App\Http\Controllers\TermsController;

test('terms controller index and create screens render inertia response', function () {
    $controller = app(TermsController::class);

    $indexResponse = $controller->index();
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
