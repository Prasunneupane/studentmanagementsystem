<?php

namespace App\Interface;

interface UserInterface
{
    public function getAllUsers();
    public function createUsers(array $data);
    public function updateUsers(int $userId, array $data);
    public function deactivateUser(int $userId);    
    public function getUserById(int $userId);
    public function activateUser(int $userId);
    public function getAllRoles();
    public function getTeacherById(int $teacherId): array;
    public function CheckTeacherUserNameExist($request, $teacherName): bool;
    public function checkIfUserExist($teacher): bool;
    public function createTeacherUser($teacher, $data);
}
