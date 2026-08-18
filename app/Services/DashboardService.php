<?php

namespace App\Services;

use App\Interface\DashboardInterface;
use App\Models\Classes;
use App\Models\Students;
use App\Models\Teachers;
use Illuminate\Support\Facades\DB;

class DashboardService implements DashboardInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getDashboardData(): array
    {
        $students = Students::count('*');
        $teachers = Teachers::count('*');
        $parents = [];
        $staff = [];
        $studentByClass = Classes::withCount('students')->get()->map(function ($class) {
            return [
                'name' => $class->name,
                'count' => $class->students_count,
            ];
        });
        $thisMonthStudentCount = Students::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        return [
            'students' => $students,
            'teachers' => $teachers,
            'parents' => $parents,
            'staff' => $staff,
            'studentByClass' => $studentByClass,
            'thisMonthStudentCount' => $thisMonthStudentCount,
        ];
    }
}
