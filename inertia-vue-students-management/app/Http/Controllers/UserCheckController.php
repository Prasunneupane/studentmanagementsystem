<?php



namespace App\Http\Controllers;

use App\Models\Students;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use App\Contracts\StudentServiceInterface;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redirect;

class UserCheckController extends Controller
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
        Inertia::setRootView('app'); // Set the root view for Inertia
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

    public function checkUser()
    {
        try {
            $user = JWTAuth::user(); // Get authenticated user

            if ($user) {
                return response()->json([
                    'status' => 'success',
                    'user' => $user
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No authenticated user found'
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}