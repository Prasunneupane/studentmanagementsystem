<?php

namespace App\Interface;

interface TermsInterface
{
  
    public function getAllTerms();
    public function store($request);

    // public function show($id);

    public function update($request, $id);

    public function destroy($id);
}
