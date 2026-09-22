<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Http\Request;

test('invoice controller index and create screens render inertia response', function () {
    $controller = app(InvoiceController::class);

    $indexResponse = $controller->index(new Request());
    $createResponse = $controller->create();

    expect($indexResponse)->toBeInstanceOf(\Inertia\Response::class)
        ->and($createResponse)->toBeInstanceOf(\Inertia\Response::class);
});
