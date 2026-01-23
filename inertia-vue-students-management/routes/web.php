<?php

use App\Http\Controllers\ClassesController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\StateDistricMunController;
use App\Http\Controllers\StudentsController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeachersController;
use App\Http\Controllers\UserCheckController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Middleware\CheckPermission;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard - accessible to all authenticated users
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Student Management Routes
    Route::prefix('students')->name('students.')->group(function () {
        // View students list - requires students.view permission
            Route::middleware(['permission:students.create'])->group(function () {
            Route::get('/create', [StudentsController::class, 'create'])->name('create');
            Route::post('/store', [StudentsController::class, 'store'])->name('store');
        });
        Route::middleware(['permission:students.view'])->group(function () {
            Route::get('/', [StudentsController::class, 'index'])->name('index');
            Route::get('/{student}', [StudentsController::class, 'show'])->name('show');
             Route::get('/load/by-date-range', [StudentsController::class, 'loadByDateRange'])->name('load.by.date.range');
        
            });

    // Edit student - requires students.edit permission
        Route::middleware(['permission:students.edit'])->group(function () {
            Route::get('/{student}/edit', [StudentsController::class, 'edit'])->name('edit');
            Route::put('/{student}', [StudentsController::class, 'update'])->name('update');
        });

        // Delete student - requires students.delete permission
        Route::middleware(['permission:students.delete'])->group(function () {
            Route::delete('/{student}', [StudentsController::class, 'destroy'])->name('destroy');
        });
         Route::get('/{student}/guardians', [StudentsController::class, 'getGuardians'])->name('guardians.get');
        Route::post('/{student}/guardians', [StudentsController::class, 'storeGuardian'])->name('guardians.store');
        Route::put('/guardians/{guardian}', [StudentsController::class, 'updateGuardian'])->name('guardians.update');
        Route::delete('/guardians/{guardian}', [StudentsController::class, 'destroyGuardian'])->name('guardians.destroy');
        
    });

    // Subject Management Routes
    Route::prefix('subjects')->name('subjects.')->group(function () {
        // View subjects
        Route::middleware(['permission:subjects.view'])->group(function () {
            Route::get('/', [SubjectController::class, 'index'])->name('index');
        });

        // Create subject
        Route::middleware(['permission:subjects.create'])->group(function () {
            Route::get('/create', [SubjectController::class, 'create'])->name('create');
            Route::post('/store', [SubjectController::class, 'store'])->name('store');
        });

        // Edit subject
        Route::middleware(['permission:subjects.edit'])->group(function () {
            Route::get('/edit/{subject}', [SubjectController::class, 'edit'])->name('edit');
            Route::put('/update/{subject}', [SubjectController::class, 'update'])->name('update');
        });

        // Delete subject
        Route::middleware(['permission:subjects.delete'])->group(function () {
            Route::put('/deletesubject/{subject}', [SubjectController::class, 'deactivate'])->name('delete');
        });
    });

    // Teacher Management Routes
    Route::prefix('teachers')->name('teachers.')->group(function () {
        // View teachers
        Route::middleware(['permission:teachers.view'])->group(function () {
            Route::get('/', [TeachersController::class, 'index'])->name('index');
        });

        // Create teacher
        Route::middleware(['permission:teachers.create'])->group(function () {
            Route::get('/create', [TeachersController::class, 'create'])->name('create');
            Route::post('/store', [TeachersController::class, 'store'])->name('store');
        });

        // Edit teacher
        Route::middleware(['permission:teachers.edit'])->group(function () {
            Route::get('/edit/{teacher}', [TeachersController::class, 'edit'])->name('edit');
            Route::put('/update/{teacher}', [TeachersController::class, 'update'])->name('update');
        });

        // Delete teacher
        Route::middleware(['permission:teachers.delete'])->group(function () {
            Route::put('/delete-teacher/{teacher}', [TeachersController::class, 'deactivate'])->name('delete');
        });
    });

    // Roles Management Routes
    Route::prefix('roles')->name('roles.')->group(function () {
        // View roles
        Route::middleware(['permission:roles.view'])->group(function () {
            Route::get('/', [RolesController::class, 'index'])->name('index');
            Route::get('/{role}/permissions/get', [RolesController::class, 'getRolePermissions'])->name('permissions.get');
        });

        // Create role
        Route::middleware(['permission:roles.create'])->group(function () {
            Route::get('/create', [RolesController::class, 'create'])->name('create');
            Route::post('/store', [RolesController::class, 'store'])->name('store');
        });

        // Edit role
        Route::middleware(['permission:roles.edit'])->group(function () {
            Route::get('/edit/{role}', [RolesController::class, 'edit'])->name('edit');
            Route::put('/update/{role}', [RolesController::class, 'update'])->name('update');
            Route::get('/assign-permission/{role}', [RolesController::class, 'assign_permission'])->name('assign_permissions');
            Route::post('/permissions/assign', [RolesController::class, 'assignPermissions'])->name('permissions.assign');
        });

        // Delete role
        Route::middleware(['permission:roles.delete'])->group(function () {
            Route::put('/delete-role/{role}', [RolesController::class, 'deactivate'])->name('delete');
        });
    });

    // Permissions Management Routes
    Route::prefix('permissions')->name('permissions.')->group(function () {
        // View permissions
        Route::middleware(['permission:permissions.view'])->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
        });

        // Create permission
        Route::middleware(['permission:permissions.create'])->group(function () {
            Route::get('/create', [PermissionController::class, 'create'])->name('create');
            Route::post('/store', [PermissionController::class, 'store'])->name('store');
        });

        // Edit permission
        Route::middleware(['permission:permissions.edit'])->group(function () {
            Route::get('/edit/{permission}', [PermissionController::class, 'edit'])->name('edit');
            Route::put('/update/{permission}', [PermissionController::class, 'update'])->name('update');
        });

        // Delete permission
        Route::middleware(['permission:permissions.delete'])->group(function () {
            Route::put('/delete-permission/{permission}', [PermissionController::class, 'deactivate'])->name('delete');
        });
    });

    // Users Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        // View users
        Route::middleware(['permission:users.view'])->group(function () {
            Route::get('/', [UserCheckController::class, 'index'])->name('index');
        });

        // Create user
        Route::middleware(['permission:users.create'])->group(function () {
            Route::get('/create', [UserCheckController::class, 'create'])->name('create');
            Route::post('/store', [UserCheckController::class, 'store'])->name('store');
        });

        // Edit user
        Route::middleware(['permission:users.edit'])->group(function () {
            Route::get('/edit/{user}', [UserCheckController::class, 'edit'])->name('edit');
            Route::put('/update/{user}', [UserCheckController::class, 'update'])->name('update');
        });

        // Delete user
        Route::middleware(['permission:users.delete'])->group(function () {
            Route::put('/delete-permission/{user}', [UserCheckController::class, 'deactivate'])->name('delete');
        });
    });

    Route::get('get-districts-by-state_id', [StudentsController::class, 'get_districts_by_state_id'])->name('get_districts_by_state_id');
    Route::get('get-municipalities-by-district_id', [StudentsController::class, 'get_municipalities_by_district_id'])->name('get_municipalities_by_district_id');
    Route::get('get-sections-by-class_id', [ClassSectionController::class, 'get_sections_by_class_id'])->name('get_sections_by_class_id');  
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';