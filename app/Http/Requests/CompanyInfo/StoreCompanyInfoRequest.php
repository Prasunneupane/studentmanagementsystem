<?php

namespace App\Http\Requests\CompanyInfo;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // dd($this->all());
        return [
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_pan_no' => 'nullable|string|max:50',
            'company_phone_no' => 'nullable|string|max:30',
            'company_mobile_no' => 'nullable|string|max:30',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_active' => 'boolean',
        ];
    }
}
