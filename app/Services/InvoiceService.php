<?php

namespace App\Services;

use App\Enums\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Interface\InvoiceInterface;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Students;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class InvoiceService implements InvoiceInterface
{
    public function __construct(private FiscalYearService $fiscalYearService)
    {
    }
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
        $year = now()->year;
        $fiscalYear = $this->fiscalYearService->getActive();
        $last = Invoice::withTrashed()
           ->count();

        $sequence = $last ? ((int) $last + 1) : 1;

        return sprintf('INV-%s%06d',strtoupper($fiscalYear->bill_year_code),$sequence);
    }

    public function createInvoice(array $data): array
    {
         //return DB::transaction(function () use ($data) {
            $academicYearId = $data['academic_year_id'] ?? DB::table('tbl_academic_years')->where('is_active', 1)->limit(1)->value('id');
            if (! $academicYearId) {
                throw ValidationException::withMessages([
                    'academic_year_id' => 'An active academic year is required before creating an invoice.',
                ]);
            }

            [$subtotal, $discountAmount, $taxAmount, $total, $calculatedItems] = $this->calculateTotals($data['items'], $data);
            dd(
                [
                'invoice_number' => $this->generateInvoiceNumber(),
                'student_id' => $data['student_id'],
                'academic_year_id' => $academicYearId,
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
                'paid_amount' => $data['paid_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]
            );
            $invoice = Invoice::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'student_id' => $data['student_id'],
                'academic_year_id' => $academicYearId,
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
                'paid_amount' => $data['paid_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($calculatedItems as $item) {
                $invoice->items()->create([
                    'fee_type' => $item['fee_type'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'discount_type' => $item['discount_type'],
                    'discount_percentage' => $item['discount_percentage'],
                    'discount_amount' => $item['discount_amount'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);
            }
            if($data['paid_amount'] ?? 0 > 0 && !empty($data['payment'])) {
                InvoicePayment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $data['paid_amount'],
                    'paid_on' => $data['paid_on'] ?? now(),
                    'payment_method' => $data['payment_method'] ?? 'CASH',
                    'reference_no' => $data['reference_no'] ?? null,
                    'note' => $data['note'] ?? null,
                    'received_by' => Auth::id(),
                ]);
            }

            return $invoice->load(['student', 'schoolClass', 'section', 'items'])->toArray();
        //});
    }

    public function updateInvoice(int $id, array $data): array
    {
        return DB::transaction(function () use ($id, $data) {
            $invoice = Invoice::findOrFail($id);
            [$subtotal, $discountAmount, $taxAmount, $total, $calculatedItems] = $this->calculateTotals($data['items'], $data);

            $invoice->update([
                'student_id' => $data['student_id'],
                'academic_year_id' => $data['academic_year_id'] ?? $invoice->academic_year_id,
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
            foreach ($calculatedItems as $item) {
                $invoice->items()->create([
                    'fee_type' => $item['fee_type'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'discount_type' => $item['discount_type'],
                    'discount_percentage' => $item['discount_percentage'],
                    'discount_amount' => $item['discount_amount'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
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
                'payment_method' => $data['payment_method'] ?? 'CASH',
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
        $bulkDiscountValue = (float) ($data['discount_value'] ?? 0);
        $bulkDiscountActive = $bulkDiscountValue > 0;
        $calculatedItems = collect($items)->map(function (array $item) use ($bulkDiscountActive): array {
            $quantity = (int) ($item['quantity'] ?? 1);
            $unitPrice = (float) $item['unit_price'];
            $gross = $quantity * $unitPrice;
            $discountType = $bulkDiscountActive ? null : ($item['discount_type'] ?? 'fixed');
            $discountValue = $bulkDiscountActive ? 0 : (float) ($item['discount_value'] ?? 0);
            $discountAmount = $discountType === 'percentage'
                ? min($gross, $gross * $discountValue / 100)
                : min($gross, $discountValue);

            return [
                ...$item,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_type' => $discountType,
                'discount_percentage' => $discountType === 'percentage' ? $discountValue : 0,
                'discount_amount' => round($discountAmount, 2),
                'total' => round(max($gross - $discountAmount, 0), 2),
            ];
        });

        $subtotal = $calculatedItems->sum(fn (array $item) => $item['quantity'] * $item['unit_price']);
        $itemDiscountAmount = $calculatedItems->sum('discount_amount');
        $afterItemDiscount = max($subtotal - $itemDiscountAmount, 0);

        $bulkDiscountAmount = 0;
        if (($data['discount_type'] ?? null) === 'percentage') {
            $bulkDiscountAmount = $afterItemDiscount * ($bulkDiscountValue / 100);
        } elseif (($data['discount_type'] ?? null) === 'fixed') {
            $bulkDiscountAmount = $bulkDiscountValue;
        }
        $bulkDiscountAmount = min($afterItemDiscount, $bulkDiscountAmount);
        $discountAmount = $itemDiscountAmount + $bulkDiscountAmount;

        $taxable = max($afterItemDiscount - $bulkDiscountAmount, 0);
        $taxAmount = $taxable * (($data['tax_percentage'] ?? 0) / 100);
        $total = $taxable + $taxAmount;

        return [round($subtotal, 2), round($discountAmount, 2), round($taxAmount, 2), round($total, 2), $calculatedItems->all()];
    }

    private function refreshStatus(Invoice $invoice): void
    {
        $status = 'unpaid';
        if ($invoice->paid_amount >= $invoice->total_amount) {
            $status = 'paid';
        } elseif ($invoice->paid_amount > 0) {
            $status = 'partial';
        } elseif (Carbon::parse($invoice->due_date)->isPast()) {
        $status = 'overdue';
        }
        $invoice->update(['status' => $status]);
    }

    public function getStudentWithClassSection(): array
    {
         return Students::with(['class:id,name', 'section:id,name'])
                ->select('id', 'first_name', 'last_name', 'class_id', 'section_id')->get()
                ->map(fn ($s) => [
                    'value' => (string) $s->id,
                    'label' => trim("{$s->first_name} {$s->last_name}") . ' - ' . ($s->class?->name ?? 'No class') . ' - ' . ($s->section?->name ?? ' '),
                    'class_id' => $s->class_id,
                    'section_id' => $s->section_id,
                ])->toArray();
    }

    public function getPaymentMethods(): array
    {
        return array_map(fn ($method) => [
            'value' => $method->value,
            'label' => $method->label(),
            'icon' => $method->icon(),
        ], PaymentMethod::cases());
    }

    public function getPaymentGateways(): array
    {
        return array_map(fn ($gateway) => [
            'value' => $gateway->value,
            'label' => $gateway->label(),
            'icon' => $gateway->icon(),
        ], PaymentGateway::cases());
    }
}

// 1954538624