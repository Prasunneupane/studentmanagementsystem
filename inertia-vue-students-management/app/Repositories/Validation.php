<?php

namespace App\Repositories;

use App\Models\User;

class Validation
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        
    }

    public function teacherValidationRules($teacherServices)
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tbl_teachers,email',
            'phone' => 'required|string|max:10|unique:tbl_teachers,phone',
            'address' => 'nullable|string|max:500',
            'subject_specialization' => 'required|string|max:255',
            'joining_date' => 'required|date',
            'leaving_date' => 'nullable|date|after_or_equal:joining_date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'dob' => 'required|date',
            'is_active' => 'required|boolean',
            'qualification' => 'nullable|string|max:255',
            // 'status' => 'required|array|in:'.implode(',', $teacherServices->getEnumerationValues('status')),
        ];

    }

    public function teacherUpdateValidationRules($teacherServices, $teacherId)
    {
        // dd($teacherId);
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tbl_teachers,email,'.$teacherId,
            'phone' => 'required|string|max:10|unique:tbl_teachers,phone,'.$teacherId,
            'address' => 'nullable|string|max:500',
            'subject_specialization' => 'required|string|max:255',
            'joining_date' => 'required|date',
            'leaving_date' => 'nullable|date|after_or_equal:joining_date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'dob' => 'required|date',
            'is_active' => 'required|boolean',
            'qualification' => 'nullable|string|max:255',
            // 'status' => 'required|array|in:'.implode(',', $teacherServices->getEnumerationValues('status')),
        ];

    }

    public function roleValidationRules($request)
    {
        return [
            'name' => 'required|string|max:255|unique:tbl_roles,name',
            'description' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ];
    }

    public function roleUpdateValidationRules($request, $roleId)
    {
        return [
            'name' => 'required|string|max:255|unique:tbl_roles,name,'.$roleId,
            'description' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ];
    }

    public function permissionValidationRules($request)
    {
        return [
            'name' => 'required|string|max:255|unique:tbl_permissions,name',
            'description' => 'nullable|string|max:500',
            'module' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ];
    }

    public function permissionUpdateValidationRules($request, $permissionId)
    {
        return [
            'name' => 'required|string|max:255|unique:tbl_permissions,name,'.$permissionId,
            'description' => 'nullable|string|max:500',
            'module' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ];
    }

    public function userValidationRules($request)
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|exists:tbl_roles,id',
        ];
    }

    public function userUpdateValidationRules($request, $user)
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class.',email,'.$user->id,
            // 'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_active' => 'required|boolean',
            'roles' => 'required|exists:tbl_roles,id',
        ];
    }

    public function classSubjectValidationRules($request)
    {
        return [
           
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'required|exists:tbl_section,id',
            'subject_id' => 'required|exists:tbl_subjects,id',
            'teacher_id' => 'nullable|exists:tbl_teachers,id',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'is_optional' => 'boolean',
            'periods_per_week' => 'required|integer|min:0|max:50',
            'max_marks' => 'required|numeric|min:0|max:1000',
            'pass_marks' => 'required|numeric|min:0|max:1000',
        
        ];
    }

    public function classSubjectUpdateValidationRules($request, $classSubjectId)
    {
        return [
           
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'required|exists:tbl_section,id',
            'subject_id' => 'required|exists:tbl_subjects,id',
            'teacher_id' => 'nullable|exists:tbl_teachers,id',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'is_optional' => 'boolean',
            'periods_per_week' => 'required|integer|min:0|max:50',
            'max_marks' => 'required|numeric|min:0|max:1000',
            'pass_marks' => 'required|numeric|min:0|max:1000',
        
        ];
    }

    public function classTeacherValidationRules($request,$id = null)
    {
          return [
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'required|exists:tbl_section,id',
            'teacher_id' => 'required|exists:tbl_teachers,id',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'is_class_teacher' => 'boolean',
            'is_active' => 'boolean',
        ];
        
    }

    public function classTeacherUpdateValidationRules($request, $classTeacherId)
    {
          return [
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'required|exists:tbl_section,id',
            'teacher_id' => 'required|exists:tbl_teachers,id',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'is_class_teacher' => 'boolean',
            'is_active' => 'boolean',
        ];
    } 
    
    public function termValidationRules($request,)
    {
        return [
            'name' => 'required|string|max:255',
            'term_number' => 'required|',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }

    public function termUpdateValidationRules($request, $termId)
    {
        return [
            'name' => 'required|string|max:255',
            'term_number' => 'required|integer|min:1|max:10',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }

    public function validateExam($request)
    {
        return $request->validate([
          'name'             => 'required|string|max:150',
            'exam_type'        => 'required|in:unit_test,midterm,final,semester,annual',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'term_id'          => 'nullable|exists:tbl_terms,id',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'weightage'        => 'nullable|numeric|min:0|max:100',
            'is_published'     => 'boolean',
              //     // exam_classes array validation
            'exam_classes'                => 'required|array|min:1',
            'exam_classes.*.class_id'     => 'required|exists:tbl_classes,id',
            'exam_classes.*.section_id'   => 'nullable|exists:tbl_section,id',
        ]);

        //  $data = $request->validate([
           

      
        // ]);

        // DB::transaction(function () use ($data, &$exam) {
        //     $exam = Exam::create([
        //         'name'             => $data['name'],
        //         'exam_type'        => $data['exam_type'],
        //         'academic_year_id' => $data['academic_year_id'],
        //         'term_id'          => $data['term_id'] ?? null,
        //         'start_date'       => $data['start_date'],
        //         'end_date'         => $data['end_date'],
        //         'weightage'        => $data['weightage'] ?? 100,
        //         'is_published'     => $data['is_published'] ?? false,
        //     ]);

        //     // Bulk insert exam_classes
        //     $insertRows = collect($data['exam_classes'])->map(fn($ec) => [
        //         'exam_id'    => $exam->id,
        //         'class_id'   => $ec['class_id'],
        //         'section_id' => $ec['section_id'] ?? null,
        //     ])->toArray();

        //     ExamClass::insert($insertRows);
        // });
    }
}
