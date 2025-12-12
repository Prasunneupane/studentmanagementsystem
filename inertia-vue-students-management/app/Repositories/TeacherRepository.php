<?php

namespace App\Repositories;

use App\Interface\TeacherInterfacce;

class TeacherRepository implements TeacherInterfacce
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function getAllTeachers()
    {
        // Implementation here  
    }
    public function createTeacher(array $data)
    {
        // Implementation here  
    }
    public function getTeacherById(int $id)
    {
        // Implementation here
    }
    public function updateTeacher(int $id, array $data)
    {
        // Implementation here  
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
            "SHOW COLUMNS FROM {$this->getTable()} WHERE Field = '{$columnName}'"
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
