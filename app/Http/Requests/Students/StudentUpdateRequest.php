<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class StudentUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
                'fName' => ['required', 'string', 'max:100'],
                'lName' => ['required', 'string', 'max:100'],
                'phone' => ['required', 'digits:10'],
                'age' => ['required', 'integer', 'between:1,100'],
                'dateOfBirth' => ['required', 'date_format:Y-m-d'],
                'classId' => ['required'],
                'stateId' => ['required'],
            ];
    }
    public function messages(): array
    {
        return [
            'fName.required' => 'The first name field is required.',
            'lName.required' => 'The last name field is required.',
            'phone.required' => 'The phone field is required.',
            'age.required' => 'The age field is required.',
            'dateOfBirth.required' => 'The date of birth field is required.',
            'classId.required' => 'The class field is required.',
            'stateId.required' => 'The state field is required.',
        ];
    }
}

