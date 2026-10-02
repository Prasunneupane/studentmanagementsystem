<?php

namespace App\Interface;

use App\Enums\PaymentGateway;
use App\Models\PaymentAttempt;

interface PaymentAttemptInterface
{
    public function createAttempt(int $invoiceId, PaymentGateway $gateway, float $amount): PaymentAttempt;

    public function handleCallback(PaymentGateway $gateway, string $attemptUuid, array $payload): PaymentAttempt;

    public function refreshFromProvider(PaymentAttempt $attempt): PaymentAttempt;
}
