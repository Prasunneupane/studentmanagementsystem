<?php

use App\Http\Controllers\SectionController;

test('section controller methods can be invoked without crashing', function () {
    $controller = app(SectionController::class);

    expect($controller->index())->toBeNull()
        ->and($controller->create())->toBeNull()
        ->and($controller->store(request()))->toBeNull()
        ->and($controller->show(new \App\Models\Section()))->toBeNull()
        ->and($controller->edit(new \App\Models\Section()))->toBeNull()
        ->and($controller->update(request(), new \App\Models\Section()))->toBeNull()
        ->and($controller->destroy(new \App\Models\Section()))->toBeNull();
});
