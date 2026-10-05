# Payment Gateway + Realtime QR — Implementation Changelog

Companion to `PAYMENT_IMPLEMENTATION_PLAN.md` (read that first for the *why* behind each decision). This file is the file-by-file record of what was actually built, grouped by the plan's phases, per your request.

**Status: Phases 0–5 (backend + frontend) are implemented and build/lint-clean. Phase 6 (automated tests) is not done — see "What's left" at the bottom.** No real sandbox credentials exist yet (eSewa/Khalti/Fonepay `.env` values are empty), so the drivers are structurally complete and verified to resolve/compile correctly, but have not been exercised against a live provider.

---

## Phase 0 — Fixed pre-existing bugs

These existed before this work and were fixed first because the new gateway-payment path reuses the same table/columns.

- **`app/Services/InvoiceService.php`**
  - `recordPayment()` / `updatePayment()`: now set `payment_date` and `payment_nepali_date` (previously omitted — both are `NOT NULL` columns on `tbl_invoice_payments`, so these calls were one strict-mode SQL setting away from failing outright).
  - `recordPayment()` / `updatePayment()`: both now validate the amount against `outstandingBalance()` and reject overpayment (previously unbounded — a staff member could record more than was owed).
  - `recordPayment()` / `updatePayment()` / `applyGatewayPayment()`: lock the invoice row (`lockForUpdate()`) before reading/writing `paid_amount`, closing a race where two concurrent payments could both read a stale balance.
  - `refreshStatus()` and `billInVoiceStatus()` were two copies of the same unpaid/partial/paid/overdue logic — `refreshStatus()` now just calls `billInVoiceStatus()`.
  - Default payment method changed from the literal string `'CASH'` to `PaymentMethod::CASH->value` (`'cash'`) — the old literal didn't match the enum's casing.
- **`app/Http/Controllers/InvoiceController.php`**
  - `recordPayment()` / `updatePayment()` validation: replaced the hardcoded list `cash,bank_transfer,card,online,cheque` (which used `online`, while `PaymentMethod` enum defines `online_payment` — these never matched) with `new Enum(PaymentMethod::class)`, so validation can never drift from the enum again.
- **`resources/js/pages/invoice/Show.vue`**
  - Its local hardcoded payment-method list used `online` to match the old (broken) backend rule. Fixed to `online_payment` to match the enum now that the backend actually enforces it — otherwise this page's "Add payment" form would have started rejecting online payments the moment Phase 0's backend fix shipped.

## Phase 1 — Scaffolding

