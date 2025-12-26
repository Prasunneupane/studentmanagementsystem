<?php



namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\Students;
use App\Models\User;
use App\Repositories\Validation;
use App\Services\UserServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use App\Contracts\StudentServiceInterface;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
class UserCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    
    private $dataValidation;
    private $userService;
    public function __construct(
        Validation $validation,
        UserServices $userService
    )
    {
        $this->dataValidation = $validation;
        $this->userService = $userService;
        // $this->middleware('auth:api');
    }

    public function index()
    {
        
        $userList = $this->userService->getAllUsers();
        return Inertia::render('user/UserList',[
            'users' => $userList
        ]); 
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $allRoles = $this->userService->getAllRoles();
        $allRoles = $this->userService->transformRoles($allRoles);
        // dd($allRoles);
        return Inertia::render('user/RegisterUser',[
            'roles' => $allRoles,
            // 'user'=>[],
          ]); 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request):RedirectResponse
    {
        
        $data =$request->validate($this->dataValidation->userValidationRules($request));
        $this->userService->createUsers($data);
        return to_route('users.index',)->with('success', 'User created successfully.');
       
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $allRoles = $this->userService->getAllRoles();
        $allRoles = $this->userService->transformRoles($allRoles);
        $userData = $this->userService->getUserWithRoles($user);
        return Inertia::render('user/RegisterUser', [
            'roles' => $allRoles,
            'user'  => $userData,
        ]);
    }


    public function update(Request $request,User $user):RedirectResponse
    {
        $data =  $request->validate(
            $this->dataValidation->userUpdateValidationRules($request, $user)
        );
        $this->userService->updateUsers($user->id, $data);
        // dd($request->all());
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'created_by'=>JWTAuth::user()->id,
        ];
        // if($request->password){
        //     $data['password'] = Hash::make($request->password);
        // }
        
        return to_route('users.index',)->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deactivate(User $user):RedirectResponse
    {
        // dd($user);
        // dd($user->id);
        $this->userService->deactivateUser($user->id);
        return to_route('users.index')->with('success', 'User deactivated successfully.');
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