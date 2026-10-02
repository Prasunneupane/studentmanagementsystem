<?php

namespace App\Payments\Gateways;

use App\Models\PaymentAttempt;
use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\DTOs\PaymentRequest;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\PaymentVerification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Khalti ePayment API v2. Source: https://docs.khalti.com/khalti-epayment/
 *
 * Important: Khalti's own documentation states the redirect/callback query
 * parameters are NOT signed. This driver therefore never treats a callback
 * payload as proof of payment by itself — verifyCallback() always falls
 * through to the /epayment/lookup/ server-to-server call, which is the only
 * trusted source of truth for this gateway.
 */
class KhaltiGateway implements PaymentGatewayDriver
{
    public function createSession(PaymentRequest $request): PaymentSession
    {
        $response = Http::baseUrl(config('payments.khalti.base_url'))
            ->withHeaders(['Authorization' => 'Key ' . config('payments.khalti.secret_key')])
            ->timeout(15)
            ->post('/epayment/initiate/', [
                'return_url' => $request->successUrl,
                'website_url' => config('app.url'),
                'amount' => (int) round($request->amount * 100), // paisa
                'purchase_order_id' => $request->attemptUuid,
                'purchase_order_name' => "Invoice #{$request->invoiceId} payment",
            ]);

        if ($response->failed()) {
            Log::warning('khalti.initiate_failed', ['status' => $response->status(), 'attempt' => $request->attemptUuid]);
            throw new \RuntimeException('Unable to start Khalti checkout. Please try again.');
        }

        $data = $response->json();

        return new PaymentSession(
            type: 'redirect',
            providerTransactionId: $data['pidx'] ?? null,
            providerReference: null,
            redirectUrl: $data['payment_url'] ?? null,
            qrPayload: null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : now()->addSeconds((int) ($data['expires_in'] ?? 900)),
        );
    }

    public function verifyCallback(array $payload): PaymentVerification
    {
        $pidx = $payload['pidx'] ?? null;
        if (!$pidx) {
            return new PaymentVerification(false, 'missing_pidx', null, null, null, 'Payment reference missing.');
        }

        return $this->lookup($pidx);
    }

    public function inquire(PaymentAttempt $attempt): PaymentVerification
    {
        if (!$attempt->provider_transaction_id) {
            return new PaymentVerification(false, 'no_reference', null, null, null, 'No provider reference recorded for this attempt.');
        }

        return $this->lookup($attempt->provider_transaction_id);
    }

    public function renderRedirect(PaymentAttempt $attempt): Response
    {
        abort_unless((bool) $attempt->checkout_url, 500, 'Khalti checkout URL missing for this attempt.');

        return redirect()->away($attempt->checkout_url);
    }

    private function lookup(string $pidx): PaymentVerification
    {
        // throw: false — Khalti returns a non-2xx (e.g. 400) for some terminal
        // statuses such as "User canceled" while still sending a valid JSON
        // body. retry()'s default $throw=true would otherwise turn that into
        // an uncaught RequestException instead of a normal failed/cancelled result.
        $response = Http::baseUrl(config('payments.khalti.base_url'))
            ->withHeaders(['Authorization' => 'Key ' . config('payments.khalti.secret_key')])
            ->timeout(15)
            ->retry(2, 300, throw: false)
            ->post('/epayment/lookup/', ['pidx' => $pidx]);

        $data = $response->json();
        if (!is_array($data) || !isset($data['status'])) {
            Log::warning('khalti.lookup_failed', ['status' => $response->status(), 'pidx' => $pidx]);

            return new PaymentVerification(false, 'lookup_failed', null, null, null, 'Could not verify payment status with Khalti.');
        }

        $status = $data['status'];

        return new PaymentVerification(
            verified: $status === 'Completed',
            providerStatus: $status,
            providerTransactionId: $data['transaction_id'] ?? $pidx,
            paidAmount: isset($data['total_amount']) ? ((float) $data['total_amount']) / 100 : null,
            currency: 'NPR',
            failureReason: $status === 'Completed' ? null : "Khalti reported status: {$status}",
        );
    }
}
