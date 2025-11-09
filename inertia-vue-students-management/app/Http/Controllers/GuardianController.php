<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\Students;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use App\Contracts\StudentServiceInterface;
use Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redirect;

class GuardianController extends Controller
{
    public function getGuardiansByStudentId(Request $request,$studentId): JsonResponse
    {
        try {
            $studentId = $studentId ?? $request->query('student_id');

            if (!$studentId) {
                return response()->json(['error' => 'Student ID is required'], 400);
            }

            $guardians = Guardian::where('student_id', $studentId)->get();

            return response()->json(['guardians' => $guardians], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching guardians: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching guardians'], 500);
        }
    }
}