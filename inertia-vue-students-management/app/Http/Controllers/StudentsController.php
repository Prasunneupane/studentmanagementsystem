<?php

namespace App\Http\Controllers;

use App\Models\Students;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use App\Contracts\StudentServiceInterface;
use Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redirect;

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
         return Inertia::render('students/StudentList'); // Adjust the view name as needed
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
    public function store(Request $request)
{
    try {
        $userId = JWTAuth::user()->id; // Get authenticated user ID
        $this->studentService->createStudent($request->all(), $userId);

        // Return an Inertia redirect with a flash message
        return Redirect::route('students.student_list') // Replace 'dashboard' with your target route
            ->with('success', 'Student registered successfully');
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Return validation errors to Inertia
        return Redirect::back()->withErrors($e->errors())->withInput();
    } catch (\Exception $e) {
        // Return error message to Inertia
        return Redirect::back()->with('error', $e->getMessage());
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

    public function student_list_by_date_range(Request $request)
    {
        // dd('here');
        $startDate = $request->input('fromDate');
        $endDate = $request->input('toDate');
       

        try {
            $students = $this->studentService->getStudentsByDateRange($startDate, $endDate);
            Log::info('Fetched students:', ['count' => count($students)]);
            return response()->json(['students' => $students], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching students by date range: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to fetch students'], 500);
        }
        
    }



}
