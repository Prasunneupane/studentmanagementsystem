<?php

namespace App\Repositories;

use App\Contracts\StudentRepositoryInterface;
use App\Models\Students;

use Illuminate\Support\Facades\Storage;

class StudentRepository implements StudentRepositoryInterface
{
    public function create(array $data): Students
    {
        // Handle photo upload if present
        if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
            $data['photo'] = $data['photo']->store('photos', 'public');
        }
        // dd($data);
        return Students::create($data);
    }

    public function getStudentsByDateRange(string $fromDate, string $toDate)
    {
        $students = Students::whereRaw('DATE(created_at) BETWEEN ? AND ?', [$fromDate, $toDate])
        ->with([
            'class:id,name',
            'section:id,name',
            'state:id,name',
            'district:id,name',
            'municipality:id,name',
        ])
        ->get();

        return $students;
    }

    public function updateStudentById(int $studentId, array $data): Students
    {
        $student = Students::findOrFail($studentId);

        // Handle photo upload if present
        if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
            // Delete old photo if exists
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $data['photo'] = $data['photo']->store('photos', 'public');
        }

        $student->update($data);

        return $student;
    }
}