<?php

namespace App\Http\Controllers;

use App\Contracts\Interface\TermsInterface;
use App\Interface\CommonServiceInterface;
use App\Models\Terms;
use Illuminate\Http\Request;

class TermsController extends Controller
{
    protected TermsInterface $termsService;
    protected CommonServiceInterface $commonService;

    public function __construct(TermsInterface $termsService, CommonServiceInterface $commonService )
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
        return inertia('Terms/Index', [
            'terms' => $TermsList
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Terms $terms)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Terms $terms)
    {
        //
    }
}
