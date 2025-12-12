<?php

namespace App\Repositories;

use App\Interface\SubjectInterface;
use App\Models\Subject;

class SubjectRepository implements SubjectInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAllSubjects()
    {
        return Subject::where('is_active', 1)->get();
    }
    public function createSubject(array $data): Subject
    {
        return Subject::create($data);
    }
    
    public function getSubjectById(int $id)
    {
        // Implementation here  

    }
    public function updateSubject(int $id, array $data)
    {
        // Implementation here  
        $subject = Subject::findOrFail($id);
        $subject->update($data);
        return $subject;
    }
    public function deleteSubject(int $id){
        // Implementation here  
        return Subject::where('id', $id)->update(['is_active' => 0]);
    }

    public function findSubjectByName(string $name):Subject|null{
        // Implementation here  
        return Subject::where('name', $name)->where('is_active',1)->first();
    }
}
