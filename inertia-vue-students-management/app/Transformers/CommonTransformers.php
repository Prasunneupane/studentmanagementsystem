<?php

namespace App\Transformers;

class CommonTransformers
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
}
