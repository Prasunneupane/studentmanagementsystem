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
}
