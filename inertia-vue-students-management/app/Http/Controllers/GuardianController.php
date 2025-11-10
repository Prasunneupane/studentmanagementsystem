<?php

namespace App\Http\Controllers;

use App\Interface\GuardianInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Log;


class GuardianController extends Controller
{
    private $guardianService;
    public function __construct(GuardianInterface $guardianService)
    {
        $this->guardianService = $guardianService;
    }
    public function getGuardiansByStudentId(Request $request,$studentId): JsonResponse
    {
        try {
            $studentId = $studentId ?? $request->query('student_id');

            if (!$studentId) {
                return response()->json(['error' => 'Student ID is required'], 400);
            }

            $guardians = $this->guardianService->getGuardiansByStudentId((int)$studentId);

            return response()->json(['guardians' => $guardians], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching guardians: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching guardians'], 500);
        }
    }
}