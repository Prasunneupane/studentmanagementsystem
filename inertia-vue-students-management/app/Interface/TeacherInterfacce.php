<?php

namespace App\Interface;

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
}
