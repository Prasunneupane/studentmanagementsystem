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
        // --- IGNORE ---
    }
    public function update($request, $id)
    {
        // --- IGNORE ---
    }

    public function destroy($id)
    {
        // --- IGNORE ---
    }

    
}