- **Reverb + broadcasting installed**: `composer require laravel/reverb`; `php artisan install:broadcasting --reverb` (published `config/broadcasting.php`, `routes/channels.php`, registered in `bootstrap/app.php`); `laravel-echo` + `pusher-js` + `@laravel/echo-vue` added to `package.json`; `configureEcho({ broadcaster: 'reverb' })` wired into `resources/js/app.ts`. `REVERB_*`/`VITE_REVERB_*` generated (random, local-only) in `.env`; placeholder-only in `.env.example`.
- **`database/migrations/2026_10_02_202358_create_payment_attempts_table.php`** (new): `tbl_payment_attempts` — uuid, invoice_id, gateway, amount, currency, status, provider_transaction_id (unique when set), provider_reference, checkout_url, qr_payload, expires_at, verified_at, created_by, meta (json). Kept entirely separate from `tbl_invoice_payments` so a pending/failed attempt can never be mistaken for a real payment.
- **`app/Models/PaymentAttempt.php`** (new): route-keyed by `uuid` (never exposes the auto-increment id in URLs), `isPending()`/`isExpired()` helpers.
- **`app/Enums/PaymentGateway.php`**: added `FONEPAY` case; added `color()` (per-gateway brand color for the UI) and `sessionType()` (`'redirect'` for eSewa/Khalti, `'qr'` for Fonepay) — this one method is what lets the frontend render any gateway without a per-provider `if`/`switch`.
- **`app/Services/InvoiceService.php`**: `getPaymentGateways()` now also returns `color` and `sessionType` so the frontend never needs its own copy of "which gateway does what."
- **`config/payments.php`** (new): sandbox/live endpoint selection via `PAYMENTS_ENV` (never `APP_DEBUG`), credential config for all three gateways.
- **`app/Interface/InvoiceInterface.php` + `InvoiceService.php`**: added `outstandingBalance()` (single source of truth for "how much is left to pay," reused by Phase 0's fixes, by `PaymentAttemptService`, and by the frontend's "Allowed:" hint) and `applyGatewayPayment()` (the *only* place a verified gateway payment becomes an `InvoicePayment` row and updates `paid_amount`/status — see the plan's SOLID section for why this stays in `InvoiceService` rather than in the payment-attempt code).

## Phase 1b — The Payments module (contracts, DTOs, factory)

All new, under `app/Payments/`:

- **`Contracts/PaymentGatewayDriver.php`**: the one interface every gateway implements — `createSession()`, `verifyCallback()`, `inquire()`, `renderRedirect()`. Adding Stripe later means writing one class against this interface; nothing here changes.
- **`DTOs/PaymentRequest.php`**, **`PaymentSession.php`**, **`PaymentVerification.php`**: readonly value objects at the driver boundary, instead of passing arrays around. `PaymentSession` carries a `type` discriminator (`'redirect'` | `'qr'`) plus an optional `meta` array for non-secret driver-specific data (used by eSewa to stash its signed form fields).
- **`Factories/PaymentGatewayFactory.php`**: `make(PaymentGateway $gateway)` — maps the enum to a driver class and resolves it through the container. Only ever accepts the enum, never a raw string/class name from the browser.

## Phase 2 — Khalti driver

- **`app/Payments/Gateways/KhaltiGateway.php`** (new): ePayment API v2. `createSession()` posts to `/epayment/initiate/`, returns a `redirect`-type session pointing at Khalti's own `payment_url`. **`verifyCallback()` ignores the callback's query parameters entirely** and always calls `/epayment/lookup/` — per Khalti's own documentation, those parameters are not signed, so the lookup call is the only thing this driver trusts. `renderRedirect()` is a plain 302 to the stored checkout URL.

## Phase 3 — eSewa driver

- **`app/Payments/Gateways/EsewaGateway.php`** (new): ePay v2. `createSession()` computes the signed form fields (HMAC-SHA256 over `total_amount,transaction_uuid,product_code`) and stores them in the attempt's `meta`; the session's `redirectUrl` points at our *own* `/pay/{uuid}/redirect` route, because eSewa requires a signed form POST, not a GET-able URL. `renderRedirect()` is where that form is actually rendered and auto-submitted, at the moment the payer's tab opens. `verifyCallback()` recomputes the signature over whatever fields eSewa says it signed, rejects on mismatch (`hash_equals`), and — even on a valid signature — still calls eSewa's status-check endpoint before treating anything as paid, per the plan's "a browser return is never sufficient on its own" rule.

## Phase 4 — Fonepay driver

- **`app/Payments/Gateways/FonepayGateway.php`** (new): `qr`-type session — `createSession()` requests a dynamic QR (`qrMessage` payload) and returns it directly for in-page rendering, no redirect involved. HMAC-SHA512 signing per the only documentation found (see the plan's research note — **this is the one driver whose field names/signing secret are not confirmed against an official fonepay.com source** and should be verified with Fonepay before production use). `renderRedirect()` throws, since a qr-type session never reaches that code path.

## Phase 1c — Orchestration, events, routes

- **`app/Services/PaymentAttemptService.php`** + **`app/Interface/PaymentAttemptInterface.php`** (new): owns the attempt lifecycle only — `createAttempt()` validates the amount against `InvoiceService::outstandingBalance()`, resolves the driver, stores the session. `handleCallback()`/`refreshFromProvider()` both funnel into one `applyVerification()` that: locks the attempt row, no-ops if it's already terminal (duplicate-callback safe), checks expiry, checks the verified amount matches the attempt's own stored amount, and — only then — calls `InvoiceService::applyGatewayPayment()`. It never computes invoice totals itself.
- **`app/Events/PaymentAttemptUpdated.php`** (new): `ShouldBroadcastNow` (not queued — avoids needing a supervised queue worker just for this; revisit if a worker is already running in production) on a private channel `payment-attempts.{uuid}`. Broadcasts only `{attemptUuid, status, amount, gateway}` — no provider data, no signatures.
- **`routes/channels.php`**: added authorization for `payment-attempts.{uuid}` — any user with `view_invoice` or `record_invoice_payment` (reusing the existing `PermissionService`, not a new permission concept).
- **`routes/payment.php`** (new, required from `routes/web.php`):
  - `POST /invoice/{invoice}/payment-attempts` (auth + `record_invoice_payment`) → create an attempt.
  - `GET /invoice/{invoice}/payment-attempts/{attempt}` (auth + `view_invoice`) → status poll (socket-disconnect fallback).
  - `GET /pay/{attempt}/redirect` (public) → what the frontend opens in a new tab for any redirect-type gateway.
  - `GET|POST /pay/{gateway}/callback/{attempt}` (public) → provider return/webhook target.
- **`bootstrap/app.php`**: CSRF exemption for `pay/*/callback/*` — providers can't carry our CSRF token; every field from that route is still treated as untrusted by the driver's `verifyCallback()`.
- **`app/Http/Controllers/PaymentAttemptController.php`**, **`PaymentCallbackController.php`** (new): thin — validate input, delegate to `PaymentAttemptService`, format the response. No provider-specific logic lives here.
- **`app/Providers/AppServiceProvider.php`**: bound `PaymentAttemptInterface → PaymentAttemptService`, matching this app's existing interface/service binding convention.

## Phase 5 — Frontend

- **`npm install qrcode.vue`** — only new frontend dependency needed beyond what the broadcasting installer already added.
- **`resources/js/composables/usePaymentAttempt.ts`** (new): typed wrapper around the two attempt endpoints.
- **`resources/js/components/invoice/PaymentAttemptSocketListener.vue`** (new): isolated into its own tiny component because `useEcho()` captures its channel name once at setup time — it's not reactive. The parent modal mounts a fresh instance (keyed by `attemptUuid`) per attempt instead of trying to re-point one long-lived subscription.
- **`resources/js/components/invoice/PaymentAttemptModal.vue`** (new): the one UI for all gateways. Branches only on `gateway.sessionType`: `redirect` → opens the gateway in a new tab and shows a "waiting" state; `qr` → renders `qrcode.vue` in-page. Polls the status endpoint every 4s as a fallback and re-verifies via that same endpoint whenever the socket fires (the socket event is a "go check" signal, never treated as proof by itself).
- **`resources/js/pages/invoice/Show.vue`**: added gateway buttons next to the existing "Add payment" amount field (reusing that same amount input, so there's exactly one place to type an amount) and the modal. Added `onMounted` pickup of a `sessionStorage` flag set by Create.vue (see next item) to auto-open the modal right after a newly created invoice loads.
- **`resources/js/pages/invoice/Create.vue`** (Decision 3 from the plan): removed the old online-payment UI entirely (static QR image + free-text transaction code — the thing that was never actually verifying anything). A gateway row is now only an *intent*: pick a gateway, enter an amount. That amount is excluded from the `payments` array sent to `createInvoice` (so the server-side total/paid-amount calculation is never touched by an unverified gateway "payment") and is instead stashed in `sessionStorage` for two minutes. Because the backend already redirects to the invoice's Show page after creation, Show.vue picks that flag up on mount and opens the payment modal against the now-persisted invoice — **this is what means staff never re-enter invoice items**: the items were already committed before any gateway code runs, and the original tab never navigates away (new tab for eSewa/Khalti, in-page modal for Fonepay's QR).
- **`app/Http/Controllers/InvoiceController.php`**: `create()` no longer passes `esewaQrUrl`/`khaltiQrUrl` (dead now that the static-image flow is gone; `bankQrUrl` is untouched — that's a real bank-transfer QR, a separate concern). `show()` now also passes `paymentGateways` so the Show page can render the gateway buttons.

---

## Verification performed

- `php -l` on every new/changed PHP file — clean.
- `php artisan route:list` — all 6 new routes registered with the expected methods/middleware.
- Tinker: the factory resolves all three drivers to the right classes; `PaymentAttemptInterface`/`InvoiceInterface` resolve through the container.
- `npm run build` — clean (no new errors; two pre-existing unrelated CSS warnings).
- `php artisan test` — the one pre-existing `InvoiceControllerTest` passes in isolation. The full suite has ~50 pre-existing failures (missing testing `APP_KEY`, undefined model factories for unrelated modules) that exist independently of this work and were not introduced by it.
- No real gateway HTTP calls were made — there are no sandbox credentials in `.env` yet (all `ESEWA_*`/`KHALTI_*`/`FONEPAY_*` values are empty placeholders).

## Post-implementation fixes (found during your live testing)

- **`app/Payments/Gateways/{Khalti,Esewa,Fonepay}Gateway.php`**: `Http::retry()` defaults its `$throw` parameter to `true` — so once retries were exhausted on a non-2xx response (e.g. Khalti returning HTTP 400 for `"status":"User canceled"`, which is a perfectly valid, parseable result, not a transport failure), Laravel threw an uncaught `RequestException` before the driver's own `$response->failed()` check ever ran. Fixed by passing `throw: false` and switching the failure check to "is there a parseable status field in the body" rather than "was the HTTP status 2xx."
- **`app/Services/PaymentAttemptService.php`**: `PaymentAttemptUpdated::dispatch()` was unguarded and ran on every single status check (including repeated polls), not just on an actual state transition. With Reverb not running (or, as happened here, something else already bound to its port), the broadcast failure threw an uncaught `BroadcastException` that turned *every* poll into a 500 — even though the attempt/invoice write immediately above it had already committed successfully. Fixed by only broadcasting when status actually changed, and wrapping the broadcast in try/catch (logged, never rethrown) so a broadcasting problem can never turn a correctly-processed payment into a failed HTTP response.
- **Port conflict**: Reverb's actual server bind port is controlled by `REVERB_SERVER_PORT` (defaults to 8080), a *different* variable from `REVERB_PORT` (the client-facing port used by the browser and by this app's own outbound broadcast calls) — on this machine, port 8080 was already bound by XAMPP's Apache, so Reverb was never actually reachable there. Moved both to port 6001 in `.env`/`.env.example` (`REVERB_PORT`, `REVERB_SERVER_PORT`, `VITE_REVERB_PORT`), verified with `php artisan reverb:start` that it now binds correctly.

## UX/workflow round (post go-live feedback)

- **`app/Http/Controllers/PaymentCallbackController.php`**: the browser-return page for eSewa/Khalti now shows a brief status message, then calls `window.close()` after ~1.2s. The original invoice tab (opened the gateway in a new tab, never navigated away) is what shows the actual outcome — it's already listening via the socket/polling fallback — so this popup's only job is to confirm and get out of the way.
- **Invoice items are now immutable after creation** (by request — items can already be reported externally, e.g. IRD, so a correction means voiding/returning the invoice and creating a new one, not editing line items):
  - `InvoiceService::updateInvoice()` no longer deletes/recreates `tbl_invoice_items`. It recomputes subtotal/discount/tax/total from the items already on file (`recalculateTotalsFromExistingItems()`) combined with whatever bulk discount/tax is submitted, and only writes invoice-level columns.
  - `UpdateInvoiceRequest` no longer validates an `items` payload at all.
  - `Edit.vue`: the fee-items section is now a read-only table (no add/remove/edit controls); its live totals preview mirrors the same existing-items math as the backend.
- **Fully-paid invoices are locked**: `Edit.vue`'s entire form (`<fieldset :disabled="isFullyPaid">`) and its submit button are disabled once `paid_amount >= total_amount`, with a banner explaining why. `Show.vue`'s payment-collection card is replaced by a "fully paid" state in the same condition (unless actively correcting an existing payment row via its own "Edit" button).
- **`Show.vue` restructured into one card**: student/invoice header, status badge, back/print/edit/delete actions, dates, items, and notes are now a single `Card` (previously split across a floating header box and a separate "Invoice details" card). Payment history got its own per-method icon and now shows the gateway name alongside the method (e.g. "Online payment · khalti").
- **Payment method field now uses the app's own `CustomSelect` component** (`resources/js/pages/CustomSelect.vue`) in both `Show.vue` and `Edit.vue`, fed by `InvoiceService::getPaymentMethods()` from the backend (new `paymentMethods` prop on both the `show()` and `edit()` controller actions) instead of a hardcoded local array — this is also what fixes a real latent bug: both pages' hardcoded lists still used `online` instead of the enum's `online_payment`, which would have started failing validation the moment Phase 0's `Rule::enum(PaymentMethod::class)` fix landed.
- **Clicking a gateway button now sets the payment-method field to `online_payment`** (`Show.vue`'s `openGatewayModal()`), since choosing a gateway is choosing to pay online — previously the method field was left on whatever it defaulted to, which didn't reflect what was actually about to happen.

## What's left (Phase 6 + open items)

1. **Automated tests** (plan Phase 6): factory-mapping unit test, each driver's signature/parsing logic against mocked HTTP, feature tests for the amount/expiry/duplicate-callback/unauthorized-channel cases, Vue modal state tests. None of this is written yet.
2. **Real credentials**: `ESEWA_MERCHANT_CODE`/`SECRET_KEY`, `KHALTI_SECRET_KEY`, `FONEPAY_MERCHANT_CODE`/`USERNAME`/`PASSWORD` are all empty. Nothing can be exercised end-to-end against a live sandbox until these are supplied.
3. **Fonepay field confirmation**: the only documentation found for Fonepay's dynamic QR API was a third-party mirror, not an official fonepay.com source. Confirm field names/signing scheme with Fonepay's merchant onboarding before relying on this driver in production.
4. **Local dev callback reachability**: eSewa/Khalti need to reach `/pay/{gateway}/callback/{attempt}` over the internet to redirect back. Locally this needs a tunnel (ngrok or similar) pointed at `APP_URL`; without one, the `inquire()`/status-check path (used by the polling fallback) still works since it's an outbound call this app makes, but the browser-redirect callback won't reach a `127.0.0.1` app.
5. **`php artisan reverb:start` + a queue worker** need to be running for broadcasts to actually reach the browser in any environment (dev or production) — this wasn't automated as part of this change, since it's a process-supervision concern, not a code change.
