<?php

namespace App\Payments\DTOs;

use Illuminate\Support\Carbon;

/**
 * What a driver hands back after starting a provider session. `type`
 * discriminates how the frontend presents it: a 'redirect' session is opened
 * in a new browser tab; a 'qr' session is rendered in-page. A driver sets
 * exactly one of $redirectUrl / $qrPayload to match its $type.
 */
final readonly class PaymentSession
{
    public function __construct(
        public string $type, // 'redirect' | 'qr'
        public ?string $providerTransactionId,
        public ?string $providerReference,
        public ?string $redirectUrl,
        public ?string $qrPayload,
        public Carbon $expiresAt,
        /** Non-secret driver-specific data to persist on the attempt (e.g. eSewa's already-signed form fields). Never put a secret key here. */
        public array $meta = [],
    ) {}
}
