<?php

namespace App\Services;

use App\Contracts\StudentRepositoryInterface;
use App\Contracts\StudentServiceInterface;
use App\Models\Students;
use Illuminate\Validation\ValidationException;

class StudentService implements StudentServiceInterface
{
    protected $studentRepository;

    public function __construct(StudentRepositoryInterface $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    public function createStudent(array $data, int $userId): Students
    {
        // Validate required fields
        $requiredFields = [
            'fName' => 'First name is required',
            'lName' => 'Last name is required',
            'phone' => 'Phone number is required',
            'age' => 'Age is required',
            'dateOfBirth' => 'Date of birth is required',
            'classId' => 'Class is required',
            'fatherName' => 'Father name is required',
            'guardianName' => 'Guardian name is required',
            'joinedDate' => 'Joined date is required',
            'stateId' => 'State is required',
        ];

        foreach ($requiredFields as $field => $message) {
            if (empty($data[$field])) {
                throw ValidationException::withMessages([$field => $message]);
            }
        }

        // Validate age
        $age = (int) $data['age'];
        if ($age < 1 || $age > 100) {
            throw ValidationException::withMessages(['age' => 'Age must be between 1 and 100']);
        }

        // Validate phone
        if (!preg_match('/^\d{10}$/', $data['phone'])) {
            throw ValidationException::withMessages(['phone' => 'Phone must be a valid 10-digit number']);
        }

        // Validate date formats
        $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($dateRegex, $data['dateOfBirth']) || !preg_match($dateRegex, $data['joinedDate'])) {
            throw ValidationException::withMessages(['date' => 'Invalid date format']);
        }

        // Prepare data for repository
        $studentData = [
            'first_name' => $data['fName'],
            'middle_name' => $data['mName'] ?? null,
            'last_name' => $data['lName'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'age' => $age,
            'date_of_birth' => $data['dateOfBirth'],
            'class_id' => $data['classId'],
            'section_id' => $data['sectionId'] ?? null,
            'mother_name' => $data['motherName'] ?? null,
            'father_name' => $data['fatherName'],
            'guardian_name' => $data['guardianName'],
            'contact_number' => $data['contactNumber'] ?? null,
            'photo' => $data['photo'] ?? null,
            'joined_date' => $data['joinedDate'],
            'address' => $data['address'] ?? null,
            'state_id' => $data['stateId'],
            'district_id' => $data['districtId'] ?? null,
            'municipality_id' => $data['municipalityId'] ?? null,
            'created_by' => $userId,
        ];
       // dd($studentData);
       return  $this->studentRepository->create($studentData);
    }

    public function getStudentsByDateRange(string $fromDate, string $toDate): array
    {
        // Validate date formats
        $dateRegex = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($dateRegex, $fromDate) || !preg_match($dateRegex, $toDate)) {
            throw ValidationException::withMessages(['date' => 'Invalid date format']);
        }
        
        return $this->studentRepository->getStudentsByDateRange($fromDate, $toDate);
    }
}