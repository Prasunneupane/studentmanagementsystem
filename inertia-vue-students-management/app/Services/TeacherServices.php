<?php

namespace App\Services;

use App\Interface\TeacherInterfacce;

class TeacherServices
{
    /**
     * Create a new class instance.
     */
    private $teacherService;
    public function __construct(TeacherInterfacce $teacherInterface)
    {
        //
        $this->teacherService = $teacherInterface;
    }

    public function getAllTeachers()
    {
        return $this->teacherService->getAllTeachers();
    }

    public function createTeacher(array $data)
    {
        return $this->teacherService->createTeacher($data);
    }
    public function getTeacherById(int $id)
    {
        return $this->teacherService->getTeacherById($id);
    }
    public function updateTeacher(int $id, array $data)
    {
        return $this->teacherService->updateTeacher($id, $data);
    }

    public function deleteTeacher(int $id)
    {
        return $this->teacherService->deleteTeacher($id);
    }

    public function findTeacherByName(string $name)
    {
        return $this->teacherService->findTeacherByName($name);
    }

    public function deactivateTeacher(int $id)
    {
        return $this->teacherService->deactivateTeacher($id);
    }
    public function activateTeacher(int $id)
    {
        return $this->teacherService->activateTeacher($id);
    }
    public function getEnumerationValues(string $columnName = 'status'): array
    {
        return $this->teacherService->getEnumerationValues($columnName);
    }
}
