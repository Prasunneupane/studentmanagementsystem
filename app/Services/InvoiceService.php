<?php

namespace App\Services;

use App\Interface\InvoiceInterface;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService implements InvoiceInterface
{
    public function getAllInvoices(array $filters = []): array
    {
        $query = Invoice::with(['student:id,first_name,last_name,photo,class_id', 'schoolClass:id,name', 'section:id,name']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($sq) => $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"));
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15)->toArray();
    }

    public function getInvoiceById(int $id): ?array
    {
        $invoice = Invoice::with(['student', 'schoolClass', 'section', 'items', 'payments'])->find($id);

        return $invoice ? $invoice->toArray() : null;
    }

    public function getInvoicesByStudent(int $studentId): array
    {
        return Invoice::with('items')->where('student_id', $studentId)->latest()->get()->toArray();
    }

    public function generateInvoiceNumber(): string
    {
        $year = now()->format('Y');
        $last = Invoice::withTrashed()
            ->where('invoice_number', 'like', "INV-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $sequence = $last ? ((int) Str::afterLast($last->invoice_number, '-') + 1) : 1;

        return sprintf('INV-%s-%06d', $year, $sequence);
    }

    public function createInvoice(array $data): array
    {
        return DB::transaction(function () use ($data) {
            [$subtotal, $discountAmount, $taxAmount, $total] = $this->calculateTotals($data['items'], $data);

            $invoice = Invoice::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'student_id' => $data['student_id'],
                'class_id' => $data['class_id'] ?? null,
                'section_id' => $data['section_id'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'discount_amount' => $discountAmount,
                'tax_percentage' => $data['tax_percentage'] ?? 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'paid_amount' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['items'] as $item) {
                $invoice->items()->create([
                    'fee_type' => $item['fee_type'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'],
                    'amount' => ($item['quantity'] ?? 1) * $item['unit_price'],
                ]);
            }

            return $invoice->load(['student', 'schoolClass', 'section', 'items'])->toArray();
        });
    }

    public function updateInvoice(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $invoice = Invoice::findOrFail($id);
            [$subtotal, $discountAmount, $taxAmount, $total] = $this->calculateTotals($data['items'], $data);

            $invoice->update([
                'student_id' => $data['student_id'],
                'class_id' => $data['class_id'] ?? null,
                'section_id' => $data['section_id'] ?? null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'subtotal' => $subtotal,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'discount_amount' => $discountAmount,
                'tax_percentage' => $data['tax_percentage'] ?? 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->items()->delete();
            foreach ($data['items'] as $item) {
                $invoice->items()->create([
                    'fee_type' => $item['fee_type'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'],
                    'amount' => ($item['quantity'] ?? 1) * $item['unit_price'],
                ]);
            }

            $this->refreshStatus($invoice);

            return $invoice->load(['student', 'schoolClass', 'section', 'items'])->toArray();
        });
    }

    public function deleteInvoice(int $id): bool
    {
        return (bool) Invoice::destroy($id);
    }

    public function recordPayment(int $invoiceId, array $data): array
    {
        return DB::transaction(function () use ($invoiceId, $data) {
            $invoice = Invoice::findOrFail($invoiceId);

            InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'amount' => $data['amount'],
                'paid_on' => $data['paid_on'] ?? now(),
                'payment_method' => $data['payment_method'] ?? 'cash',
                'reference_no' => $data['reference_no'] ?? null,
                'note' => $data['note'] ?? null,
                'received_by' => Auth::id(),
            ]);

            $invoice->increment('paid_amount', $data['amount']);
            $this->refreshStatus($invoice->fresh());

            return $invoice->fresh(['payments'])->toArray();
        });
    }

    private function calculateTotals(array $items, array $data): array
    {
        $subtotal = collect($items)->sum(fn ($item) => ($item['quantity'] ?? 1) * $item['unit_price']);

        $discountAmount = 0;
        if (($data['discount_type'] ?? null) === 'percentage') {
            $discountAmount = $subtotal * (($data['discount_value'] ?? 0) / 100);
        } elseif (($data['discount_type'] ?? null) === 'fixed') {
            $discountAmount = (float) ($data['discount_value'] ?? 0);
        }

        $taxable = $subtotal - $discountAmount;
        $taxAmount = $taxable * (($data['tax_percentage'] ?? 0) / 100);
        $total = $taxable + $taxAmount;

        return [round($subtotal, 2), round($discountAmount, 2), round($taxAmount, 2), round($total, 2)];
    }

    private function refreshStatus(Invoice $invoice): void
    {
        $status = 'unpaid';
        if ($invoice->paid_amount >= $invoice->total_amount) {
            $status = 'paid';
        } elseif ($invoice->paid_amount > 0) {
            $status = 'partial';
        } elseif ($invoice->due_date->isPast()) {
            $status = 'overdue';
        }
        $invoice->update(['status' => $status]);
    }
}