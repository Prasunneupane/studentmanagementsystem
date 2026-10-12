<?php

use App\Http\Controllers\CompanyInfoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::prefix('company-info')->name('company-info.')->group(function () {
        Route::middleware(['permission:view_company_info'])->group(function () {
            Route::get('/', [CompanyInfoController::class, 'index'])->name('index');
        });

        Route::middleware(['permission:create_company_info'])->group(function () {
            Route::get('/create', [CompanyInfoController::class, 'create'])->name('create');
            Route::post('/', [CompanyInfoController::class, 'store'])->name('store');
        });

        Route::middleware(['permission:edit_company_info'])->group(function () {
            Route::get('/{companyInfo}/edit', [CompanyInfoController::class, 'edit'])->whereNumber('companyInfo')->name('edit');
            Route::put('/{companyInfo}', [CompanyInfoController::class, 'update'])->whereNumber('companyInfo')->name('update');
        });

        Route::middleware(['permission:delete_company_info'])->group(function () {
            Route::delete('/{companyInfo}', [CompanyInfoController::class, 'destroy'])->whereNumber('companyInfo')->name('destroy');
        });
    });
});
