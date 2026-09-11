<?php

namespace App\Http\Controllers;

use App\Interface\TermsInterface;
use App\Interface\CommonServiceInterface;
use App\Models\Terms;
use App\Http\Requests\Term\TermRequest;

class TermsController extends Controller
{
    protected TermsInterface $termsService;
    protected CommonServiceInterface $commonService;

    public function __construct(
        TermsInterface $termsService,
        CommonServiceInterface $commonService
    )
    {
        $this->termsService = $termsService;
        $this->commonService = $commonService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $TermsList = $this->termsService->getAllTerms();
        // dd($TermsList);
        return inertia('terms/TermsList', [
            'terms' => $TermsList
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $academicYears = $this->commonService->getAcademicYearList();
        $currentAcademicYear = $this->commonService->getCurrentAcademicYear();
        return inertia( 'terms/AddUpdateTerms',[
            'academicYears' => $academicYears,
            'currentAcademicYear' => $currentAcademicYear,
            
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TermRequest $request)
    {
        //  
        // dd($request);
        $validatedData = $request->validated();
        // dd($validatedData);
        $this->termsService->store($validatedData);

        return redirect()->route('terms.index')->with('success', 'Term created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Terms $terms)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Terms $terms)
    {
        //
        $academicYears = $this->commonService->getAcademicYearList();
        return inertia( 'terms/AddUpdateTerms',[
            'terms' => $terms,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TermRequest $request, Terms $terms)
    {
        $validatedData = $request->validated();
        $this->termsService->update($validatedData, $terms->id);

        return redirect()->route('terms.index')->with('success', 'Term updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Terms $terms)
    {
        $this->termsService->destroy($terms->id);
        return redirect()->route('terms.index')->with('success', 'Term deleted successfully.');
    }
}
