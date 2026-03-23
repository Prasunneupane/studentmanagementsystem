<?php

namespace App\Http\Controllers;

use App\Interface\CommonServiceInterface;
use App\Interface\ExamScheduleInterface;
use App\Models\Exam;
use App\Repositories\Validation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   private  CommonServiceInterface $commonServices;
   private ExamScheduleInterface $examScheduleService;
   private $validation;
    public function __construct(
        CommonServiceInterface $commonServices,
        ExamScheduleInterface $examScheduleService,
        Validation $validation
    )
    {
        $this->commonServices = $commonServices;
        $this->validation = $validation;
        $this->examScheduleService = $examScheduleService;
    }
    public function index()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currentYear = $this->commonServices->getCurrentAcademicYear();
        $classesSection = $this->commonServices->getClassessWithSections();
        $academicYears = $this->commonServices->getAcademicYearList();
        $termsList = $this->commonServices->getTermsList();
        return Inertia::render('exams/ExamCreate', [
            'classes' => $classesSection,
            'academicYears' => $academicYears,
            'terms' => $termsList, 
            'currentAcademicYear' => $currentYear
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validation->validateExam($request);
        // dd($data);
        $exam = $this->examScheduleService->createExam($data);
        // dd($exam);
        return redirect()->route('exams.schedule', $exam->id)
            ->with('exam', $exam);
    }

    /**
     * Display the specified resource.
     */
    public function show(Exam $exam)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exam $exam)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Exam $exam)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exam $exam)
    {
        //
    }
}
