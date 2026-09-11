<?php

namespace App\Http\Requests\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $permission = $this->route('permission');
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tbl_permissions', 'name')->ignore($permission?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'module' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
