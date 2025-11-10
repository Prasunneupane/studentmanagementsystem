<?php

use App\Http\Controllers\ClassSectionController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\StateDistricMunController;
use App\Http\Controllers\StudentsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['jwt.verify'])->group(function () {
    Route::get('/getStudentListByDateRange', [StudentsController::class, 'student_list_by_date_range'])->name('student_list_by_date_range');
    Route::delete('/deleteStudent/{id}', [StudentsController::class, 'destroy']);
    Route::get('/getListOfStates', [StateDistricMunController::class,'getAllStates'])->name('statelist');
    Route::get('/getListOfDistrictByStateId', [StateDistricMunController::class,'getDistrictsByStateId'])->name('districtlist');
    Route::get('/getListOfMunicipalitiesByDistrictId', [StateDistricMunController::class,'getMunicipalitiesByDistrictId'])->name('municipalitylist');
    Route::get('/getClassesList', [ClassSectionController::class,'getAllClasses'])->name('classeslist');
    Route::get('/getSectionList', [ClassSectionController::class,'getAllSection'])->name('sectionlist');
    Route::get('/guardian/getGuardiansByStudentId/{student_id}', [GuardianController::class,'getGuardiansByStudentId'])->name('getGuardiansByStudentId');
});
