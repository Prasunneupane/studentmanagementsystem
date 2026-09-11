<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $student = $this->route('student');
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('students', 'email')->ignore($student?->id)],
            'phone' => ['required', 'string', 'size:10'],
            'age' => ['required', 'integer', 'min:1', 'max:100'],
            'date_of_birth' => ['required', 'date'],
            'class_id' => ['required', 'exists:tbl_classes,id'],
            'section_id' => ['nullable', 'exists:tbl_section,id'],
            'contact_number' => ['nullable', 'string'],
            'joined_date' => ['required', 'date'],
            'address' => ['nullable', 'string'],
            'state_id' => ['required', 'exists:tbl_states,id'],
            'district_id' => ['nullable', 'exists:tbl_districts,id'],
            'municipality_id' => ['nullable', 'exists:tbl_municipalities,id'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
