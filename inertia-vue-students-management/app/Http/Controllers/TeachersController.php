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
        dd($statusOptions);
        return Inertia::render('teachers/AddTeacher', [
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
