<?php

namespace App\Interface;

use phpDocumentor\Reflection\Types\Boolean;

interface TeacherInterfacce
{
    public function getAllTeachers();
    public function createTeacher(array $data);
    public function getTeacherById(int $id);
    public function updateTeacher(int $id, array $data);
    public function deleteTeacher(int $id);

    public function findTeacherByName(string $name);

    public function deactivateTeacher(int $id);
    public function activateTeacher(int $id);

    public function getEnumerationValues(string $columnName): array;

    public function getAllSubject():array;
    public function getSubjectNameById(int $id): ?string;
    
}
