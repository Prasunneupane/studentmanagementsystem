<?php 
use App\Http\Controllers\InvoiceController;

Route::middleware(['auth'])->group(function () {

    Route::prefix('invoice')->name('invoice.')->group(function () {

        Route::middleware(['permission:create_invoice'])->group(function () {
            Route::get('/create', [InvoiceController::class, 'create'])->name('create');
            Route::post('/', [InvoiceController::class, 'store'])->name('store');
        });

        Route::middleware(['permission:view_invoice'])->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('show');
        });
        Route::middleware(['permission:edit_invoice'])->group(function () {
            Route::get('/{invoice}/edit', [InvoiceController::class, 'edit'])->whereNumber('invoice')->name('edit');
            Route::put('/{invoice}', [InvoiceController::class, 'update'])->whereNumber('invoice')->name('update');
        });
        Route::middleware(['permission:delete_invoice'])->group(function () {
            Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->whereNumber('invoice')->name('destroy');
        });
        Route::middleware(['permission:record_invoice_payment'])->group(function () {
            Route::post('/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->whereNumber('invoice')->name('payments.store');
        });
        Route::middleware(['permission:print_invoice'])->group(function () {
            Route::get('/{invoice}/print', [InvoiceController::class, 'print'])->whereNumber('invoice')->name('print');
        });
    });
   
});