<?php

namespace App\Http\Controllers;

use App\Interface\TermsInterface;
use App\Interface\CommonServiceInterface;
use App\Models\Terms;
use App\Repositories\Validation;
use Illuminate\Http\Request;

class TermsController extends Controller
{
    protected TermsInterface $termsService;
    protected CommonServiceInterface $commonService;

    protected $validation;

    public function __construct(
        TermsInterface $termsService,
        CommonServiceInterface $commonService,
        Validation $validation
    )
    {
        $this->termsService = $termsService;
        $this->commonService = $commonService;
        $this->validation = $validation;
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
    public function store(Request $request)
    {
        //  
        // dd($request);
        $validatedData = $request->validate($this->validation->termValidationRules($request));
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
    public function update(Request $request, Terms $terms)
    {
        $validatedData = $request->validate($this->validation->termUpdateValidationRules($request, $terms->id));
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
