<?php

namespace App\Http\Requests\ClassSubject;

use Illuminate\Foundation\Http\FormRequest;

class ClassSubjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'exists:tbl_classes,id'],
            'section_id' => ['required', 'exists:tbl_section,id'],
            'subject_id' => ['required', 'exists:tbl_subjects,id'],
            'teacher_id' => ['nullable', 'exists:tbl_teachers,id'],
            'academic_year_id' => ['required', 'exists:tbl_academic_years,id'],
            'is_optional' => ['boolean'],
            'periods_per_week' => ['required', 'integer', 'min:0', 'max:50'],
            'max_marks' => ['required', 'numeric', 'min:0', 'max:1000'],
            'pass_marks' => ['required', 'numeric', 'min:0', 'max:1000'],
        ];
    }
}
