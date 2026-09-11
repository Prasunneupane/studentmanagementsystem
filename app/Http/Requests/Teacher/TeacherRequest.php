<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teacher = $this->route('teacher');
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('tbl_teachers', 'email')->ignore($teacher?->id)],
            'phone' => ['required', 'string', 'max:10', Rule::unique('tbl_teachers', 'phone')->ignore($teacher?->id)],
            'address' => ['nullable', 'string', 'max:500'],
            'subject_specialization' => ['required', 'exists:tbl_subjects,id'],
            'joining_date' => ['required', 'date'],
            'leaving_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'dob' => ['required', 'date'],
            'is_active' => ['required', 'boolean'],
            'qualification' => ['nullable', 'string', 'max:255'],
        ];
    }
}
