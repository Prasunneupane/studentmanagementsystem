<?php

use App\Http\Controllers\ClassesController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\StateDistricMunController;
use App\Http\Controllers\StudentsController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeachersController;
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
        Route::get('/edit/{subject}', [SubjectController::class, 'edit'])->name('edit');
        Route::put('/update/{subject}', [SubjectController::class, 'update'])->name('update');
        Route::post('/store', [SubjectController::class, 'store'])->name('store');
    });

    Route::prefix('teachers')->name('teachers.')->group(function () {
        Route::get('/', [TeachersController::class, 'index'])->name('index');
        Route::get('/create', [TeachersController::class, 'create'])->name('create');
        Route::put('/delete-teacher/{teacher}', [TeachersController::class, 'deactivate'])->name('delete');
        Route::get('/edit/{teacher}', [TeachersController::class, 'edit'])->name('edit');
        Route::put('/update/{teacher}', [TeachersController::class, 'update'])->name('update');
        Route::post('/store', [TeachersController::class, 'store'])->name('store');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RolesController::class, 'index'])->name('index');
        Route::get('/create', [RolesController::class, 'create'])->name('create');
        Route::put('/delete-role/{role}', [RolesController::class, 'deactivate'])->name('delete');
        Route::get('/edit/{role}', [RolesController::class, 'edit'])->name('edit');
        Route::put('/update/{role}', [RolesController::class, 'update'])->name('update');
        Route::post('/store', [RolesController::class, 'store'])->name('store');
    });

    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/create', [PermissionController::class, 'create'])->name('create');
        Route::put('/delete-permission/{permission}', [PermissionController::class, 'deactivate'])->name('delete');
        Route::get('/edit/{permission}', [PermissionController::class, 'edit'])->name('edit');
        Route::put('/update/{permission}', [PermissionController::class, 'update'])->name('update');
        Route::post('/store', [PermissionController::class, 'store'])->name('store');
    });
});

// Route::get('/registerStudent', [ClassSectionController::class,'registerStudent'])->name('registerStudent');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
