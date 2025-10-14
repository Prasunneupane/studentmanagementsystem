<?php

use App\Http\Controllers\ClassesController;
use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\StateDistricMunController;
use App\Http\Controllers\StudentsController;
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
});

Route::middleware(['jwt.verify'])->group(function () {
        Log::info('Inside jwt.verify middleware group');
    //   Route::prefix('students')->name('students.')->group(function () {
         Log::info('inside route group');
        Route::get('/getStudentListByDateRange', [StudentsController::class, 'student_list_by_date_range'])->name('student_list_by_date_range');
    // });
});

Route::get('/getListOfStates', [StateDistricMunController::class,'getAllStates'])->name('statelist');
Route::get('/getListOfDistrictByStateId', [StateDistricMunController::class,'getDistrictsByStateId'])->name('districtlist');
Route::get('/getListOfMunicipalitiesByDistrictId', [StateDistricMunController::class,'getMunicipalitiesByDistrictId'])->name('municipalitylist');
Route::get('/getClassesList', [ClassSectionController::class,'getAllClasses'])->name('classeslist');
Route::get('/getSectionList', [ClassSectionController::class,'getAllSection'])->name('sectionlist');
// Route::get('/registerStudent', [ClassSectionController::class,'registerStudent'])->name('registerStudent');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
