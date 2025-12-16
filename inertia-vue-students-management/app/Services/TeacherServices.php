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

    public function createTeacher(array $data,$request)
    {
        // dd($request['status']['value']);
       
        $createData = [
            ...$data,
            'status' => $request['status']['value'] ?? null,
            'created_by' => auth()->id(),
            'date_of_birth' => $request['dob'] ?? null,
           
        ];
        // dd($createData);
        return $this->teacherService->createTeacher($createData);
    }
    public function getTeacherById(int $id)
    {
        return $this->teacherService->getTeacherById($id);
    }
    public function updateTeacher(int $id, array $data,$request)
    {
         if($request['status']['value'] !='on_leave' || $request['status']['value'] !='active'){
            $request['leaving_date']=date('Y-m-d');
            $request['is_active']=false;
        }   
        $updateData = [
            ...$data,
            'status' => $request['status']['value'] ?? null,
            'date_of_birth' => $request['dob'] ?? null,
            'leaving_date' => $request['leaving_date'] ?? null,
            'is_active' => $request['is_active'] ?? true,
        ];
        // dd($updateData);
        return $this->teacherService->updateTeacher($id, $updateData);
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
