<?php

use App\Http\Controllers\ClassesController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\StateDistricMunController;
use App\Http\Controllers\StudentsController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // dd('Hello World');
    return Inertia::render('auth/Login');
})->name('home');

// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard (kept as is)
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Student Management routes with prefix + name
        Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', [StudentsController::class, 'index'])->name('index');
        Route::get('/create', [StudentsController::class, 'create'])->name('create');
        Route::post('/store', [StudentsController::class, 'store'])->name('store');
        Route::get('/{student}', [StudentsController::class, 'show'])->name('show');
        Route::get('/{student}/edit', [StudentsController::class, 'edit'])->name('edit');
        Route::put('/{student}', [StudentsController::class, 'update'])->name('update');
        Route::delete('/{student}', [StudentsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('subjects')->name('subjects.')->group(function () {
        Route::get('/', [SubjectController::class, 'index'])->name('index');
        Route::get('/create', [SubjectController::class, 'create'])->name('create');
        Route::put('/deletesubject/{subject}', [SubjectController::class, 'deactivate'])->name('delete');
        Route::post('/store', [SubjectController::class, 'store'])->name('store');
    });
});

// Route::get('/registerStudent', [ClassSectionController::class,'registerStudent'])->name('registerStudent');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
