<?php

namespace App\Http\Requests\ExamSchedule;

use Illuminate\Foundation\Http\FormRequest;

class ExamScheduleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.class_id' => ['required', 'exists:tbl_classes,id'],
            'schedules.*.section_id' => ['nullable', 'exists:tbl_section,id'],
            'schedules.*.subject_id' => ['required', 'exists:tbl_subjects,id'],
            'schedules.*.exam_date' => ['required', 'date'],
            'schedules.*.start_time' => ['nullable', 'date_format:H:i'],
            'schedules.*.end_time' => ['nullable', 'date_format:H:i'],
            'schedules.*.room_no' => ['nullable', 'string', 'max:50'],
            'schedules.*.max_theory_marks' => ['nullable', 'numeric', 'min:0'],
            'schedules.*.max_practical_marks' => ['nullable', 'numeric', 'min:0'],
            'schedules.*.max_total_marks' => ['nullable', 'numeric', 'min:0'],
            'schedules.*.pass_marks' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
