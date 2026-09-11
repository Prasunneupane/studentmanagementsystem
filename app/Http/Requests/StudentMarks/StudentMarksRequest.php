<?php

namespace App\Http\Requests\StudentMarks;

use Illuminate\Foundation\Http\FormRequest;

class StudentMarksRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        if ($this->routeIs('marks.enter')) {
            return [
                'class_id' => ['required', 'exists:tbl_classes,id'],
                'section_id' => ['required', 'exists:tbl_section,id'],
                'subject_id' => ['required', 'exists:tbl_subjects,id'],
            ];
        }

        if ($this->routeIs('marks.calculate') || $this->routeIs('marks.finalize')) {
            return [
                'class_id' => ['required', 'exists:tbl_classes,id'],
                'section_id' => ['nullable', 'exists:tbl_section,id'],
            ];
        }

        return [
            'marks' => ['required', 'array', 'min:1'],
            'marks.*.student_id' => ['required', 'exists:students,id'],
            'marks.*.class_id' => ['required', 'exists:tbl_classes,id'],
            'marks.*.section_id' => ['nullable', 'exists:tbl_section,id'],
            'marks.*.subject_id' => ['required', 'exists:tbl_subjects,id'],
            'marks.*.theory_marks' => ['nullable', 'numeric', 'min:0'],
            'marks.*.practical_marks' => ['nullable', 'numeric', 'min:0'],
            'marks.*.is_absent' => ['boolean'],
            'marks.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
