<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyInfo\StoreCompanyInfoRequest;
use App\Interface\CompanyInfoInterface;
use App\Models\CompanyInfo;
use Inertia\Inertia;

class CompanyInfoController extends Controller
{
    public function __construct(private CompanyInfoInterface $companyInfoService) {}

    public function index()
    {
        return Inertia::render('company-info/Index', [
            'companies' => $this->companyInfoService->getAllCompanyInfo(),
        ]);
    }

    public function create()
    {
        return Inertia::render('company-info/Create');
    }

    public function store(StoreCompanyInfoRequest $request)
    {
        $this->companyInfoService->createCompanyInfo($request->validated());

        return redirect()->route('company-info.index')->with('success', 'Company info created successfully.');
    }

    public function edit(CompanyInfo $companyInfo)
    {
        return Inertia::render('company-info/Edit', [
            'company' => $this->companyInfoService->getCompanyInfoById($companyInfo->id),
        ]);
    }

    public function update(StoreCompanyInfoRequest $request, CompanyInfo $companyInfo)
    {
        
        $this->companyInfoService->updateCompanyInfo($companyInfo->id, $request->validated());

        return redirect()->route('company-info.index')->with('success', 'Company info updated successfully.');
    }

    public function destroy(CompanyInfo $companyInfo)
    {
        $this->companyInfoService->deleteCompanyInfo($companyInfo->id);

        return redirect()->route('company-info.index')->with('success', 'Company info deleted successfully.');
    }
}
