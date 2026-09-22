<?php

use App\Http\Controllers\EnrollmentsController;

test('enrollments controller methods can be invoked without crashing', function () {
    $controller = app(EnrollmentsController::class);

    expect($controller->index())->toBeNull()
        ->and($controller->create())->toBeNull()
        ->and($controller->store(request()))->toBeNull()
        ->and($controller->show(new \App\Models\Enrollments()))->toBeNull()
        ->and($controller->edit(new \App\Models\Enrollments()))->toBeNull()
        ->and($controller->update(request(), new \App\Models\Enrollments()))->toBeNull()
        ->and($controller->destroy(new \App\Models\Enrollments()))->toBeNull();
});
