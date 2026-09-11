<?php

namespace App\Http\Controllers;

use App\Interface\CommonServiceInterface;
use App\Interface\ExamScheduleInterface;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamSchedule;
use App\Models\Subject;
use App\Http\Requests\ExamSchedule\ExamScheduleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamScheduleController extends Controller
{
    private CommonServiceInterface $commonServices;
    private ExamScheduleInterface $examSchedule;

    public function __construct(
        CommonServiceInterface $commonServices,
        ExamScheduleInterface  $examSchedule
    ) {
        $this->commonServices = $commonServices;
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
        // dd($classes);
        return Inertia::render('exams/ExamSchedule', [
            'exam'            => $exam->only('id', 'name', 'exam_type', 'start_date', 'end_date', 'academic_year_id'),
            'examClasses'     => $examClasses,
            'classes'         => $classes,
            'subjectsByClass' => $subjectsByClass,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(ExamScheduleRequest $request, Exam $exam)
    {
        $data = $request->validated();
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
        // dd($exam);
        if (!$exam) abort(404);

        $examClasses     = $this->examSchedule->getClassSectionByExamId($exam->id);
        // dd($examClasses);
        $classIds        = $this->examSchedule->getUniqueClassIds($examClasses);
        $classes         = $this->commonServices->getClassessWithSections();
        $subjectsByClass = $this->examSchedule->getSubjectsByClass($classIds, $exam);
        // dd($subjectsByClass);
        // ── Key addition: send existing values so the form is pre-filled ──
        $existingSchedule = $this->examSchedule->getExistingScheduleMap($exam->id);
        //  dd($existingSchedule);
        return Inertia::render('exams/EditExamSchedule', [
            'exam'             => $exam->only('id', 'name', 'exam_type', 'start_date', 'end_date', 'academic_year_id'),
            'examClasses'      => $examClasses,
            'classes'          => $classes,
            'subjectsByClass'  => $subjectsByClass,
            'existingSchedule' => $existingSchedule,   // <── pre-populated values
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(ExamScheduleRequest $request, int $id)
    {
        $exam = Exam::findOrFail($id);
        $data = $request->validated();

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