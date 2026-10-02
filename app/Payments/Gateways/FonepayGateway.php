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
 * Fonepay dynamic merchant QR.
 *
 * UNLIKE eSewa/Khalti, this is implemented against a document that could
 * only be found on a third-party GitHub mirror ("Fonepay Dynamic QR API
 * v1.1"), not on an official fonepay.com/dev.fonepay.com page. Field names
 * (prn, merchantCode, dataValidation), the HMAC-SHA512 scheme, and the exact
 * signed-message order below are the best available reference, not
 * independently confirmed against Fonepay's own documentation.
 *
 * TODO before production: confirm these fields, the signing secret (this
 * implementation signs with the merchant password per that document — this
 * is the single most likely detail to be wrong and should be confirmed with
 * Fonepay's merchant onboarding team), and endpoint paths directly with
 * Fonepay.
 */
class FonepayGateway implements PaymentGatewayDriver
{
    public function createSession(PaymentRequest $request): PaymentSession
    {
        $amount = round($request->amount, 2);
        $prn = $request->attemptUuid;
        $remarks1 = "Invoice #{$request->invoiceId}";
        $remarks2 = 'Payment';

        $payload = [
            'amount' => $amount,
            'remarks1' => $remarks1,
            'remarks2' => $remarks2,
            'prn' => $prn,
            'merchantCode' => config('payments.fonepay.merchant_code'),
            'username' => config('payments.fonepay.username'),
            'password' => config('payments.fonepay.password'),
            'dataValidation' => $this->sign([$amount, $prn, config('payments.fonepay.merchant_code'), $remarks1, $remarks2]),
        ];

        $response = Http::baseUrl(config('payments.fonepay.base_url'))
            ->timeout(15)
            ->post('/merchant/merchantDetailsForThirdParty/thirdPartyDynamicQrDownload', $payload);

        if ($response->failed() || !($response->json('success') ?? true)) {
            Log::warning('fonepay.qr_generation_failed', ['status' => $response->status(), 'prn' => $prn]);
            throw new \RuntimeException('Unable to generate the Fonepay QR. Please try again.');
        }

        $data = $response->json();

        return new PaymentSession(
            type: 'qr',
            providerTransactionId: null,
            providerReference: $prn,
            redirectUrl: null,
            qrPayload: $data['qrMessage'] ?? null,
            expiresAt: now()->addMinutes((int) config('payments.attempt_ttl_minutes', 15)),
        );
    }

    public function verifyCallback(array $payload): PaymentVerification
    {
        $prn = $payload['prn'] ?? null;
        if (!$prn) {
            return new PaymentVerification(false, 'missing_prn', null, null, null, 'Payment reference missing.');
        }

        return $this->statusCheck($prn);
    }

    public function inquire(PaymentAttempt $attempt): PaymentVerification
    {
        return $this->statusCheck($attempt->provider_reference ?? $attempt->uuid);
    }

    public function renderRedirect(PaymentAttempt $attempt): Response
    {
        throw new \LogicException('Fonepay is a QR-type session; renderRedirect() is unreachable for it.');
    }

    private function sign(array $valuesInOrder): string
    {
        $message = implode(',', $valuesInOrder);

        return strtoupper(hash_hmac('sha512', $message, (string) config('payments.fonepay.password')));
    }

    private function statusCheck(string $prn): PaymentVerification
    {
        $merchantCode = config('payments.fonepay.merchant_code');
        $payload = [
            'prn' => $prn,
            'merchantCode' => $merchantCode,
            'username' => config('payments.fonepay.username'),
            'password' => config('payments.fonepay.password'),
            'dataValidation' => $this->sign([$prn, $merchantCode]),
        ];

        // throw: false — see the identical note in KhaltiGateway::lookup().
        $response = Http::baseUrl(config('payments.fonepay.base_url'))
            ->timeout(15)
            ->retry(2, 300, throw: false)
            ->post('/merchant/merchantDetailsForThirdParty/thirdPartyDynamicQrGetStatus', $payload);

        $data = $response->json();
        if (!is_array($data) || !isset($data['paymentStatus'])) {
            Log::warning('fonepay.status_check_failed', ['status' => $response->status(), 'prn' => $prn]);

            return new PaymentVerification(false, 'status_check_failed', null, null, null, 'Could not verify payment status with Fonepay.');
        }

        $status = $data['paymentStatus'];

        return new PaymentVerification(
            verified: $status === 'success',
            providerStatus: $status,
            providerTransactionId: $data['fonepayTraceId'] ?? null,
            paidAmount: null, // status-check response (per the available spec) does not echo the amount back; PaymentAttemptService compares against the attempt's own stored amount instead
            currency: 'NPR',
            failureReason: $status === 'success' ? null : "Fonepay reported status: {$status}",
        );
    }
}
