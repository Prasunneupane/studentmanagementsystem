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

    public function classTeacherValidationRules($request)
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
}
