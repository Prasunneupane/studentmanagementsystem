<?php

namespace App\Http\Controllers;

use App\Models\Students;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use App\Contracts\StudentServiceInterface;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $studentService;

    public function __construct(StudentServiceInterface $studentService)
    {
        $this->studentService = $studentService;
        // $this->middleware('auth:api');
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
          return Inertia::render('students/RegisterStudent'); // Adjust the view name as needed
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {

        // dd($request->all());
        try {
            $userId = JWTAuth::user()->id;
            // dd($userId);
            $this->studentService->createStudent($request->all(), $userId);
            return response()->json(['message' => 'Student created successfully'], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Students $students)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Students $students)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Students $students)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Students $students)
    {
        //
    }

    public function registerStudent(Students $students)
    {
       $students->attach($students->student_id);
       return redirect()->route('students.index')->with('success', 'Student registered successfully.');
    }



}
