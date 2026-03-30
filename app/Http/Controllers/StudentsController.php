<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Students;
use App\Transformers\CommonTransformers;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use App\Contracts\StudentServiceInterface;
use Log;
use Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redirect;

class StudentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $studentService;

    private $transformers;

    public function __construct(StudentServiceInterface $studentService,CommonTransformers $transformers)
    {
        $this->studentService = $studentService;
        $this->transformers = $transformers;
        // $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        //
        $fromDate = $request->input('from_date', now()->startOfYear()->format('Y-m-d'));
        $toDate = $request->input('to_date', now()->format('Y-m-d'));
        $students =  $this->studentService->getStudentsByDateRange($fromDate, $toDate);
        // dd($students);
        $studentList = $this->transformers->studentListTransform($students); 
        // dd($studentList);
        $classList =  $this->studentService->getClassList();
        $stateList =  $this->studentService->getStateList();
        return Inertia::render('students/StudentList',
        [
            'initialStudents'=>$studentList,
            'classes'=>$classList,
            'states'=>$stateList,
            'initialFromDate'=>$fromDate,
            'initialToDate'=>$toDate,
        ]
        ); // Adjust the view name as needed
    }

/**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        
        $classList =  $this->studentService->getClassList();
        $stateList =  $this->studentService->getStateList();
        $defaultStates = (array) $this->studentService->getDefaultStates();
        $districtList =  $this->studentService->getDistrictList($defaultStates['value']);
        $defaultDistricts = (array) $this->studentService->getDefaultDistricts();
        $municipalitiesList =  $this->studentService->getMunicipalityList($defaultDistricts['value']);
        $defaultMunicipalities = (array) $this->studentService->getDefaultMunicipalities();
        $defaultValues =  $this->studentService->getDefaultValue();
        // $defaultValues = $this->transformers->defaultValuesTransform($defaultValues);
        //dd($defaultValues);

        return Inertia::render('students/RegisterStudent',
        [
                    'classList'=>$classList,
                    'stateList'=>$stateList,
                    'districtList'=>$districtList,
                    'municipalitiesList'=>$municipalitiesList,
                    'defaultValues'=>$defaultValues,
                    'defaultStates'=>$defaultStates,
                    'defaultDistricts'=>$defaultDistricts,
                    'defaultMunicipalities'=>$defaultMunicipalities,
            ]
        ); 
    }

    public function get_districts_by_state_id(Request $request)
    {
        $state_id = $request->state_id;
        $districtList =  $this->studentService->getDistrictList($state_id);
        return response()->json($districtList, 200);
    }

    public function get_municipalities_by_district_id(Request $request)
    {
        $district_id = $request->district_id;
        $municipalitiesList =  $this->studentService->getMunicipalityList($district_id);
        return response()->json( $municipalitiesList, 200);
    }

   
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
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
    public function update_student_by_student_id(Request $request, $student_id)
    {
        // dd( JWTAuth::user()->id );
        try {
            $userId = JWTAuth::user()->id; // Get authenticated user ID
            $student= $this->studentService->updateStudentById($student_id, $request->all());

            // Return an Inertia redirect with a flash message
           return response()->json([
                'message' => 'Student Updated successfully.',
                'student_id' => $student->id,
                'success' => true,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors to Inertia
             Log::error('Error Updating student ID ' . $student_id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update student.',
                'details' => $e->getMessage(),
                'success' => false,
            ], 500);
        } catch (\Exception $e) {
            // Return error message to Inertia
            Log::error('Error updating student ID ' . $student_id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update student.',
                'details' => $e->getMessage(),
                'success' => false,

            ], 500);

        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $student = Students::findOrFail($id);
            $student->delete(); // Soft delete (if SoftDeletes is used)

            return response()->json([
                'message' => 'Student deleted successfully.',
                'student_id' => $student->id,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting student ID ' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to delete student.',
                'details' => $e->getMessage()
            ], 500);
        }
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
            // Log::info('Fetched students:', ['count' => count($students)]);
            return response()->json(['students' => $students], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching students by date range: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to fetch students'], 500);
        }

    }

    public function update(Request $request, Students $student)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:students,email,' . $student->id,
            'phone' => 'required|string|size:10',
            'age' => 'required|integer|min:1|max:100',
            'date_of_birth' => 'required|date', 
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'nullable|exists:tbl_section,id',
            'contact_number' => 'nullable|string',
            'joined_date' => 'required|date',
            'address' => 'nullable|string',
            'state_id' => 'required|exists:tbl_states,id',
            'district_id' => 'nullable|exists:tbl_districts,id',
            'municipality_id' => 'nullable|exists:tbl_municipalities,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($student->photo) {
                Storage::delete($student->photo);
            }
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }
        // dd($validated); 
        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'student' => [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'age' => $student->age,
                'date_of_birth' => $student->date_of_birth,
                'joined_date' => $student->joined_date,
                'address' => $student->address,
                'contact_number' => $student->contact_number,
                'photo_url' => $student->photo ? Storage::url($student->photo) : '/images/default-avatar.png',
                'class_id' => $student->class_id,
                'class_name' => $student->class?->name,
                'section_id' => $student->section_id,
                'section_name' => $student->section?->name,
                'state_id' => $student->state_id,
                'state_name' => $student->state?->name,
                'district_id' => $student->district_id,
                'district_name' => $student->district?->name,
                'municipality_id' => $student->municipality_id,
                'municipality_name' => $student->municipality?->name,
            ]
        ]);
    }

    public function loadByDateRange(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $students = Students::with(['class', 'section', 'state', 'district', 'municipality'])
            ->whereBetween('joined_date', [$request->from_date, $request->to_date])
            ->where('is_active', 1)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'middle_name' => $student->middle_name,
                    'last_name' => $student->last_name,
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'age' => $student->age,
                    'date_of_birth' => $student->date_of_birth,
                    'joined_date' => $student->joined_date,
                    'address' => $student->address,
                    'contact_number' => $student->contact_number,
                    'photo_url' => $student->photo ? Storage::url($student->photo) : '/images/default-avatar.png',
                    'class_id' => $student->class_id,
                    'class_name' => $student->class?->name,
                    'section_id' => $student->section_id,
                    'section_name' => $student->section?->name,
                    'state_id' => $student->state_id,
                    'state_name' => $student->state?->name,
                    'district_id' => $student->district_id,
                    'district_name' => $student->district?->name,
                    'municipality_id' => $student->municipality_id,
                    'municipality_name' => $student->municipality?->name,
                ];
            });

        return response()->json(['students' => $students]);
    }


     public function getGuardians(Students $student)
    {
        $guardians = $student->guardians()
            ->where('is_active', 1)
            ->get()
            ->map(function ($guardian) {
                return [
                    'id' => $guardian->id,
                    'name' => $guardian->name,
                    'relation' => $guardian->relation,
                    'phone' => $guardian->phone,
                    'email' => $guardian->email,
                    'occupation' => $guardian->occupation,
                    'address' => $guardian->address,
                    'is_primary_contact' => (bool)$guardian->is_primary_contact,
                ];
            });

        return response()->json(['guardians' => $guardians]);
    }

    /**
     * Store guardian
     */
    public function storeGuardian(Request $request, Students $student)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'is_primary_contact' => 'boolean',
        ]);

        $guardian = $student->guardians()->create($validated);

        return response()->json([
            'success' => true,
            'guardian' => [
                'id' => $guardian->id,
                'name' => $guardian->name,
                'relation' => $guardian->relation,
                'phone' => $guardian->phone,
                'email' => $guardian->email,
                'occupation' => $guardian->occupation,
                'address' => $guardian->address,
                'is_primary_contact' => (bool)$guardian->is_primary_contact,
            ]
        ]);
    }

    /**
     * Update guardian
     */
    public function updateGuardian(Request $request, Guardian $guardian)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'is_primary_contact' => 'boolean',
        ]);

        $guardian->update($validated);

        return response()->json([
            'success' => true,
            'guardian' => [
                'id' => $guardian->id,
                'name' => $guardian->name,
                'relation' => $guardian->relation,
                'phone' => $guardian->phone,
                'email' => $guardian->email,
                'occupation' => $guardian->occupation,
                'address' => $guardian->address,
                'is_primary_contact' => (bool)$guardian->is_primary_contact,
            ]
        ]);
    }

    /**
     * Delete guardian
     */
    public function destroyGuardian(Guardian $guardian)
    {
        $guardian->update(['is_active' => 0]);
        
        return response()->json([
            'success' => true,
            'message' => 'Guardian deleted successfully'
        ]);
    }




}
