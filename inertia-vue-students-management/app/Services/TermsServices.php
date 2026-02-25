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
        return Terms::all();
    }

    public function store($request)
    {
        return Terms::create($request);
    }
    public function update($request, $id)
    {
        $term = Terms::findOrFail($id);
        if(!$term) {
            throw new \Exception("Term not found", 404);
        }
        return $term->update($request);
    }

    public function destroy($id)
    {
        // --- IGNORE ---
    }

    
}
