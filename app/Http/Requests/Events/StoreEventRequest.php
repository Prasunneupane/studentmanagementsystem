<?php

namespace App\Http\Requests\Events;

use App\Enums\EventCategory;
use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreEventRequest extends FormRequest
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
        // dd($this->all());
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'status' => ['required', new Enum(EventStatus::class)], //  Check enum cases
            'event_type' => ['required', new Enum(EventCategory::class)], //  Check your enum cases
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
