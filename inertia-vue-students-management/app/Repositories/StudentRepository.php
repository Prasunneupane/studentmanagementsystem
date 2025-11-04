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

    public function getStudentsByDateRange(string $fromDate, string $toDate): array
    {
        $students = Students::whereRaw('DATE(created_at) BETWEEN ? AND ?', [$fromDate, $toDate])
        ->with([
            'class:id,name',
            'section:id,name',
        ])
        ->get();

        return $students->toArray();
    }
}