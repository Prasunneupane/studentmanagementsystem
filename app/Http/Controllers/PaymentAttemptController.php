<?php

namespace App\Http\Controllers;

use App\Enums\PaymentGateway;
use App\Interface\InvoiceInterface;
use App\Interface\PaymentAttemptInterface;
use App\Models\PaymentAttempt;
use App\Payments\Factories\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class PaymentAttemptController extends Controller
{
    public function __construct(
        private PaymentAttemptInterface $paymentAttempts,
        private InvoiceInterface $invoiceService,
        private PaymentGatewayFactory $gatewayFactory,
    ) {}

    public function store(Request $request, string $invoice)
    {
        $data = $request->validate([
            'gateway' => ['required', new Enum(PaymentGateway::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $gateway = PaymentGateway::from($data['gateway']);
        $attempt = $this->paymentAttempts->createAttempt((int) $invoice, $gateway, (float) $data['amount']);

        return response()->json([
            'attemptUuid' => $attempt->uuid,
            'gateway' => $gateway->value,
            'type' => $gateway->sessionType(),
            // Redirect-type gateways are always opened via this one internal
            // URL regardless of provider — eSewa/Khalti/Stripe differences
            // are resolved server-side in the driver, never in the frontend.
            'redirectUrl' => $gateway->sessionType() === 'redirect' ? route('payment.redirect', $attempt->uuid) : null,
            'qrPayload' => $attempt->qr_payload,
            'amount' => (float) $attempt->amount,
            'expiresAt' => $attempt->expires_at?->toIso8601String(),
        ]);
    }

    public function redirect(string $attempt)
    {
        $attempt = PaymentAttempt::where('uuid', $attempt)->firstOrFail();

        if ($attempt->isExpired()) {
            return response('<!doctype html><html><body><p>This payment link has expired. Please close this tab and start a new payment from the invoice page.</p></body></html>', 410);
        }

        $gateway = $attempt->gateway instanceof PaymentGateway ? $attempt->gateway : PaymentGateway::from($attempt->gateway);

        return $this->gatewayFactory->make($gateway)->renderRedirect($attempt);
    }

    public function status(string $invoice, string $attempt)
    {
        $attemptModel = PaymentAttempt::where('uuid', $attempt)
            ->where('invoice_id', $invoice)
            ->firstOrFail();

        if ($attemptModel->isPending() && !$attemptModel->isExpired()) {
            $attemptModel = $this->paymentAttempts->refreshFromProvider($attemptModel);
        }

        return response()->json([
            'attemptUuid' => $attemptModel->uuid,
            'status' => $attemptModel->status,
            'amount' => (float) $attemptModel->amount,
            'outstandingBalance' => $this->invoiceService->outstandingBalance((int) $invoice),
        ]);
    }
}
