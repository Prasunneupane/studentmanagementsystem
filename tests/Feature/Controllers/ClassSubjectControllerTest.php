<?php

use App\Http\Controllers\ClassSubjectController;
use Illuminate\Http\Request;

test('class subject controller index and create screens render inertia response', function () {
    $controller = app(ClassSubjectController::class);

    $indexResponse = $controller->index(new Request());
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
