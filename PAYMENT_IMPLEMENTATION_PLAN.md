# Payment Gateway + Realtime QR Implementation Plan (Consolidated)

## Purpose

This document merges `PAYMENT_GATEWAY_FACTORY_PLAN.md` and `SOCKET_PAYMENT_IMPLEMENTATION.md` into one concrete, codebase-specific plan, checked against what actually exists today. **This is a plan for review — no application code has been changed yet.** Edit this file directly (strike out, replace, add numbered comments), and implementation will follow what's agreed here.

Where the two source documents left an open question, this plan picks a default and marks it `DECISION:` so it's easy to find and override.

> **Revision 2** (after live feedback + web research against official provider docs, cited inline): architecture generalized beyond a QR-only factory so Stripe and other future gateways plug in on the same contract; Fonepay upgraded from "scaffold only" to "attempt a real driver"; the QR-vs-redirect question below is answered with sources; the "don't make staff re-enter invoice items" requirement is solved structurally (new-tab payment, nothing ever navigates the original page away), not by trying to preserve form state.

---

## 1. What exists today (verified in code)

- "eSewa/Khalti" payment today is **not a real gateway integration**. `InvoiceController::create()` passes three *static* QR image URLs (`config('services.payment_qr.*')`, themselves just env-configured image links) to `invoice/Create.vue`. When a staff member adds a payment row with method `online_payment`, the Vue page shows the matching static image and a free-text box for a transaction code (`payment_code`) that staff type in by hand after looking at their own phone. There is no API call, no signature verification, no callback, no amount-aware QR.
- `PaymentGateway` enum (`app/Enums/PaymentGateway.php`) has only `ESEWA` and `KHALTI`. **No `FONEPAY` case.**
- `app/Services/InvoiceService.php` (bound to `App\Interface\InvoiceInterface` in `AppServiceProvider`) does all invoice math: `calculateTotals()`, `calculateItemTotal()`, `itemWiseTaxCalculation()`, `billInVoiceStatus()`/`refreshStatus()` (duplicated paid/partial/overdue logic in two places). `createInvoice()` also writes `InvoicePayment` rows inline from a `payments[]` array, trusting whatever `payment_code`/`amount` the browser sent — fine for cash/bank/cheque entered by staff, not fine for anything we want to call "verified online payment."
- **Two real bugs** in the existing payment-recording path, independent of this feature:
  - `InvoiceService::recordPayment()` and `updatePayment()` never set `payment_date` or `payment_nepali_date`, both **required (`NOT NULL`)** columns on `tbl_invoice_payments` (added by `2026_09_23_211616_invoice_column_addition.php`). These calls are at risk of a SQL error depending on strict mode.
  - `InvoiceController::recordPayment()`/`updatePayment()` validate `payment_method` against `cash,bank_transfer,card,online,cheque`, but `PaymentMethod` enum defines `online_payment`, not `online`. The default fallback `'CASH'` (uppercase) also doesn't match the enum's lowercase `cash` value.
