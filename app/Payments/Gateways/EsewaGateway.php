<?php

namespace App\Payments\Gateways;

use App\Models\PaymentAttempt;
use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\DTOs\PaymentRequest;
use App\Payments\DTOs\PaymentSession;
use App\Payments\DTOs\PaymentVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * eSewa ePay v2. Source: https://developer.esewa.com.np/pages/Epay
 *
 * ePay v2 has no standalone QR/redirect-URL product — it is a signed-form
 * POST to eSewa's hosted page. That form can't be expressed as a plain URL
 * the frontend can window.open() directly, so createSession() points the
 * session at our own internal redirect route; renderRedirect() is where the
 * signed auto-submitting form actually gets built and served, the moment the
 * payer's browser opens that route in its new tab.
 */
class EsewaGateway implements PaymentGatewayDriver
{
    public function createSession(PaymentRequest $request): PaymentSession
    {
        $amount = round($request->amount, 2);
        $fields = [
            'amount' => $amount,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'transaction_uuid' => $request->attemptUuid,
            'product_code' => config('payments.esewa.merchant_code'),
            'product_service_charge' => 0,
            'product_delivery_charge' => 0,
            'success_url' => $request->successUrl,
            'failure_url' => $request->failureUrl,
            'signed_field_names' => 'total_amount,transaction_uuid,product_code',
        ];
        $fields['signature'] = $this->sign([
            'total_amount' => $fields['total_amount'],
            'transaction_uuid' => $fields['transaction_uuid'],
            'product_code' => $fields['product_code'],
        ]);

        return new PaymentSession(
            type: 'redirect',
            providerTransactionId: null, // eSewa only issues a reference after payment completes
            providerReference: $request->attemptUuid, // our transaction_uuid, needed for the status-check call
            redirectUrl: route('payment.redirect', $request->attemptUuid),
            qrPayload: null,
            expiresAt: now()->addMinutes((int) config('payments.attempt_ttl_minutes', 15)),
            meta: ['esewa_form_fields' => $fields],
        );
    }

    public function verifyCallback(array $payload): PaymentVerification
    {
        $signedFieldNames = explode(',', $payload['signed_field_names'] ?? '');
        $toVerify = [];
        foreach ($signedFieldNames as $field) {
            $toVerify[$field] = $payload[$field] ?? null;
        }
        $expectedSignature = $this->sign($toVerify);

        if (!isset($payload['signature']) || !hash_equals($expectedSignature, $payload['signature'])) {
            Log::warning('esewa.signature_mismatch', ['transaction_uuid' => $payload['transaction_uuid'] ?? null]);

            return new PaymentVerification(false, 'signature_invalid', null, null, null, 'Payment signature could not be verified.');
        }

        // A valid signature proves the redirect came from eSewa, but per our
        // security policy a browser return is never sufficient on its own —
        // always cross-check with eSewa's own status endpoint before completing.
        return $this->statusCheck((string) $payload['transaction_uuid'], (float) $payload['total_amount']);
    }

    public function inquire(PaymentAttempt $attempt): PaymentVerification
    {
        $transactionUuid = $attempt->provider_reference ?? $attempt->uuid;

        return $this->statusCheck($transactionUuid, (float) $attempt->amount);
    }

    public function renderRedirect(PaymentAttempt $attempt): Response
    {
        $fields = $attempt->meta['esewa_form_fields'] ?? null;
        abort_unless(is_array($fields), 500, 'eSewa form fields missing for this attempt.');

        $actionUrl = e(config('payments.esewa.form_url'));
        $inputs = '';
        foreach ($fields as $name => $value) {
            $inputs .= sprintf('<input type="hidden" name="%s" value="%s">', e($name), e((string) $value));
        }

        $html = <<<HTML
            <!doctype html>
            <html><head><meta charset="utf-8"><title>Redirecting to eSewa…</title></head>
            <body>
                <p>Redirecting to eSewa, please wait…</p>
                <form id="esewa-form" method="POST" action="{$actionUrl}">{$inputs}</form>
                <script>document.getElementById('esewa-form').submit();</script>
            </body></html>
            HTML;

        return response($html);
    }

    private function sign(array $fieldsInOrder): string
    {
        $message = implode(',', array_map(
            fn ($key, $value) => "{$key}={$value}",
            array_keys($fieldsInOrder),
            $fieldsInOrder,
        ));

        return base64_encode(hash_hmac('sha256', $message, (string) config('payments.esewa.secret_key'), true));
    }

    private function statusCheck(string $transactionUuid, float $totalAmount): PaymentVerification
    {
        // throw: false — see the identical note in KhaltiGateway::lookup():
        // a non-2xx status here may still carry a valid, parseable status
        // body (e.g. CANCELED), and retry()'s default $throw=true would
        // otherwise turn that into an uncaught RequestException.
        $response = Http::timeout(15)
            ->retry(2, 300, throw: false)
            ->get(config('payments.esewa.status_url'), [
                'product_code' => config('payments.esewa.merchant_code'),
                'total_amount' => $totalAmount,
                'transaction_uuid' => $transactionUuid,
            ]);

        $data = $response->json();
        if (!is_array($data) || !isset($data['status'])) {
            Log::warning('esewa.status_check_failed', ['status' => $response->status(), 'transaction_uuid' => $transactionUuid]);

            return new PaymentVerification(false, 'status_check_failed', null, null, null, 'Could not verify payment status with eSewa.');
        }

        $status = $data['status'];

        return new PaymentVerification(
            verified: $status === 'COMPLETE',
            providerStatus: $status,
            providerTransactionId: $data['ref_id'] ?? null,
            paidAmount: $status === 'COMPLETE' ? $totalAmount : null,
            currency: 'NPR',
            failureReason: $status === 'COMPLETE' ? null : "eSewa reported status: {$status}",
        );
    }
}
