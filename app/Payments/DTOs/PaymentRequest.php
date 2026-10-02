<?php

namespace App\Payments\DTOs;

final readonly class PaymentRequest
{
    public function __construct(
        public string $attemptUuid,
        public int $invoiceId,
        public float $amount,
        public string $currency,
        public string $successUrl,
        public string $failureUrl,
    ) {}
}
