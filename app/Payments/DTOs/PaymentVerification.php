<?php

namespace App\Payments\DTOs;

final readonly class PaymentVerification
{
    public function __construct(
        public bool $verified,
        public string $providerStatus,
        public ?string $providerTransactionId,
        public ?float $paidAmount,
        public ?string $currency,
        public ?string $failureReason, // safe display text only, never raw provider error payloads
    ) {}
}
