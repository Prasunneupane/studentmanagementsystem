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
    // Existing dashboard route...
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Student Management routes
    Route::get('/students', [StudentsController::class, 'index'])->name('students.index');
    Route::get('/students/create', [StudentsController::class, 'create'])->name('students.create');
    Route::post('/students/store', [StudentsController::class, 'store'])->name('students.store');
    Route::get('/students/{student}', [StudentsController::class, 'show'])->name('students.show');
    Route::get('/students/{student}/edit', [StudentsController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentsController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentsController::class, 'destroy'])->name('students.destroy');
});

Route::get('/getListOfStates', [StateDistricMunController::class,'getAllStates'])->name('statelist');
Route::get('/getListOfDistrictByStateId', [StateDistricMunController::class,'getDistrictsByStateId'])->name('districtlist');
Route::get('/getListOfMunicipalitiesByDistrictId', [StateDistricMunController::class,'getMunicipalitiesByDistrictId'])->name('municipalitylist');
Route::get('/getClassesList', [ClassSectionController::class,'getAllClasses'])->name('classeslist');
Route::get('/getSectionList', [ClassSectionController::class,'getAllSection'])->name('sectionlist');
// Route::get('/registerStudent', [ClassSectionController::class,'registerStudent'])->name('registerStudent');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
