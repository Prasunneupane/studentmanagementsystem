<?php

use App\Http\Controllers\PaymentAttemptController;
use App\Http\Controllers\PaymentCallbackController;
use Illuminate\Support\Facades\Route;

// Authenticated: starting an attempt and polling its status are actions
// taken from inside the invoice screens, by staff who can already manage
// that invoice.
Route::middleware(['auth'])->group(function () {
    Route::middleware(['permission:record_invoice_payment'])->group(function () {
        Route::post('/invoice/{invoice}/payment-attempts', [PaymentAttemptController::class, 'store'])
            ->whereNumber('invoice')
            ->name('payment-attempts.store');
    });

    Route::middleware(['permission:view_invoice'])->group(function () {
        Route::get('/invoice/{invoice}/payment-attempts/{attempt}', [PaymentAttemptController::class, 'status'])
            ->whereNumber('invoice')
            ->name('payment-attempts.status');
    });
});

// Public: this is where the payer's own browser lands (new tab) or where a
// provider's server posts a webhook. No session, no CSRF token, nothing
// provider-supplied is trusted — see PaymentCallbackController's docblock.
Route::get('/pay/{attempt}/redirect', [PaymentAttemptController::class, 'redirect'])->name('payment.redirect');
Route::match(['get', 'post'], '/pay/{gateway}/callback/{attempt}', [PaymentCallbackController::class, 'handle'])->name('payment.callback');
