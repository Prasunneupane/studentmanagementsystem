<?php

namespace App\Services;


use App\Interface\SubjectInterface;
use Dotenv\Exception\ValidationException;

class SubjectService 
{
    private  $subjectInterface;

    /**
     * Create a new class instance.
     */
    public function __construct(SubjectInterface $subjectInterface)
    {
        $this->subjectInterface = $subjectInterface;
    }   

    public function createSubject(array $data)
    {
        if ($this->subjectInterface->findSubjectByName($data['name'])) {
            throw ValidationException::withMessages([
                'name' => 'Subject already exists.'
            ]);
        }
        // dd($this->subjectInterface->createSubject($data));
        return $this->subjectInterface->createSubject($data);
    }

    public function getAllSubjects()
    {
        return $this->subjectInterface->getAllSubjects();
    }

    public function deleteSubject($subjectId)
    {
        return $this->subjectInterface->deleteSubject($subjectId);
    }
    

    
}
