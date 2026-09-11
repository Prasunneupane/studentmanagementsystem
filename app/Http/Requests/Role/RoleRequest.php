<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        if ($this->routeIs('roles.permissions.assign')) {
            return [
                'role_id' => ['required', 'exists:tbl_roles,id'],
                'permissions' => ['nullable', 'array'],
                'permissions.*' => ['exists:tbl_permissions,id'],
            ];
        }

        $role = $this->route('role');
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tbl_roles', 'name')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
