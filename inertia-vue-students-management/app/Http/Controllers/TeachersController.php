<?php

namespace App\Http\Controllers;

use App\Models\Teachers;
use App\Services\TeacherServices;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeachersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $teacherServices;
    public function __construct(TeacherServices $teacherServices)
    {
        $this->teacherServices = $teacherServices;
    }
    public function index()
    {
        //
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
         $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tbl_teachers,email',
            'phone' => 'required|string|max:10|unique:tbl_teachers,phone',
            'address' => 'nullable|string|max:500',
            'subject_specializtion' => 'required|string|max:255',
            'joining_date' => 'required|date',
            'leaving_date' => 'nullable|date|after_or_equal:joining_date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'dob' => 'required|date',
            'is_active' => 'required|boolean',
            // 'status' => 'required|array|in:'.implode(',', $this->teacherServices->getEnumerationValues('status')),

        ]);
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
    public function edit(Teachers $teachers)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Teachers $teachers)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teachers $teachers)
    {
        //
    }
}
