<?php
namespace App\Repositories;

use App\Models\Guardian;

class GuardianRepository
{
    /**
     * Create a new guardian
     */
    public function create(array $data): Guardian
    {
        // dd($data);
        return Guardian::create($data);
    }

    /**
     * Get guardians by student ID
     */
    public function getByStudentId(int $studentId)
    {
        return Guardian::where('student_id', $studentId)->get();
    }

    /**
     * Update guardian
     */
    public function update(int $id, array $data): bool
    {
        $guardian = Guardian::findOrFail($id);
        return $guardian->update($data);
    }

    /**
     * Delete guardian
     */
    public function delete(int $id): bool
    {
        $guardian = Guardian::findOrFail($id);
        return $guardian->delete();
    }

    /**
     * Get primary contact guardian for student
     */
    public function getPrimaryContact(int $studentId)
    {
        return Guardian::where('student_id', $studentId)
            ->where('is_primary_contact', true)
            ->first();
    }
}