- **Nothing realtime exists**: no `laravel/reverb`, no `laravel-echo`/`pusher-js` in `package.json`, no `config/broadcasting.php`, no `routes/channels.php`. ~~This is a greenfield addition.~~ **Done** — `laravel/reverb` installed, `php artisan install:broadcasting --reverb` run (published `config/broadcasting.php`, `routes/channels.php`, registered in `bootstrap/app.php`'s `->withRouting(channels: ...)`, wired `configureEcho({ broadcaster: 'reverb' })` into `resources/js/app.ts` via the new `@laravel/echo-vue` package), `laravel-echo` + `pusher-js` + `@laravel/echo-vue` added to `package.json`, `REVERB_*`/`VITE_REVERB_*` generated in `.env` and placeholder-only in `.env.example`.
- **No QR rendering library** on the frontend (no `qrcode.vue` or similar) — still pending, needed only for the Fonepay driver (see Decision 2 below).

### Research findings used to correct this plan (sources inline, see `PAYMENT_IMPLEMENTATION_CHANGELOG.md` research log for the full report)

- **eSewa ePay v2** (developer.esewa.com.np/pages/Epay): strictly a signed-form POST redirect to eSewa's hosted page — **no standalone QR product exists**. Signature = HMAC-SHA256, Base64, over `total_amount,transaction_uuid,product_code` joined with commas in that exact order. Status check is a GET endpoint; the return-URL payload is itself a Base64 JSON blob carrying its own signature, which must be re-verified server-side — never trust it on receipt alone.
- **Khalti ePayment API v2** (docs.khalti.com/khalti-epayment): initiate returns both `pidx` and a `payment_url` (redirect, no QR product). **The redirect/callback query params are explicitly unsigned per Khalti's own docs** — their docs instruct using the `/epayment/lookup/` POST as the only trusted verification path. This plan follows that: the callback route only triggers a lookup call; it never reads payment status from the query string.
- **Fonepay dynamic QR**: a genuine merchant QR-generation API exists (request a QR for an exact amount/reference, get back a `qrMessage` payload to render, plus a status-check endpoint and an optional WebSocket push). However, the only documentation found (`API_Details.md` in a third-party GitHub mirror, self-labeled "Fonepay Dynamic QR API v1.1") is **not hosted on an official fonepay.com/dev.fonepay.com domain** — treat the exact field names (`prn`, `merchantCode`, `dataValidation`, HMAC-SHA512) as the best available reference, not confirmed-official. **Action before going live: confirm these fields with Fonepay's own merchant onboarding.** Earlier field names floated in this plan (`PID`/`BID`/`UID`/`DV`, from the original medium article) do not appear in this more current source and should be discarded.
- No actively maintained Laravel package implements a multi-gateway driver/factory abstraction — `dipesh79/laravel-esewa` and `neputertech/khalti` are maintained but are each a single thin service class. They're useful only to cross-check field names, not as an architecture reference; this plan's factory/contract design is original to this codebase.
- **Stripe** (for future extensibility, not implemented now): Checkout Sessions (`mode: payment`) via `stripe/stripe-php` directly, independent of Laravel Cashier (which targets subscriptions). Redirect-based, webhook-verified via the `Stripe-Signature` header. Confirms the driver contract below (redirect-type session + signed verification) already fits a fourth, unrelated provider without modification — the point of Decision 1/2 below.

## 2. Decisions (defaults chosen — please confirm or edit)

**DECISION 1 — Which gateways get real integrations now, and how new ones (Stripe) get added later.**
Build **eSewa, Khalti, and Fonepay** now (Fonepay flagged as "verify fields with Fonepay before production" per the research above, but a real driver, not a stub — per your request to attempt it). The driver contract is deliberately **not QR-specific**: it returns a `PaymentSession` that is either a `redirect` session (a URL to send the payer to) or a `qr` session (a payload to render as a QR in-page). Stripe, or any future gateway, is just a new enum case + a new driver class returning a `redirect` session — no change to the contract, factory, controller, or frontend modal shell. This is what "easy to add, security tight" means concretely: the attack surface (signature verification, amount/currency re-checking, idempotent completion) lives once per driver behind the same contract, not duplicated per integration.

**DECISION 2 — QR vs. redirect, per gateway, based on what's actually real (not forced into one shape).**
Confirmed by research, not assumed: eSewa and Khalti have **no standalone QR product** — both are hosted-checkout redirects. Forcing them into a QR (even one that links back to our own redirect route) would be theater, not a real QR. So:
- **eSewa, Khalti (and later Stripe)** → `redirect` session. The frontend opens the provider's checkout URL in a **new browser tab** (`window.open`), never navigates the current page away. The original invoice tab stays exactly as it was and simply listens on the private payment-attempt channel for the outcome.
- **Fonepay** → `qr` session. Its `qrMessage` payload is rendered as an actual scannable QR in the same modal, in-page, no new tab, no redirect — this is the one gateway where a real QR is honest to build.

This directly answers your question: **true QR is only real for Fonepay here; eSewa/Khalti get "pay in a new tab," not a fake QR.**

**DECISION 3 — Solving "don't make staff re-enter invoice items" structurally, not by preserving form state.**
Both source documents require the invoice to be persisted *before* a real gateway session opens — and that requirement turns out to be exactly what also solves this. Because payment always happens in a new tab/modal against an **already-saved** invoice:
- Create page lets staff optionally mark "collect X via eSewa/Khalti/Fonepay now" as an *intent* (gateway + amount), not a payment row.
- On submit, the invoice is created from priced items + any cash/bank/card/cheque rows only (today's logic, unchanged) — exactly as it does today, so nothing about item entry changes.
- If a gateway intent was set, the Show page (which the create flow already redirects to) auto-opens the payment modal for that already-persisted invoice and amount.
- The `Show` page also gets a general "Collect remaining balance via gateway" action for any invoice with `due_amount > 0`, independent of creation flow.
- Because the original tab never navigates away (new tab for redirect gateways, in-page modal for Fonepay's QR), there is **nothing to restore** — the items the staff entered were already committed to the database before any gateway code runs, and the browser tab holding the create/show page is simply untouched throughout.
→ *Edit this if you'd rather keep everything on the Create page with a draft-invoice status instead — that's a materially bigger change (new `draft` invoice state) and wasn't needed to satisfy "persist before initiating a session."*

**DECISION 4 — Partial payment rule.** Any amount where `0 < amount <= outstanding balance` (both source docs agree; `outstanding balance = total_amount - paid_amount`, computed server-side, never trusted from the browser).

**DECISION 5 — Realtime transport.** Laravel Reverb (first-party, self-hosted, matches `SOCKET_PAYMENT_IMPLEMENTATION.md`'s recommendation and this app's "no external vendor accounts" posture elsewhere).

**DECISION 6 — Pre-existing bugs (section 1).** Fixed as part of this work (Phase 0 below) since the new gateway-payment path reuses the same `tbl_invoice_payments` columns and would inherit the same mistakes otherwise.

---

## 3. SOLID mapping (per your instruction)

| Principle | How it's applied |
|---|---|
| **S**ingle Responsibility | `InvoiceService` keeps *all* invoice/payment math (totals, outstanding balance, status, applying a verified gateway payment). Gateway drivers only know their provider's HTTP/signature details. A new `PaymentAttemptService` only orchestrates attempt lifecycle (create → initiate → verify → complete) and calls into `InvoiceService` for the math. Controllers stay thin (validate input, call a service, return a response). |
| **O**pen/Closed | Adding a provider = new enum case + new driver class + one factory mapping line. No existing driver, controller, or `InvoiceService` code changes. |
| **L**iskov Substitution | Every driver implements the same `PaymentGatewayDriver` contract and is interchangeable wherever that contract is type-hinted (factory, `PaymentAttemptService`, tests). |
| **I**nterface Segregation | The contract only has the three methods every driver genuinely needs (`createSession`, `verifyCallback`, `inquire`). Nothing provider-specific leaks into it. |
| **D**ependency Inversion | `PaymentAttemptService` and controllers depend on `PaymentGatewayDriver`/`PaymentGatewayFactory` abstractions and on `InvoiceInterface`, never on `EsewaGateway` etc. directly. Bound in `AppServiceProvider`, same convention already used for every other service in this app. |

---

## 4. Data model changes

### New table: `tbl_payment_attempts`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `uuid` | string, unique | Public identifier; used in URLs and the broadcast channel name |
| `invoice_id` | FK → `tbl_invoices` | cascadeOnDelete |
| `gateway` | string | `PaymentGateway` enum value |
| `amount` | decimal(10,2) | Requested amount for this attempt only |
| `currency` | string, default `NPR` | |
| `status` | string, default `pending` | `pending`, `succeeded`, `failed`, `expired`, `cancelled` |
| `provider_transaction_id` | string, nullable, indexed | Provider's own reference (eSewa ref id / Khalti `pidx` / Fonepay txn id) |
| `provider_reference` | string, nullable | Secondary provider field if needed (e.g. signature or token) |
| `checkout_url` | text, nullable | What `/pay/{uuid}/redirect` sends the browser to |
| `qr_payload` | text, nullable | What the Vue modal encodes into the QR (our own redirect URL, per Decision 2) |
| `expires_at` | timestamp | |
| `verified_at` | timestamp, nullable | Set only after server-side verification succeeds |
| `created_by` | FK → `users`, nullable | Staff member who started the attempt |
| `meta` | json, nullable | Non-secret debug info only (never raw credentials/signature material) |
| timestamps | | |

Indexes: `uuid` (unique), `invoice_id`, `status`, `(gateway, status)`.

### `tbl_invoice_payments` — no schema change

Existing columns (`payment_gateway`, `payment_code`, `reference_no`) are reused: a completed attempt writes one `InvoicePayment` row with `payment_gateway` = the enum value and `reference_no` = the provider's transaction id, `payment_code` left null (that field was only ever for manually-typed codes, which this flow removes for gateway payments).

### `PaymentGateway` enum

Add `case FONEPAY = 'fonepay';` with label/icon, matching the existing pattern.

---

## 5. Backend components

```
app/Payments/
  Contracts/PaymentGatewayDriver.php      # createSession(), verifyCallback(), inquire()
  DTOs/PaymentRequest.php                 # readonly: invoiceId, attemptUuid, amount, currency, successUrl, failureUrl
  DTOs/PaymentSession.php                 # readonly: type ('redirect'|'qr'), providerTransactionId, redirectUrl|qrPayload, expiresAt
  DTOs/PaymentVerification.php            # readonly: verified, providerStatus, providerTransactionId, paidAmount, currency, failureReason
  Factories/PaymentGatewayFactory.php     # make(PaymentGateway): PaymentGatewayDriver
  Gateways/EsewaGateway.php               # type: redirect — signed form auto-submit, HMAC-SHA256
  Gateways/KhaltiGateway.php              # type: redirect — payment_url; completion ONLY via lookup API (callback params are unsigned per Khalti's own docs)
  Gateways/FonepayGateway.php             # type: qr — real driver; field names flagged for confirmation with Fonepay, see research note above
  # Gateways/StripeGateway.php             # not built now — same contract, type: redirect, listed here so adding it later is "drop a file in, add an enum case"

app/Models/PaymentAttempt.php             # casts, relations, isPending()/isExpired() helpers

app/Interface/PaymentAttemptInterface.php
app/Services/PaymentAttemptService.php    # createAttempt(), handleProviderCallback(), completeAttempt(), expireStaleAttempts()

app/Events/PaymentAttemptUpdated.php      # ShouldBroadcast — attempt id, status, amount only

app/Http/Controllers/PaymentAttemptController.php
  store()     # POST  /invoice/{invoice}/payment-attempts     -> returns {type, redirectUrl|qrPayload, expiresAt, attemptUuid}
  redirect()  # GET   /pay/{attempt:uuid}/redirect            (public; only used for redirect-type sessions — opened in a NEW TAB by the frontend, never navigates the invoice page)
  status()    # GET   /invoice/{invoice}/payment-attempts/{attempt:uuid}  (auth, polling fallback)

app/Http/Controllers/PaymentCallbackController.php
  esewa()     # POST  /pay/esewa/callback     (public, CSRF-exempt) -> verify signature, then status-check endpoint
  khalti()    # GET   /pay/khalti/callback    (public, CSRF-exempt) -> ignores query params' status, calls /epayment/lookup/ as the only trusted source
  fonepay()   # POST  /pay/fonepay/callback   (public, CSRF-exempt) -> verify HMAC-SHA512, then status-check endpoint

routes/payment.php                        # new file, required from routes/web.php
routes/channels.php                       # already published by `install:broadcasting`; payment-attempts.{uuid} channel added here
```

### Where the calculation lives (your explicit requirement)

`InvoiceInterface` gains two methods, implemented in `InvoiceService` — **all business math for gateway payments goes through here, nowhere else**:

```php
public function outstandingBalance(int $invoiceId): float; // max(total_amount - paid_amount, 0), server-authoritative
public function applyGatewayPayment(int $invoiceId, PaymentAttempt $attempt, PaymentVerification $verification): array;
    // Inside a DB transaction: lockForUpdate() the invoice, re-validate amount <= outstanding balance,
    // create the InvoicePayment row, increment paid_amount, recompute status via the existing
    // refreshStatus()/billInVoiceStatus() logic (de-duplicated into one method as part of Phase 0),
    // return the fresh invoice.
```

`PaymentAttemptService::completeAttempt()` locks the `PaymentAttempt` row, checks it's still `pending` and unexpired, then calls `InvoiceService::applyGatewayPayment()` — it never touches `paid_amount`/`status` itself. This is the SRP boundary: attempt lifecycle vs. invoice math stay in separate classes.

### Idempotency & security (from both source docs, concretely)

- Callback routes are CSRF-exempt (external POSTs) but every field is treated as untrusted: the driver's `verifyCallback()` must check the provider's signature (eSewa/Khalti each have a documented signing scheme) before `PaymentAttemptService` does anything else.
- `completeAttempt()` is guarded by `DB::transaction` + `lockForUpdate()` on both the attempt and the invoice, and checks `status === 'pending'` before writing — a duplicate callback (same provider transaction id, retried webhook) finds the attempt already `succeeded` and no-ops.
- A unique index on `provider_transaction_id` (nullable, so only enforced once populated) is a second line of defense against double-processing.
- `channels.php` authorizes `payment-attempts.{uuid}` only for a user who can manage the underlying invoice (reuse the `record_invoice_payment`/`view_invoice` permission check already used by the routes).
- Broadcast payload is restricted to `{attempt_uuid, status, amount, gateway}` — no secrets, no signature material, no full provider payloads.
- Amounts/currency from `verifyCallback()`/`inquire()` are compared against the attempt's own stored `amount`/`currency` before completion — a verified callback for the wrong amount is rejected, not silently accepted.

---

## 6. Frontend changes

- ~~`npm install laravel-echo pusher-js qrcode.vue`~~ — `laravel-echo`, `pusher-js`, `@laravel/echo-vue` **done** (via `install:broadcasting`). `qrcode.vue` still to add (only Fonepay needs it).
- ~~`resources/js/echo.ts`~~ — not needed as a separate file; `configureEcho({ broadcaster: 'reverb' })` is already called once in `resources/js/app.ts`, and components use the `useEcho`/`echo()` helpers from `@laravel/echo-vue` directly.
- New component `resources/js/components/invoice/PaymentAttemptModal.vue`:
  - Props: `invoiceId`, `gateway`, `amount`.
  - On mount: POST to create the attempt. Branch on the response's `type`:
    - `redirect` (eSewa, Khalti, future Stripe): show "Opening <Gateway> in a new tab…", call `window.open(redirectUrl, '_blank')`. The modal itself shows amount/total/remaining balance and a pending state — it does **not** navigate the current tab.
    - `qr` (Fonepay): render `qrPayload` via `qrcode.vue` in-page, same amount/total/remaining-balance display, countdown to `expiresAt`.
  - Subscribes to `private-payment-attempts.{uuid}` via Echo while open; on event, re-fetches the status endpoint (never trusts the socket payload as proof) and transitions pending → success/failed/expired.
  - Unsubscribes on close/unmount/expiry; falls back to polling the status endpoint if the socket disconnects.
  - Themed per gateway (eSewa green / Khalti purple / Fonepay's brand color).
- `invoice/Create.vue`: remove the manual online-payment row (QR image + free-text code) per Decision 3; replace with a gateway-intent picker that's carried through to the post-create redirect. Because the invoice is already saved by the time this modal opens, the Create page's own item/discount/tax state is simply never touched again — nothing to preserve, nothing to restore.
- `invoice/Show.vue`: add "Collect via eSewa/Khalti/Fonepay" action (enabled whenever `due_amount > 0`) that opens `PaymentAttemptModal`.
- Labelled "Live" once a given gateway's driver is real (all three, per Decision 1) — "Mock" labelling from the original socket plan doesn't apply since these are real drivers against sandbox credentials, not a simulate-payment mock.

---

## 7. Environment & config

```
PAYMENTS_ENV=sandbox            # explicit, independent of APP_DEBUG — never branch on APP_DEBUG

ESEWA_MERCHANT_CODE=
ESEWA_SECRET_KEY=
KHALTI_SECRET_KEY=
FONEPAY_MERCHANT_CODE=
FONEPAY_USERNAME=
FONEPAY_PASSWORD=
# StripeGateway not built yet; when added: STRIPE_SECRET_KEY / STRIPE_WEBHOOK_SECRET, same pattern

BROADCAST_CONNECTION=reverb     # done — see note below
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=
REVERB_PORT=
REVERB_SCHEME=
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

All of the above go in `.env.example` with **empty** values; real sandbox secrets only in the untracked local `.env`. A new `config/payments.php` reads them via `config()` (never `env()` outside config files, per existing article-critique in the factory plan).

**Status: Reverb/broadcasting block done.** `REVERB_*` values are generated (random, local-only) in `.env`; `.env.example` has the keys with empty values. `ESEWA_*`/`KHALTI_*`/`FONEPAY_*` are still empty everywhere — these need real sandbox values from you (or from each provider's published UAT merchant account where one exists, e.g. eSewa's documented test merchant `EPAYTEST`) before any driver can be exercised end-to-end, though the drivers can be built and unit-tested against mocked HTTP without them.

---

## 8. Phased implementation order

1. **Phase 0 — fix existing bugs, no new features.** `recordPayment`/`updatePayment` set `payment_date`/`payment_nepali_date`; align `PaymentMethod` enum value (`online_payment`) with controller validation; de-duplicate `refreshStatus()`/`billInVoiceStatus()` into one `InvoiceService` method.
2. **Phase 1 — scaffolding.** ~~Reverb/Echo install and config~~ **done**. Remaining: migrations (`tbl_payment_attempts`, `FONEPAY` enum case), `PaymentAttempt` model, contracts/DTOs (with the `redirect`/`qr` discriminator), factory, `PaymentAttemptService` + interface + binding, `routes/channels.php` entry for `payment-attempts.{uuid}`.
3. **Phase 2 — Khalti driver + full attempt lifecycle.** Simplest to verify end-to-end since its lookup API is unambiguous; build the whole pipe (create attempt → new-tab redirect → lookup-based callback → complete → broadcast) against it first.
4. **Phase 3 — eSewa driver.** Same contract, HMAC-SHA256 signing + status-check verification.
5. **Phase 4 — Fonepay driver.** `qr`-type session, HMAC-SHA512 signing per the semi-official spec — flagged for field confirmation before production use, but implemented for real now per your request.
6. **Phase 5 — frontend.** `PaymentAttemptModal.vue` (new-tab for redirect-type, in-page QR for qr-type), Create/Show page changes, `qrcode.vue` install.
7. **Phase 6 — tests.** Factory mapping, each driver's request/response/signature handling (mocked HTTP), feature tests for the amount/expiry/duplicate/unauthorized cases listed in both source docs, Vue modal states.

Each phase is independently shippable and reviewable — recommend merging/reviewing phase by phase rather than as one large change.

---

## 9. Open items still needing your input

1. eSewa merchant code/secret, Khalti secret key, Fonepay merchant code/username/password — sandbox credentials to use (never the article's, never committed). Drivers will be built and unit-tested against mocked HTTP regardless; real end-to-end testing needs these.
2. Does the local/production environment have a public HTTPS endpoint for provider callbacks? If not yet, Phases 2–4 can still be built and tested via each provider's inquiry/lookup/status-check API, with the callback route ready for when a public URL exists.
3. Confirm Fonepay's field names/signature scheme directly with Fonepay before relying on this in production — the only source found for them is a third-party mirror, not fonepay.com itself.

---

## 10. What you'll get after implementation

A second markdown file, `PAYMENT_IMPLEMENTATION_CHANGELOG.md`, listing every file touched, grouped by phase, with a one-line "what" and "why" per file — so the change is auditable against this plan.
