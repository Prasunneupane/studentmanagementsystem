# Realtime Online Payment Implementation

## Recommendation

Use **Laravel Reverb** for this Laravel 12 + Vue/Inertia application.

Reverb is Laravel's first-party WebSocket server and works with Laravel broadcasting and Laravel Echo. It avoids adding an external hosted provider for this first-party workflow and is a good fit for the requested live payment-status modal. The tradeoff is operational: Reverb must keep running as a server process, and production needs a process manager, an open/reverse-proxied WebSocket endpoint, and TLS (`wss://`) when the site uses HTTPS.

| Option | Best fit | Tradeoff |
| --- | --- | --- |
| **Laravel Reverb** | This application; self-hosted Laravel broadcasts | You operate the WebSocket process and network endpoint |
| Pusher / Ably | Fastest hosted setup, minimal server operations | Hosted service, account/credentials, and possible usage costs |
| Soketi or another Pusher-protocol server | Existing infrastructure already operates it | Additional service and operational ownership; less direct Laravel integration |

For the current mock and eventual gateway status notifications, choose Reverb. If operating a WebSocket service is not acceptable, use Pusher or Ably instead. A WebSocket does not process a payment; it only delivers status changes to the browser.

## Important Payment Flow Decision

The QR code must represent a **specific payment attempt**, not a static gateway image. A static eSewa/Khalti QR cannot identify this invoice, the amount being paid, or a transaction attempt to update over a socket.

Also, scanning a QR code does not prove that money was paid. For the mock flow, the QR should open a mock checkout page with a clearly labeled **Simulate payment** action. That action calls Laravel; Laravel records the mock success and broadcasts the update. Do not let the Vue page mark a payment paid just because it received a client-side message or because the QR was opened.

Recommended user flow:

1. Create/save the invoice with its full total and `unpaid` status.
2. Let the user choose eSewa or Khalti and enter the amount they want to pay now. For example, a 12,000 payment on a 25,000 invoice leaves 13,000 due.
3. Reject an attempt when the invoice total or payable balance is zero. In the UI, show a toast such as “Please add a fee item with a price.” The server must also reject non-positive amounts and amounts above the remaining balance.
4. Ask Laravel to create a pending payment attempt for the selected gateway and amount.
5. Show a modal styled for the selected gateway: eSewa green, Khalti purple. Render a QR for the returned, short-lived mock checkout URL. Show the payment amount, invoice total, and remaining balance separately.
6. The QR opens the mock checkout page. The user presses **Simulate payment** there.
7. Laravel validates the attempt, records it once, updates the invoice's paid amount/status, and broadcasts its new status.
8. The original browser receives the event, changes the modal to a success state, and refreshes/reloads the invoice totals from Laravel.

If the modal must appear before the invoice is saved, first persist an invoice draft and create the payment attempt against that draft. Do not create a “paid” invoice from frontend-calculated totals.

## What Is Needed

### Backend

- Install/configure Reverb and Laravel broadcasting.
- Add a payment-attempt record (recommended) with an opaque UUID, invoice ID, gateway, amount, status (`pending`, `succeeded`, `failed`, `expired`), expiry time, and unique mock/provider transaction ID. Keep it separate from a final `InvoicePayment` so pending attempts are not counted as paid.
- Add an authenticated endpoint to create an attempt. Validate the gateway against the `PaymentGateway` enum, amount as positive, and amount no greater than the current invoice balance. Calculate the balance on the server.
- Return the attempt UUID, expiry, amount, gateway, and one-time/signed mock checkout URL. The frontend uses that URL as the QR payload.
- Add a mock checkout page/route and a server endpoint for its **Simulate payment** action. The server should lock the attempt and invoice in a database transaction, check the attempt is pending and unexpired, prevent duplicate completion, create the successful `InvoicePayment`, and recalculate invoice paid amount and status from successful payments.
- Broadcast a `PaymentAttemptUpdated` event after the database transaction commits. Include only safe display data such as attempt ID, status, and amount; never broadcast gateway secrets.
- Authorize a private channel such as `payment-attempts.{uuid}` so only a user allowed to manage that invoice can subscribe. Use a high-entropy UUID and still enforce channel authorization.
- Make completion idempotent: repeating the mock confirmation/callback must not create another payment row.

### Frontend

- Add Laravel Echo and the Reverb-compatible Pusher JS client, following Laravel's installer output for the installed Laravel version.
- Add a Vue QR renderer (for example `qrcode.vue`) to render the checkout URL returned by Laravel. Do not use the existing static `esewaQrUrl`/`khaltiQrUrl` as proof of payment.
- On gateway selection, check the computed invoice total and remaining amount. If zero, toast and do not create an attempt or open the QR modal.
- Subscribe to the private attempt channel while the modal is open; unsubscribe when it closes, the attempt expires, or the component unmounts.
- Treat the socket event as a signal to fetch/refresh server state. Show success only after Laravel reports a persisted successful payment. Handle pending, failed, expired, reconnecting, and timeout states.
- Display “Mock payment” clearly until real gateway initiation and verified callbacks are implemented.

## Install and Run (Development)

Use Laravel's broadcasting installer and select Reverb, or follow the Laravel 12 Reverb installation steps for this project. The installer configures the server, broadcasting, and usually the Echo client scaffolding. If installing manually, the usual pieces are:

```sh
composer require laravel/reverb
php artisan install:broadcasting --reverb
npm install laravel-echo pusher-js
```

Review generated files before applying changes if the installer reports existing broadcasting configuration. Configure matching server/client Reverb values in `.env`, including `BROADCAST_CONNECTION=reverb`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, and corresponding `VITE_REVERB_*` values. Do not commit real secrets.

Run the Laravel app, Vite, Reverb, and a queue worker during development:

```sh
php artisan serve
npm run dev
php artisan reverb:start
php artisan queue:work
```

A queued `ShouldBroadcast` event needs a working queue worker. For a quick local mock, `ShouldBroadcastNow` avoids queue delay; use queued broadcasting and a supervised worker for production. Restart Vite after changing `VITE_*` variables. For production, configure a process manager for Reverb and the queue worker, proxy the WebSocket endpoint, use `wss://`, and permit the WebSocket connection through the host firewall.

## Security and Correctness Checks

- Never accept invoice totals, payment status, or gateway success as authoritative from Vue.
- Validate ownership/permissions, gateway, amount, expiry, and pending status on every server transition.
- Lock/recalculate against the current invoice balance to handle two payment attempts racing.
- Use a unique provider transaction ID and idempotent completion for real gateways.
- For real eSewa/Khalti payments, replace the mock confirmation with the gateway's server-to-server verification/callback flow. Only verified server responses may create successful payments.
- Test zero amount, partial payment, full payment, overpayment, expired attempt, duplicate callback, unauthorized channel subscription, and socket reconnect/timeout behavior.

## Current Project Notes

- `InvoiceController::create()` already sends gateway choices from `getPaymentGateways()` through Inertia.
- The gateway enum currently defines eSewa and Khalti.
- The current QR URL props are static images. The mock checkout needs an attempt-specific URL instead.
- No Echo/Reverb frontend setup or application broadcast event/channel was found during this review.
- The current create-invoice screen should save the invoice before starting a gateway attempt, or introduce an explicit draft flow. The socket layer should not be used to bypass invoice persistence or payment validation.
