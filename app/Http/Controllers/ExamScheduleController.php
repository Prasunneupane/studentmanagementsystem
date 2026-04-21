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
    private CommonServiceInterface $commonServices;
    private Validation $validation;
    private ExamScheduleInterface $examSchedule;

    public function __construct(
        CommonServiceInterface $commonServices,
        Validation             $validation,
        ExamScheduleInterface  $examSchedule
    ) {
        $this->commonServices = $commonServices;
        $this->validation     = $validation;
        $this->examSchedule   = $examSchedule;
    }

    // ── Index ─────────────────────────────────────────────────────────
    public function index()
    {
        //
    }

    // ── Create (schedule builder) ─────────────────────────────────────
    public function create(Exam $exam)
    {
        $examClasses     = $this->examSchedule->getClassSectionByExamId($exam->id);
        $classIds        = $this->examSchedule->getUniqueClassIds($examClasses);
        $classes         = $this->commonServices->getClassessWithSections();
        $subjectsByClass = $this->examSchedule->getSubjectsByClass($classIds, $exam);

        return Inertia::render('exams/ExamSchedule', [
            'exam'            => $exam->only('id', 'name', 'exam_type', 'start_date', 'end_date', 'academic_year_id'),
            'examClasses'     => $examClasses,
            'classes'         => $classes,
            'subjectsByClass' => $subjectsByClass,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request, Exam $exam)
    {
        $data = $this->validation->validateExamSchedule($request);
        $this->examSchedule->saveExamSchedule($exam, $data['schedules']);

        return redirect()->route('exams.index')
            ->with('success', 'Exam schedule saved successfully.');
    }

    // ── Show (view schedule) ──────────────────────────────────────────
    public function scheduleShow(int $id)
    {
        $exam = $this->examSchedule->getExamWithDetails($id);

        if (!$exam) abort(404);

        $groupedSchedule = $this->examSchedule->getScheduleGroupedByClass($id);

        return Inertia::render('exams/Schedule', [
            'exam'            => $exam,
            'groupedSchedule' => $groupedSchedule,
        ]);
    }

    // ── Edit (pre-populated schedule form) ───────────────────────────
    public function edit(int $id)
    {
        $exam = $this->examSchedule->getExamWithDetails($id);

        if (!$exam) abort(404);

        $examClasses     = $this->examSchedule->getClassSectionByExamId($exam->id);
        $classIds        = $this->examSchedule->getUniqueClassIds($examClasses);
        $classes         = $this->commonServices->getClassessWithSections();
        $subjectsByClass = $this->examSchedule->getSubjectsByClass($classIds, $exam);

        // ── Key addition: send existing values so the form is pre-filled ──
        $existingSchedule = $this->examSchedule->getExistingScheduleMap($exam->id);

        return Inertia::render('exams/EditExamSchedule', [
            'exam'             => $exam->only('id', 'name', 'exam_type', 'start_date', 'end_date', 'academic_year_id'),
            'examClasses'      => $examClasses,
            'classes'          => $classes,
            'subjectsByClass'  => $subjectsByClass,
            'existingSchedule' => $existingSchedule,   // <── pre-populated values
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, int $id)
    {
        $exam = Exam::findOrFail($id);
        $data = $this->validation->validateExamSchedule($request);

        $this->examSchedule->updateExamSchedule($exam, $data['schedules']);

        return redirect()->route('exams.schedule.show', $id)
            ->with('success', 'Exam schedule updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy(int $scheduleId)
    {
        $this->examSchedule->deleteSchedule($scheduleId);

        return back()->with('success', 'Schedule entry removed.');
    }
}