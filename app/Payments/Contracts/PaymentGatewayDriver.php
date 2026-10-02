<?php

namespace App\Payments\Contracts;

use App\Models\PaymentAttempt;
use App\Payments\DTOs\PaymentRequest;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\PaymentVerification;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every gateway (eSewa, Khalti, Fonepay, and any future one such as Stripe)
 * implements only this contract. Nothing provider-specific — signing scheme,
 * endpoint shapes, QR vs. redirect — leaks past it. Adding a gateway means
 * writing one new class against this interface; nothing else in the
 * application changes (Open/Closed + Dependency Inversion).
 */
interface PaymentGatewayDriver
{
    /**
     * Start a provider session for the given request. Must not be called
     * with unvalidated amounts — the caller (PaymentAttemptService) has
     * already checked the amount against the invoice's outstanding balance.
     */
    public function createSession(PaymentRequest $request): PaymentSession;

    /**
     * Verify an inbound callback payload. Every field in $payload is
     * untrusted input from the network until this method proves otherwise
     * (signature check and/or a server-to-server status call, per driver).
     */
    public function verifyCallback(array $payload): PaymentVerification;

    /**
     * Actively ask the provider for the current status of an attempt,
     * independent of any callback having arrived. Used for the status
     * polling fallback and for gateways (Khalti) whose callback params are
     * not themselves trustworthy.
     */
    public function inquire(PaymentAttempt $attempt): PaymentVerification;

    /**
     * Put the payer's browser in front of this provider for a 'redirect'
     * type session (a plain 302 for a direct checkout URL, or a rendered
     * auto-submitting form where the provider requires a signed POST). The
     * frontend always opens the same internal URL for any redirect-type
     * gateway; this method is where the per-provider difference lives, so
     * nothing about the controller or frontend needs to know it exists.
     *
     * Never called for a 'qr' type session (see PaymentGateway::sessionType()) —
     * a qr-type driver may implement this by throwing, since it's unreachable.
     */
    public function renderRedirect(PaymentAttempt $attempt): Response;
}
