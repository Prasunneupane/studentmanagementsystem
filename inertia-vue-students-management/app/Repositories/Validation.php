<?php

namespace App\Repositories;

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
}
