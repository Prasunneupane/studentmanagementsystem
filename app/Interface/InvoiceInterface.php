<?php

namespace App\Interface;

use App\Models\Invoice;
use App\Models\PaymentAttempt;
use App\Payments\DTOs\PaymentVerification;

interface InvoiceInterface
{
    public function getAllInvoices(array $filters = []): array;
    public function getInvoiceById(int $id): ?array;
    public function createInvoice(array $data): array;
    public function updateInvoice(int $id, array $data): array;
    public function deleteInvoice(int $id): bool;
    public function recordPayment(int $invoiceId, array $data): array;
    public function updatePayment(int $invoiceId, int $paymentId, array $data): array;
    public function getInvoicesByStudent(int $studentId): array;
    public function generateInvoiceNumber(): string;
    public function getStudentWithClassSection():array;
    public function getPaymentMethods():array;
    public function getInvoiceStatus():array;
    public function getPaymentGateways():array;
    public function outstandingBalance(Invoice|int $invoice): float;
    public function recordPrint(int $id): array;
    public function applyGatewayPayment(int $invoiceId, PaymentAttempt $attempt, PaymentVerification $verification): array;
}