<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        if ($this->routeIs('users.create-teacher-user')) {
            return [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'roles' => ['required', 'exists:tbl_roles,id'],
            ];
        }

        $user = $this->route('user');
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => $this->isMethod('post') ? ['required', 'string', 'min:8', 'confirmed'] : ['nullable', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'exists:tbl_roles,id'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
