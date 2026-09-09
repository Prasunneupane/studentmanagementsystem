<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class StudentsCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'joinedDate' => ['required', 'date_format:Y-m-d'],
            'stateId' => ['required'],
            // 'fatherName' => ['required', 'string', 'max:100'],
            // 'guardianName' => ['required', 'string', 'max:100'],
        ];


    }

    public function messages(): array
    {
        return [
            'fName.required' => 'First name is required.',
            'lName.required' => 'Last name is required.',
            'phone.required' => 'Phone number is required.',
            'phone.digits' => 'Phone number must be exactly 10 digits.',
            'age.required' => 'Age is required.',
            'age.integer' => 'Age must be an integer.',
            'age.between' => 'Age must be between 1 and 100.',
            'dateOfBirth.required' => 'Date of birth is required.',
            'dateOfBirth.date_format' => 'Date of birth must be in the format YYYY-MM-DD.',
            'classId.required' => 'Class ID is required.',
            'joinedDate.required' => 'Joined date is required.',
            'joinedDate.date_format' => 'Joined date must be in the format YYYY-MM-DD.',
            'stateId.required' => 'State ID is required.',
        ];
    }
}
