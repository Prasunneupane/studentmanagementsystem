<?php

namespace App\Http\Requests\Term;

use Illuminate\Foundation\Http\FormRequest;

class TermRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'term_number' => ['required', 'integer', 'min:1', 'max:10'],
            'academic_year_id' => ['required', 'exists:tbl_academic_years,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
