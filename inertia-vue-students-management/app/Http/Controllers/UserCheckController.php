<?php



namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\Students;
use App\Models\User;
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

    

    public function __construct()
    {
        
        // $this->middleware('auth:api');
    }

    public function index()
    {
        $userList = User::where('is_active',1)->with('roles')->get()->map(function($user){
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roleName' => $user->roles->pluck('name')->join(', '),
                'is_active' => $user->is_active,
                // 'phone' => $user->phone,
                // 'address' => $user->address,
                // 'subject_specialization' => $user->subject_specialization,
                // 'status' => $user->status,
                // 'qualification' => $user->qualification,
            ];
        });
        return Inertia::render('user/UserList',[
            'users' => $userList
        ]); 
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $allRoles = Roles::where('is_active',1)->pluck('id','name');
        
        $mappedRoles = $allRoles->map(function($item, $key){
            return ['value'=>$item,'label'=>$key];
        })->values();
        $allRoles = $mappedRoles->prepend([
            'value' => '',
            'label' => 'Select Role',
        ])->values();
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => 'required|exists:tbl_roles,id',
        ]);
        // dd($request->all());
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active'=>1,
            'created_by'=>JWTAuth::user()->id,
        ]);
        $user->roles()->sync([
            $request->roles => ['created_by' => JWTAuth::user()->id]
        ]);
        return to_route('users.index',)->with('success', 'User created successfully.');
       
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $allRoles = Roles::where('is_active', 1)->pluck('id', 'name');

        $mappedRoles = $allRoles->map(function ($item, $key) {
            return ['value' => $item, 'label' => $key];
        })->values();

        $allRoles = $mappedRoles->prepend([
            'value' => '',
            'label' => 'Select Role',
        ])->values();

        // Get user with roles
        $userWithRoles = $user->load('roles');

        // Add roleId array to user
        $userData = $userWithRoles->toArray();
       $userData['roleId'] = $user->roles->pluck('id')->first();
        return Inertia::render('user/RegisterUser', [
            'roles' => $allRoles,
            'user'  => $userData,
        ]);
    }


    public function update(Request $request,User $user):RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class.',email,'.$user->id,
            // 'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'roles' => 'required|exists:tbl_roles,id',
        ]);
        // dd($request->all());
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'created_by'=>JWTAuth::user()->id,
        ];
        // if($request->password){
        //     $data['password'] = Hash::make($request->password);
        // }
        $user->update($data);
        $user->roles()->sync([
            $request->roles => ['created_by' => JWTAuth::user()->id]
        ]);
        return to_route('users.index',)->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deactivate(User $user):RedirectResponse
    {
        // dd($user);
        $user->is_active = 0;
        $user->update();
        $user->roles()->detach();
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