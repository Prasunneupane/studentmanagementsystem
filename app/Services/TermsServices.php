<?php

namespace App\Services;

use App\Interface\TermsInterface;
use App\Models\Terms;

class TermsServices implements TermsInterface
{
    /**
     * Create a new class instance.
     */
   
    public function getAllTerms()
    {
        return Terms::where('is_active', true)->get();
    }

    public function store($request)
    {
        // dd($request);
        $data =[
            ...$request,
            'is_active' => true,
            'created_by' => auth()->id(),
        ];
        // dd($data);
        return Terms::create($data);
    }
    public function update($request, $id)
    {
        $term = Terms::findOrFail($id);
        if(!$term) {
            throw new \Exception("Term not found", 404);
        }
            $data =[
                ...$request,
                'updated_by' => auth()->id(),
            ];
        return $term->update($data);
    }

    public function destroy($id)
    {
        $term = Terms::findOrFail($id);
        if(!$term) {
            throw new \Exception("Term not found", 404);
        }
        return $term->update(['is_active' => false]);
    }

    
}
