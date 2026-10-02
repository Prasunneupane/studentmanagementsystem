<?php

use App\Models\PaymentAttempt;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// A payment attempt's status updates are only visible to a user who can
// manage the underlying invoice, not merely anyone with a guessable uuid.
Broadcast::channel('payment-attempts.{uuid}', function ($user, string $uuid) {
    $attempt = PaymentAttempt::where('uuid', $uuid)->first();

    if (!$attempt) {
        return false;
    }

    return app(PermissionService::class)->hasAny($user, ['view_invoice', 'record_invoice_payment']);
});
