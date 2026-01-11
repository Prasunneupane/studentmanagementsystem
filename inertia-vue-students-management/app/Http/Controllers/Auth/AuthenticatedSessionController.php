<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Log;
use Tymon\JWTAuth\Facades\JWTAuth;
class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        //
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();
        $token = JWTAuth::fromUser($user);
        
        // Store token in session (NOT flash)
        session(['jwt_token' => $token]);
        
        // Debug: Log that token was stored
        Log::info('JWT token stored in session', [
            'user_id' => $user->id,
            'token_length' => strlen($token),
            'session_id' => session()->getId(),
        ]);
        
        return redirect()->intended(route('dashboard'));   
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        // Invalidate the JWT token
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
            // Token already invalid or doesn't exist
        }
        
        // Remove from session
        session()->forget('jwt_token');
        
        Auth::guard('web')->logout();
        if ($userId) {
            Cache::forget("user_permissions_{$userId}");
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
