<?php

namespace App\Http\Controllers;

use App\Interface\CommonServiceInterface;
use App\Interface\ExamScheduleInterface;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamSchedule;
use App\Models\Subject;
use App\Repositories\Validation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private CommonServiceInterface $commonServices;
    private $validation;

    private ExamScheduleInterface  $examSchedule;
    public function __construct(
        CommonServiceInterface $commonServices,
        Validation $validation,
        ExamScheduleInterface $examSchedule
    )
    {
        $this->commonServices = $commonServices;
        $this->validation = $validation;
        $this->examSchedule = $examSchedule;
    }
    
    public function index()
    {
        //
    }

   public function create(Exam $exam)
    {
        // Load which classes/sections are in this exam
        $examClasses = $this->examSchedule->getClassSectionByExamId($exam->id);
        // dd($examClasses);
        
        // Unique class IDs
        $classIds = $this->examSchedule->getUniqueClassIds($examClasses);
        
        // Classes with sections
        $classes = $this->commonServices->getClassessWithSections();
            
        // Subjects per class (from class_subjects for this academic year)
        $subjectsByClass = $this->examSchedule->getSubjectsByClass($classIds, $exam);
        // dd($subjectsByClass);
        // foreach ($classIds as $classId) {
        //     $subjectsByClass[(string) $classId] = Subject::whereHas('classSubjects', function ($q) use ($classId, $exam) {
        //             $q->where('class_id', $classId)
        //               ->where('academic_year_id', $exam->academic_year_id);
        //         })
        //         ->get(['id', 'name', 'code'])
        //         ->map(fn($s) => [
        //             'id'   => (string) $s->id,
        //             'name' => $s->name,
        //             'code' => $s->code ?? '',
        //         ])
        //         ->values()
        //         ->toArray();
        // }

        return Inertia::render('exams/ExamSchedule', [
            'exam'           => $exam->only('id', 'name', 'exam_type', 'start_date', 'end_date', 'academic_year_id'),
            'examClasses'    => $examClasses,
            'classes'        => $classes,
            'subjectsByClass' => $subjectsByClass,
        ]);
    }

    /**
     * Save exam schedules (bulk upsert)
     * POST /exams/{exam}/schedule
     */
    public function store(Request $request, Exam $exam)
    {
        $data = $this->validation->validateExamSchedule($request);

        $this->examSchedule->saveExamSchedule($exam, $data['schedules']);

        return redirect()->route('exams.index')
            ->with('success', 'Exam schedule saved successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ExamSchedule $examSchedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExamSchedule $examSchedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExamSchedule $examSchedule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExamSchedule $examSchedule)
    {
        //
    }
}
