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

        return response($this->renderClosingPage($result->status));
    }

    /**
     * This tab (opened via window.open() from the invoice page) is only ever
     * meant to carry the payer through the provider's checkout. The result
     * itself is shown back on the original invoice tab, which is already
     * listening for it over the socket/polling fallback — so this page's only
     * job is a brief confirmation, then closing itself automatically.
     */
    private function renderClosingPage(string $status): string
    {
        [$heading, $tone] = match ($status) {
            'succeeded' => ['Payment received', '#15803d'],
            'failed' => ['Payment was not completed', '#b91c1c'],
            default => ['Payment is still being confirmed', '#1d4ed8'],
        };

        return <<<HTML
            <!doctype html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>{$heading}</title>
                <style>
                    body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8fafc; }
                    .card { text-align: center; padding: 2rem; }
                    h1 { color: {$tone}; font-size: 1.1rem; margin-bottom: 0.5rem; }
                    p { color: #64748b; font-size: 0.875rem; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h1>{$heading}</h1>
                    <p id="message">Returning you to the invoice…</p>
                </div>
                <script>
                    setTimeout(function () {
                        window.close();
                        // If the browser      blocked the close (e.g. this tab wasn't
                        // opened by script in this session), fall back to a message.
                        setTimeout(function () {
                            document.getElementById('message').textContent = 'You can close this tab now.';
                        }, 300);
                    }, 1200);
                </script>
            </body>
            </html>
            HTML;
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
