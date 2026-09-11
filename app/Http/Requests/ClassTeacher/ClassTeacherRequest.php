<?php

namespace App\Http\Requests\ClassTeacher;

use Illuminate\Foundation\Http\FormRequest;

class ClassTeacherRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'exists:tbl_classes,id'],
            'section_id' => ['required', 'exists:tbl_section,id'],
            'teacher_id' => ['required', 'exists:tbl_teachers,id'],
            'academic_year_id' => ['required', 'exists:tbl_academic_years,id'],
            'is_class_teacher' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
