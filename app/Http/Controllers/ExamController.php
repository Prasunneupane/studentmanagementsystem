<?php

namespace App\Http\Controllers;

use App\Interface\CommonServiceInterface;
use App\Interface\ExamScheduleInterface;
use App\Models\AcademicYears;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Repositories\Validation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private CommonServiceInterface $commonServices;
    private ExamScheduleInterface $examScheduleService;
    private $validation;
    public function __construct(
        CommonServiceInterface $commonServices,
        ExamScheduleInterface $examScheduleService,
        Validation $validation
    ) {
        $this->commonServices = $commonServices;
        $this->validation = $validation;
        $this->examScheduleService = $examScheduleService;
    }
    public function index(Request $request)
    {
        $exams = $this->examScheduleService->getAllSchedulesForIndex();

        $academicYears = $this->commonServices->getAcademicYearList();
        return Inertia::render('exams/ScheduleIndex', [
            'exams' => $exams,
            'academicYears' => $academicYears,
            'filters' => $request->only(['academic_year_id', 'exam_type', 'status', 'search']),
        ]);
    }

    /**
     * Print-friendly view of exam schedule
     */
    

    
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

        $exam = $this->examScheduleService->createExam($data);
        // dd($exam->id);
        return redirect()
            ->route('exams.schedule', $exam->id)
            ->with('success', 'Exam created successfully');
    }

    /**
     * Display the specified resource.
     */

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

    public function scheduleIndex(Request $request)
    {
        $exams = $this->examScheduleService->getAllSchedulesForIndex();

        $academicYears = $this->commonServices->getAcademicYearList();
        return Inertia::render('exams/ScheduleIndex', [
            'exams' => $exams,
            'academicYears' => $academicYears,
            'filters' => $request->only(['academic_year_id', 'exam_type', 'status', 'search']),
        ]);
    }

    // ── View schedule detail for one exam (used by the slide-over) ───
    public function scheduleShow(int $id)
    {
        $exam = $this->examScheduleService->getExamWithDetails($id);
        // dd($exam);
        $groupedSchedule = $this->examScheduleService->getScheduleGroupedByClass($id);
        
        if (!$exam)
            abort(404);
        // dd($groupedSchedule);
        return Inertia::render('exams/Schedule', [
            'exam' => $exam,
            'groupedSchedule' => $groupedSchedule,
        ]);
    }

    // ── Delete a schedule row ────────────────────────────────────────
    public function scheduleDestroy(int $id)
    {
        $this->examScheduleService->deleteSchedule($id);
        return back()->with('success', 'Schedule deleted successfully.');
    }

    // ── Toggle is_active on parent exam ─────────────────────────────
    public function toggleActive(int $id)
    {
        $exam = $this->examScheduleService->toggleActive($id);
        return back()->with('success', 'Status updated.');
    }

    public function schedule(int $id)
    {
        $exam = $this->examScheduleService->getExamWithDetails($id);

        if (!$exam) {
            abort(404);
        }
        
        $groupedSchedule = $this->examScheduleService->getScheduleGroupedByClass($id);
        
        // Unique classes enrolled in this exam (from exam_classes)
        $classes = $exam->examClasses
            ->groupBy('class_id')
            ->map(fn($rows) => [
                'class_id' => $rows->first()->class_id,
                'class_name' => $rows->first()->class->name ?? 'N/A',
                'sections' => $rows->map(fn($r) => [
                    'section_id' => $r->section_id,
                    'section_name' => $r->section->name ?? 'N/A',
                ])->values(),
            ])
            ->values();
        // dd($classes);
        // return Inertia::render('exams/Schedule', [
        //     'exam' => $exam,
        //     'groupedSchedule' => $groupedSchedule,
        //     'classes' => $classes,
        // ]);
         return response()->json([
                'exam' => $exam,
                'groupedSchedule' => $groupedSchedule,
                'classes' => $classes,
            ]);
    }
}
