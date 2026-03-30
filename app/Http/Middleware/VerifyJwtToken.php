<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class VerifyJwtToken
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Get token from Authorization header
            $token = $request->bearerToken();

            // Log token for debugging
            Log::info('JWT Token from header:', ['token' => $token]);

            // If no token in header, try session (for web routes)
            if (!$token) {
                $token = session('jwt_token');
                Log::info('JWT Token from session:', ['token' => $token]);
            }

            if (!$token) {
                Log::warning('No JWT token provided');
                return $request->expectsJson() || $request->inertia()
                    ? response()->json(['error' => 'Unauthorized: No token provided'], 401)
                    : redirect()->route('home'); // Redirect to / for login
            }

            // Set and validate token
            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            if (!$user) {
                Log::warning('Invalid JWT token');
                return $request->expectsJson() || $request->inertia()
                    ? response()->json(['error' => 'Unauthorized: Invalid token'], 401)
                    : redirect()->route('home');
            }

            // Optionally set Auth::user() for the request
            // Auth::login($user);

        } catch (JWTException $e) {
            Log::error('JWT Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $request->expectsJson() || $request->inertia()
                ? response()->json(['error' => 'Unauthorized: ' . $e->getMessage()], 401)
                : redirect()->route('home');
        }

        return $next($request);
    }
}