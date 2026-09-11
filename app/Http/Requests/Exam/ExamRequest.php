<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $exam = $this->route('exam');
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('tbl_exams', 'name')->ignore($exam?->id)],
            'exam_type' => ['required', 'in:unit_test,midterm,final,semester,annual'],
            'academic_year_id' => ['required', 'exists:tbl_academic_years,id'],
            'term_id' => ['nullable', 'exists:tbl_terms,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'weightage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_published' => ['boolean'],
            'exam_classes' => ['required', 'array', 'min:1'],
            'exam_classes.*.class_id' => ['required', 'exists:tbl_classes,id'],
            'exam_classes.*.section_id' => ['nullable', 'exists:tbl_section,id'],
        ];
    }
}
