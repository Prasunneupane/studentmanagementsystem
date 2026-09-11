<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class LoadByDateRangeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ];
    }
}
