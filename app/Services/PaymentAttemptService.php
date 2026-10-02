<?php

namespace App\Services;

use App\Enums\PaymentGateway;
use App\Events\PaymentAttemptUpdated;
use App\Interface\InvoiceInterface;
use App\Interface\PaymentAttemptInterface;
use App\Models\Invoice;
use App\Models\PaymentAttempt;
use App\Payments\DTOs\PaymentRequest;
use App\Payments\DTOs\PaymentVerification;
use App\Payments\Factories\PaymentGatewayFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Owns the lifecycle of a PaymentAttempt (create -> initiate -> verify ->
 * complete -> broadcast). It never computes invoice totals/balance/status
 * itself — that math lives entirely in InvoiceService, per the SRP split in
 * PAYMENT_IMPLEMENTATION_PLAN.md. This class only knows "is this attempt
 * still valid to act on" and "which driver handles it."
 */
class PaymentAttemptService implements PaymentAttemptInterface
{
    public function __construct(
        private PaymentGatewayFactory $factory,
        private InvoiceInterface $invoiceService,
    ) {}

    public function createAttempt(int $invoiceId, PaymentGateway $gateway, float $amount): PaymentAttempt
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $balance = $this->invoiceService->outstandingBalance($invoice);

        if ($amount <= 0 || $amount > $balance) {
            throw ValidationException::withMessages([
                'amount' => "Amount must be greater than zero and no more than the outstanding balance ({$balance}).",
            ]);
        }

        $attempt = PaymentAttempt::create([
            'invoice_id' => $invoice->id,
            'gateway' => $gateway->value,
            'amount' => $amount,
            'currency' => 'NPR',
            'status' => 'pending',
            'expires_at' => now()->addMinutes((int) config('payments.attempt_ttl_minutes', 15)),
            'created_by' => Auth::id(),
        ]);

        $request = new PaymentRequest(
            attemptUuid: $attempt->uuid,
            invoiceId: $invoice->id,
            amount: $amount,
            currency: 'NPR',
            successUrl: route('payment.callback', ['gateway' => $gateway->value, 'attempt' => $attempt->uuid]),
            failureUrl: route('payment.callback', ['gateway' => $gateway->value, 'attempt' => $attempt->uuid]),
        );

        $session = $this->factory->make($gateway)->createSession($request);

        $attempt->update([
            'provider_transaction_id' => $session->providerTransactionId,
            'provider_reference' => $session->providerReference,
            'checkout_url' => $session->redirectUrl,
            'qr_payload' => $session->qrPayload,
            'expires_at' => $session->expiresAt,
            'meta' => $session->meta,
        ]);

        return $attempt->refresh();
    }

    public function handleCallback(PaymentGateway $gateway, string $attemptUuid, array $payload): PaymentAttempt
    {
        $attempt = PaymentAttempt::where('uuid', $attemptUuid)->firstOrFail();
        $verification = $this->factory->make($gateway)->verifyCallback($payload);

        return $this->applyVerification($attempt, $verification);
    }

    public function refreshFromProvider(PaymentAttempt $attempt): PaymentAttempt
    {
        $gateway = $attempt->gateway instanceof PaymentGateway ? $attempt->gateway : PaymentGateway::from($attempt->gateway);
        $verification = $this->factory->make($gateway)->inquire($attempt);

        return $this->applyVerification($attempt, $verification);
    }

    private function applyVerification(PaymentAttempt $attempt, PaymentVerification $verification): PaymentAttempt
    {
        $statusBefore = $attempt->status;

        $attempt = DB::transaction(function () use ($attempt, $verification) {
            $attempt = PaymentAttempt::lockForUpdate()->findOrFail($attempt->id);

            if (!$attempt->isPending()) {
                return $attempt; // already succeeded/failed/expired — idempotent no-op, e.g. a duplicate callback
            }

            if ($attempt->isExpired()) {
                $attempt->update(['status' => 'expired']);

                return $attempt;
            }

            if ($verification->verified) {
                $amountMatches = $verification->paidAmount === null
                    || abs($verification->paidAmount - (float) $attempt->amount) < 0.01;

                if (!$amountMatches) {
                    $attempt->update(['status' => 'failed', 'meta' => array_merge($attempt->meta ?? [], [
                        'failure_reason' => 'Verified amount did not match the requested attempt amount.',
                    ])]);

                    return $attempt;
                }

                $attempt->update([
                    'status' => 'succeeded',
                    'provider_transaction_id' => $verification->providerTransactionId ?? $attempt->provider_transaction_id,
                    'verified_at' => now(),
                ]);

                $this->invoiceService->applyGatewayPayment($attempt->invoice_id, $attempt, $verification);

                return $attempt;
            }

            if ($this->isTerminalFailure($verification->providerStatus)) {
                $attempt->update(['status' => 'failed', 'meta' => array_merge($attempt->meta ?? [], [
                    'failure_reason' => $verification->failureReason,
                ])]);
            }

            return $attempt; // otherwise still genuinely pending on the provider's side — leave as-is
        });

        // The attempt/invoice write above already committed — broadcasting is
        // a best-effort realtime notification on top of it, never allowed to
        // turn a successful verification into a failed HTTP response (e.g.
        // Reverb not running, or something else already bound to its port).
        // Only broadcast on an actual transition, not on every poll.
        if ($attempt->status !== $statusBefore) {
            try {
                PaymentAttemptUpdated::dispatch($attempt);
            } catch (\Throwable $e) {
                Log::warning('payment_attempt.broadcast_failed', [
                    'attempt_uuid' => $attempt->uuid,
                    'status' => $attempt->status,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $attempt;
    }

    private function isTerminalFailure(string $providerStatus): bool
    {
        $normalized = strtolower($providerStatus);

        foreach (['cancel', 'fail', 'expir', 'not_found', 'ambiguous'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }
}
