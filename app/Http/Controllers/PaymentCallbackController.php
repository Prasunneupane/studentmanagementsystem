<?php

namespace App\Http\Controllers;

use App\Enums\PaymentGateway;
use App\Interface\PaymentAttemptInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public (unauthenticated, CSRF-exempt — see bootstrap/app.php) endpoints
 * that providers redirect the payer's browser to (eSewa, Khalti) or post a
 * server-to-server webhook to (Fonepay). Every field read here is untrusted
 * network input; PaymentAttemptService/the driver's verifyCallback() is what
 * actually proves anything, never this controller.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(private PaymentAttemptInterface $paymentAttempts) {}

    public function handle(Request $request, string $gateway, string $attempt)
    {
        try {
            $gatewayEnum = PaymentGateway::from($gateway);
        } catch (\ValueError) {
            throw new NotFoundHttpException();
        }

        $payload = match ($gatewayEnum) {
            PaymentGateway::ESEWA => $this->decodeEsewaPayload($request),
            PaymentGateway::KHALTI => $request->query->all(),
            PaymentGateway::FONEPAY => $request->all(),
        };

        $result = $this->paymentAttempts->handleCallback($gatewayEnum, $attempt, $payload);

        if ($gatewayEnum->sessionType() === 'qr') {
            // Server-to-server webhook (Fonepay) — no browser tab to render a page for.
            return response()->json(['status' => $result->status]);
        }

        $message = match ($result->status) {
            'succeeded' => 'Payment received. You can close this tab and return to the invoice.',
            'failed' => 'Payment was not completed. You can close this tab and try again from the invoice page.',
            default => 'Payment is still being confirmed. You can close this tab; the invoice page will update automatically.',
        };

        return response("<!doctype html><html><body style=\"font-family:sans-serif;padding:2rem\"><p>{$message}</p></body></html>");
    }

    private function decodeEsewaPayload(Request $request): array
    {
        $data = $request->query('data');
        if (!$data) {
            return [];
        }

        $decoded = json_decode(base64_decode($data), true);

        return is_array($decoded) ? $decoded : [];
    }
}
