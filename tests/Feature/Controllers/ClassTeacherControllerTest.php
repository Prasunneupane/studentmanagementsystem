<?php

use App\Http\Controllers\ClassTeacherController;
use Illuminate\Http\Request;

test('class teacher controller index and create screens render inertia response', function () {
    $controller = app(ClassTeacherController::class);

    $indexResponse = $controller->index(new Request());
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
