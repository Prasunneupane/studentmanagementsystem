<?php

namespace App\Repositories;

use App\Interface\TeacherInterfacce;
use App\Models\Teachers;
use Storage;

class TeacherRepository implements TeacherInterfacce
{
    /**
     * Create a new class instance.
     */
    private $model;
    public function __construct(Teachers $teachers)
    {
        $this->model = $teachers;
    }
    public function getAllTeachers()
    {
        // Implementation here  
        return $this->model->where('is_active', true)->get();
    }
    public function createTeacher(array $data)
    {
        // Implementation here 
        if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
            $data['photo'] = $data['photo']->store('teachers', 'public');
        }
        // dd($data);
        return $this->model->create($data);
    }
    public function getTeacherById(int $id)
    {
        // Implementation here
    }
    public function updateTeacher(int $id, array $data)
    {
        $teacher = $this->model->findOrFail($id);
         // Handle photo upload if present
        if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
            // Delete old photo if exists
            if ($teacher->photo) {
                Storage::disk('public')->delete($teacher->photo);
            }
            $data['photo'] = $data['photo']->store('photos', 'public');
        }
        $teacher->update($data);
    }
    public function deleteTeacher(int $id)
    {
        // Implementation here
    }
    public function findTeacherByName(string $name)
    {
        // Implementation here  
    }
    public function deactivateTeacher(int $id)
    {
        // Implementation here  
    }
    public function activateTeacher(int $id)
    {
        // Implementation here
    }
    public function getEnumerationValues(string $columnName = 'status'): array
{
    // 1. Get raw column type from database
        $type = \DB::select(
            "SHOW COLUMNS FROM {$this->model->getTable()} WHERE Field = '{$columnName}'"
        )[0]->Type;

        // 2. Extract only the enum values from the column definition
        preg_match('/^enum\((.*)\)$/', $type, $matches);

        $values = str_getcsv($matches[1], ',', "'");

        // 3. Convert each enum value into { value, label }
        return array_map(function ($value) {
            $label = ucwords(str_replace('_', ' ', $value));

            return [
                'value' => $value,
                'label' => $label,
            ];
        }, $values);
    }
}
