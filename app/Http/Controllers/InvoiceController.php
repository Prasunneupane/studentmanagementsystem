<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Interface\InvoiceInterface;
use App\Models\Classes;
use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceInterface $invoiceService) {}

    public function index(Request $request)
    {
        return Inertia::render('invoice/Index', [
            'invoices' => $this->invoiceService->getAllInvoices($request->only(['status', 'class_id', 'search', 'per_page'])),
            'classes' => Classes::select('id', 'name as label')->get(),
        ]);
    }

    public function create()
    {
        $studentWithClassSection = $this->invoiceService->getStudentWithClassSection();
        return Inertia::render('invoice/Create', [
           'students' => $studentWithClassSection,
        ]);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $invoice = $this->invoiceService->createInvoice($request->validated());

        return Redirect::route('invoice.show', $invoice['id'])->with('success', 'Invoice created successfully');
    }

    public function show(string $id)
    {
        $invoice = $this->invoiceService->getInvoiceById((int) $id);
        abort_unless($invoice !== null, 404);

        return Inertia::render('invoice/Show', ['invoice' => $invoice]);
    }

    public function edit(string $id)
    {
        $invoice = $this->invoiceService->getInvoiceById((int) $id);
        abort_unless($invoice !== null, 404);

        return Inertia::render('invoice/Edit', ['invoice' => $invoice]);
    }

    public function update(UpdateInvoiceRequest $request, string $id)
    {
        $invoiceId = (int) $id;
        $this->invoiceService->updateInvoice($invoiceId, $request->validated());

        return Redirect::route('invoice.show', $invoiceId)->with('success', 'Invoice updated successfully');
    }

    public function destroy(string $id)
    {
        $this->invoiceService->deleteInvoice((int) $id);

        return Redirect::route('invoice.index')->with('success', 'Invoice deleted successfully');
    }

    public function recordPayment(Request $request, string $id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_on' => 'nullable|date',
            'payment_method' => 'required|in:cash,bank_transfer,card,online,cheque',
            'reference_no' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $this->invoiceService->recordPayment((int) $id, $data);

        return Redirect::back()->with('success', 'Payment recorded successfully');
    }

    public function print(string $id)
    {
        $invoice = $this->invoiceService->getInvoiceById((int) $id);
        abort_unless($invoice !== null, 404);

        return Inertia::render('invoice/Print', ['invoice' => $invoice]);
    }
}