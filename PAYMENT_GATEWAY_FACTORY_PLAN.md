# Payment Gateway Factory Plan: eSewa, Khalti, and Fonepay

## Scope

This document proposes a QR-first payment integration for the Laravel 12 + Vue/Inertia student invoice application. It analyzes the linked article and describes the architecture to use when implementation is authorized. No application code or dependencies are changed by this plan.

Article analyzed: [eSewa, Khalti and Fonepay integration in Laravel](https://pratikshrestha404.medium.com/esewa-khalti-and-fonepay-integration-in-laravel-3a5ca3c138b5)

## Article Analysis

The article's strongest architectural idea is a common gateway contract implemented by separate eSewa, Khalti, and Fonepay classes. Its proposed operations cover payment initiation, transaction inquiry, interpreting success, and determining the amount reported by a provider. This separation is a useful starting point for a Factory pattern: the application selects a provider adapter without putting provider-specific API logic in controllers.

The code should not be copied as-is:

- It was written in 2024, and gateway endpoints, signatures, required fields, sandbox rules, and response schemas may have changed.
- The examples are provider-specific web checkout/redirect or form flows; they do not establish that all three integrations return an approved dynamic QR payload suitable for a modal.
- The eSewa sample builds and auto-submits a gateway form. Khalti uses an initiation/payment URL and a `pidx` inquiry. The Fonepay sample uses merchant request and verification fields such as `PRN`, `PID`, `BID`, `UID`, and `DV`. These cannot safely be forced into identical provider-specific request/response handling.
- The examples select sandbox/live endpoints by checking `APP_DEBUG`. Environment selection must instead be an explicit payments configuration, independent of debug mode.
- The article reads secrets with `env()` in integration classes. Application code should read `config()` values; environment variables belong in config files and untracked deployment secrets.
- A browser return URL or callback parameter is not proof that a payment succeeded. The server must verify the callback signature when the provider supports one and perform a server-to-server inquiry before recording a successful payment.
- The article includes test-account values and sample keys. They are not repeated here. Treat them as public, potentially expired examples, not credentials for this application.

The Factory/adapter concept is reusable. The article's endpoint details, status checks, QR behavior, and sample credentials require confirmation against current official merchant documentation and the merchant account's sandbox access before implementation.

## Non-Negotiable QR Requirement

The payment modal must show a QR for the selected provider, but each QR must come from a supported payment flow for that provider. Do not treat a static brand QR as an invoice payment, and do not assume that any returned `payment_url` is valid to encode as a QR. Confirm that the specific eSewa, Khalti, and Fonepay merchant products support QR/deep-link checkout and that they return or accept a dynamic amount/transaction reference.

The adapter's initiation result should provide either:

- a provider-generated QR payload/image, or
- a provider-approved checkout/deep-link URL explicitly documented as safe to encode into a QR.

If a provider offers only a redirect/form checkout for this merchant account, it does not satisfy the requested modal QR experience. Stop and resolve that product/API requirement before writing its adapter. Do not silently fall back to manual transaction-code entry or show an unrelated static QR.

A QR being scanned/opened is not evidence of payment. Only a server-side verified provider result can mark a payment successful.

## Recommended Factory Design

Keep gateway behavior behind a small provider contract and return normalized application objects. Do not make the controller contain a switch statement with provider HTTP logic.

Suggested structure:

```text
app/Payments/
  Contracts/PaymentGatewayDriver.php
  DTOs/PaymentRequest.php
  DTOs/PaymentSession.php
  DTOs/PaymentVerification.php
  Factories/PaymentGatewayFactory.php
  Gateways/EsewaGateway.php
  Gateways/KhaltiGateway.php
  Gateways/FonepayGateway.php
```

`PaymentGatewayFactory` accepts the validated `PaymentGateway` enum and returns the matching driver. Each driver implements the same application-facing methods while retaining its own API fields, signatures, endpoints, and response parsing.

Suggested contract responsibilities:

```text
createSession(PaymentRequest): PaymentSession
verifyCallback(untrusted callback data): PaymentVerification
inquire(PaymentAttempt): PaymentVerification
```

`PaymentSession` should normalize the values needed by the application, for example:

```text
gateway
providerTransactionId
qrPayload or qrImageUrl
expiresAt
```

`PaymentVerification` should normalize:

```text
verified
providerStatus
providerTransactionId
paidAmount
currency
failureReason (safe display text only)
```

The normalized shape must not erase important provider differences. Drivers own request signing, provider-specific HTTP calls, callback parsing, inquiry, amount/currency checks, and mapping provider states to application states. Use dedicated DTOs/value objects rather than unvalidated arrays for the boundaries where practical.

The factory maps the existing enum value to a driver. When Fonepay is approved, add its enum case and label/icon, then register its driver in one predictable factory mapping. Validate gateway input against enum values before resolving a driver; never accept a class name from the browser.

## Payment and QR Lifecycle

The create-invoice page currently begins from invoice line items and can collect partial amounts. The payment amount must remain separate from the invoice total. For example, a 12,000 payment on a 25,000 invoice leaves 13,000 due.

1. Validate that there is at least one priced fee item and the computed payable total is positive. If zero, show: “Please add an item with a price.” Do not create a gateway session or open its QR modal.
2. Persist the invoice before initiating a real gateway session. If the product requires checkout before final invoice creation, introduce a persisted draft invoice rather than trusting browser totals.
3. Create a `pending` payment attempt in the database with an opaque UUID, invoice ID, gateway, amount, NPR currency, expiry, and provider identifiers as they become available.
4. Check the requested partial amount against the current server-calculated outstanding balance. Reject zero, negative, and over-balance amounts.
5. Resolve the selected driver through `PaymentGatewayFactory` and initiate the provider's supported QR checkout.
6. Save the normalized session/transaction references and return the QR payload/image/approved URL, amount, expiry, and attempt ID to Vue.
7. Render the QR inside a modal themed for eSewa, Khalti, or Fonepay. Brand colors and logos should identify the selected provider without implying success. Show amount to pay, invoice total, and outstanding balance clearly. Display pending, success, failure, expired, and reconnecting states.
8. Receive the provider return/callback on a Laravel endpoint. Treat every field as untrusted until verified. Use the provider driver's signature/verification/inquiry path and check the exact attempt, amount, currency, merchant, and transaction reference.
9. In a database transaction, lock the payment attempt and invoice, ensure the attempt is still pending and unexpired, prevent duplicate completion, create a successful `InvoicePayment`, recalculate invoice paid amount/status from successful payments, and mark the attempt terminal.
10. Broadcast the persisted state change through Laravel Reverb. Vue uses the event to know when to refresh server state; the socket event itself does not authenticate payment.

Keep `PaymentAttempt` separate from successful `InvoicePayment` records. A pending or failed gateway attempt must not increase paid amount or make the invoice appear paid.

## Socket Responsibilities

Use Laravel Reverb + Laravel Echo for browser status updates, as recommended in `SOCKET_PAYMENT_IMPLEMENTATION.md`.

- Authorize a private channel for the particular payment attempt and user/invoice permissions.
- Broadcast only safe state (attempt ID, status, amount, expiry); never broadcast API secrets, signature inputs, or sensitive customer data.
- Broadcast after the payment transaction commits.
- Make callback processing idempotent. Duplicate provider callbacks must not create duplicate payment records.
- Vue subscribes while the modal is open, unsubscribes on close/unmount/expiry, handles connection loss, and reloads the authoritative invoice/attempt status from Laravel after an event.
- For local mock testing, use an explicit mock driver or mock-confirmation endpoint. Do not make a public client event or QR scan update the attempt directly.

## Credentials and Environment Configuration

Obtain current sandbox credentials and QR/API specifications from each provider's official merchant documentation/account. The article's included testing values may be old or public; do not use them as application secrets.

Use explicit configuration, for example:

```text
PAYMENTS_ENV=sandbox
ESEWA_MERCHANT_CODE=...
ESEWA_SECRET_KEY=...
KHALTI_PUBLIC_KEY=...
KHALTI_SECRET_KEY=...
FONEPAY_MERCHANT_ID=...
FONEPAY_SECRET_KEY=...
```

The exact variables must follow each current provider's requirements. Keep secret values only in an untracked local `.env` or deployment secret store. Add non-secret variable names/documentation to `.env.example`, never secret values. Put endpoints and credentials under `config/services.php` (or a dedicated payments config file), read them via `config()`, and choose sandbox/live explicitly. Do not switch gateways based on `APP_DEBUG`.

Use Laravel's HTTP client with TLS verification, timeouts, bounded retries where safe, and structured logging that redacts secrets and signature material. Do not log full payment payloads or credentials.

## Testing Plan

- Unit test factory mapping for eSewa, Khalti, and Fonepay; reject an unsupported enum value.
- Unit test each driver's request construction, QR/session response parsing, signature generation/validation, inquiry mapping, and error responses using mocked HTTP.
- Feature test zero/negative amount, no priced items, partial payment, exact outstanding balance, overpayment, expired attempt, wrong gateway/reference, wrong amount/currency, duplicate callback, and unauthorized attempt/channel access.
- Test the Vue modal for each provider theme, QR rendering, loading/pending/success/failure/expiry states, and socket reconnect behavior.
- Use sandbox credentials only in opt-in integration tests; never require real credentials for the default test suite.
- Verify that each provider's current merchant sandbox actually returns a QR/deep link compatible with the intended modal before accepting its adapter as complete.

## Decisions Required Before Implementation

1. Which official merchant product/API will supply the QR for each provider? Confirm eSewa, Khalti, and Fonepay separately; the article alone does not establish a common QR capability.
2. Does QR scan open an external provider app/browser checkout, or a provider-hosted dynamic QR confirmation screen?
3. Should invoice creation finish first and then open a payment modal, or should the application create a draft invoice and support checkout before finalization?
4. What is the desired partial-payment rule? Recommended: allow any positive amount up to the current outstanding balance and keep the remaining amount due.
5. Which sandbox merchant accounts and callback URLs are available, and can the local/production app receive provider callbacks over HTTPS?

## Project Baseline

At the time of writing, the repository uses Laravel 12, PHP 8.2+, Vue 3, and Inertia. `PaymentGateway` currently contains eSewa and Khalti; Fonepay has not yet been added. No existing gateway driver/factory, Laravel Echo client, or Reverb broadcast integration was found. The project has a separate realtime socket plan in `SOCKET_PAYMENT_IMPLEMENTATION.md`.
