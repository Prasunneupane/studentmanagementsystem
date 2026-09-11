<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class DeleteProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['password' => ['required', 'current_password']]; }
}
