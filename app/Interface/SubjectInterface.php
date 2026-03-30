<?php

namespace App\Interface;

interface SubjectInterface
{
    public function getAllSubjects();
    public function createSubject(array $data);
    public function getSubjectById(int $id);
    public function updateSubject(int $id, array $data);
    public function deleteSubject(int $id);

    public function findSubjectByName(string $name);

}
