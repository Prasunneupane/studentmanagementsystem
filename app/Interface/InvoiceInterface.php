<?php

namespace App\Interface;

use Arr;

interface InvoiceInterface
{
    public function getAllInvoices(array $filters = []): array;

    public function getInvoiceById(int $id): ?array;

    public function createInvoice(array $data): array;

    public function updateInvoice(int $id, array $data): array;

    public function deleteInvoice(int $id): bool;

    public function recordPayment(int $invoiceId, array $data): array;

    public function getInvoicesByStudent(int $studentId): array;

    public function generateInvoiceNumber(): string;
    public function getStudentWithClassSection():array;
}