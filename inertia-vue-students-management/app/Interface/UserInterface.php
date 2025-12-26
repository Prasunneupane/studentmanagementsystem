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
}
