<?php

namespace App\Http\Controllers;

use App\Models\Teachers;
use App\Repositories\validation;
use App\Services\TeacherServices;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeachersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $teacherServices;
    private $teacherValidation;
    public function __construct(
        TeacherServices $teacherServices,
        Validation $validation 
        )
    {
        $this->teacherServices = $teacherServices;
        $this->teacherValidation = $validation;
    }
    public function index()
    {
        $teacherList = $this->teacherServices->getAllTeachers();
        return Inertia::render('teachers/TeacherList', [
            'teachers' => $teacherList
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $statusOptions = $this->teacherServices->getEnumerationValues('status');
        // dd($statusOptions);
        return Inertia::render('teachers/AddTeacher', [
            'status' => $statusOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
         $validatedData = $request->validate(
            $this->teacherValidation->teacherValidationRules($this->teacherServices)
        );
        // dd($validatedData);
        $createTeacher = $this->teacherServices->createTeacher($validatedData,$request->all());
        return redirect()->route('teachers.index')->with('success', 'Teacher created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Teachers $teachers)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Teachers $teacher)
    {
        // dd($teacher->toArray());
        return Inertia::render('teachers/AddTeacher', [
            'teacher' => $teacher->toArray(),
            'status' => $this->teacherServices->getEnumerationValues('status'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Teachers $teacher)
    {
        $validatedData = $request->validate(
            $this->teacherValidation->teacherUpdateValidationRules($this->teacherServices, $teacher->id)
        );
        // dd($validatedData);
        $updateTeacher = $this->teacherServices->updateTeacher($teacher->id, $validatedData, $request->all());
        return redirect()->route('teachers.index')->with('success', 'Teacher updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teachers $teachers)
    {
        //
    }
}
