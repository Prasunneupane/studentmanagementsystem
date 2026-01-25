<?php

namespace App\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Storage;

class CommonTransformers extends JsonResource
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function stateListTransformer($states)
    {
        $formattedStates = [];
        foreach ($states as $id =>$state) {
            $formattedStates[] = [
                'value' => $id,
                'label' => $state,
            ];
        }
        return $formattedStates;
    }

    public function districtListTransformer($districts)
    {
        $formattedDistricts = [];
        foreach ($districts as $district) {
            $formattedDistricts[] = [
                'value' => $district->id,
                'label' => $district->name,
            ];
        }
        return $formattedDistricts;
    }   

    public function municipalityListTransformer($municipalities)
    {
        $formattedMunicipalities = [];
        foreach ($municipalities as $municipality) {
            $formattedMunicipalities[] = [
                'value' => $municipality->id,
                'label' => $municipality->name,
            ];
        }
        return $formattedMunicipalities;
    }   

    public function classListTransformer($classes)
    {
        $formattedClasses = [];
        foreach ($classes as $id => $name) {
            $formattedClasses[] = [
                'value' => $id,
                'label' => $name,
            ];
        }
        return $formattedClasses;
    }

    public function sectionListTransformer($sections)
    {
        $formattedSections = [];
        foreach ($sections as $id => $name) {
            $formattedSections[] = [
                'value' => $id,
                'label' => $name,
            ];
        }
        return $formattedSections;
    }

    public function defaultValuesTransform($defaultValues)
    {
        $formattedDefaults = [];
        foreach ($defaultValues as $key => $value) {
            $formattedDefaults[] = [
                'setting_key' => $key,
                'setting_value' => $value,
            ];
        }
        return $formattedDefaults;
    }

    public function studentListTransform($students)
    {
        // dd($students);
        return collect($students)->map(fn ($student) => [
            'id' => $student->id,
            'first_name' => $student->first_name,
            'middle_name' => $student->middle_name,
            'last_name' => $student->last_name,
            'email' => $student->email,
            'phone' => $student->phone,
            'age' => $student->age,
            'date_of_birth' => $student->date_of_birth,
            'joined_date' => $student->joined_date,
            'address' => $student->address,
            'contact_number' => $student->contact_number,
            'photo_url' => $student->photo
                ? Storage::url($student->photo)
                : '/images/default-avatar.png',

            'class_id' => $student->class_id,
            'class_name' => $student->class?->name,

            'section_id' => $student->section_id,
            'section_name' => $student->section?->name,

            'state_id' => $student->state_id,
            'state_name' => $student->state?->name,

            'district_id' => $student->district_id,
            'district_name' => $student->district?->name,

            'municipality_id' => $student->municipality_id,
            'municipality_name' => $student->municipality?->name,
        ])->toArray();
    }
}
