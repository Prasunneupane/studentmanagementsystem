<?php

namespace App\Http\Controllers;

use App\Interface\CommonServiceInterface;
use App\Interface\StudentMarksInterface;
use App\Interface\ExamScheduleInterface;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Repositories\Validation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentMarksController extends Controller
{
    private CommonServiceInterface $commonServices;
    private StudentMarksInterface $marksService;
    private ExamScheduleInterface $examScheduleService;
    private Validation $validation;

    public function __construct(
        CommonServiceInterface $commonServices,
        StudentMarksInterface $marksService,
        ExamScheduleInterface $examScheduleService,
        Validation $validation
    ) {
        $this->commonServices = $commonServices;
        $this->marksService = $marksService;
        $this->examScheduleService = $examScheduleService;
        $this->validation = $validation;
    }

    /**
     * Marks entry dashboard — select exam, class, section, subject
     */
    public function index(Request $request)
    {
        $academicYears = $this->commonServices->getAcademicYearList();
        $currentYear = $this->commonServices->getCurrentAcademicYear();
        $classes = $this->commonServices->getClassessWithSections();

        $exams = $this->marksService->getExamsWithSchedules(
            $request->input('academic_year_id', $currentYear?->value)
        );

        // If exam + class + section selected, get subjects
        $subjects = collect();
        if ($request->filled(['exam_id', 'class_id', 'section_id'])) {
            $subjects = $this->marksService->getScheduleSubjects(
                (int) $request->exam_id,
                (int) $request->class_id,
                (int) $request->section_id
            );
        }

        return Inertia::render('marks/MarksIndex', [
            'academicYears' => $academicYears,
            'currentAcademicYear' => $currentYear,
            'classes' => $classes,
            'exams' => $exams,
            'subjects' => $subjects,
            'filters' => $request->only(['academic_year_id', 'exam_id', 'class_id', 'section_id']),
        ]);
    }

    /**
     * Show marks entry form for a specific exam+class+section+subject
     */
    public function enterMarks(Request $request, int $examId)
    {
        $request->validate([
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'required|exists:tbl_section,id',
            'subject_id' => 'required|exists:tbl_subjects,id',
        ]);

        $exam = Exam::with(['academicYear', 'term'])->findOrFail($examId);

        $data = $this->marksService->getStudentsForMarksEntry(
            $examId,
            (int) $request->class_id,
            (int) $request->section_id,
            (int) $request->subject_id
        );

        $classes = $this->commonServices->getClassessWithSections();

        return Inertia::render('marks/MarksEntry', [
            'exam' => $exam->only('id', 'name', 'exam_type', 'start_date', 'end_date', 'academic_year_id'),
            'classId' => (int) $request->class_id,
            'sectionId' => (int) $request->section_id,
            'subjectId' => (int) $request->subject_id,
            'students' => $data['students'],
            'schedule' => $data['schedule'],
            'classes' => $classes,
        ]);
    }

    /**
     * Save marks
     */
    public function storeMarks(Request $request, int $examId)
    {
        $data = $this->validation->validateMarksEntry($request);
        $this->marksService->saveMarks($examId, $data['marks']);

        return back()->with('success', 'Marks saved successfully.');
    }

    /**
     * View individual student marksheet
     */
    public function marksheet(int $examId, int $studentId)
    {
        $marksheet = $this->marksService->getMarksheet($examId, $studentId);

        return Inertia::render('marks/Marksheet', [
            'marksheet' => $marksheet,
        ]);
    }

    /**
     * Results view for a class/section
     */
    public function results(Request $request, int $examId)
    {
        $exam = Exam::with(['academicYear', 'term'])->findOrFail($examId);
        $classes = $this->commonServices->getClassessWithSections();

        // Get exam classes for filtering
        $examClasses = ExamClass::where('exam_id', $examId)
            ->with(['class', 'section'])
            ->get()
            ->map(fn($ec) => [
                'class_id' => $ec->class_id,
                'class_name' => $ec->class->name ?? 'N/A',
                'section_id' => $ec->section_id,
                'section_name' => $ec->section->name ?? 'N/A',
            ]);

        $results = collect();
        if ($request->filled(['class_id', 'section_id'])) {
            $results = $this->marksService->getResults(
                $examId,
                (int) $request->class_id,
                (int) $request->section_id
            );
        }

        return Inertia::render('marks/Results', [
            'exam' => $exam,
            'examClasses' => $examClasses,
            'classes' => $classes,
            'results' => $results,
            'filters' => $request->only(['class_id', 'section_id']),
        ]);
    }

    /**
     * Calculate results for a class/section
     */
    public function calculateResults(Request $request, int $examId)
    {
        $request->validate([
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'nullable|exists:tbl_section,id',
        ]);

        $this->marksService->calculateResults(
            $examId,
            (int) $request->class_id,
            $request->section_id ? (int) $request->section_id : null
        );

        return back()->with('success', 'Results calculated successfully.');
    }

    /**
     * Finalize results for a class/section
     */
    public function finalizeResults(Request $request, int $examId)
    {
        $request->validate([
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'nullable|exists:tbl_section,id',
        ]);

        $this->marksService->finalizeResults(
            $examId,
            (int) $request->class_id,
            $request->section_id ? (int) $request->section_id : null
        );

        return back()->with('success', 'Results finalized successfully.');
    }

    /**
     * Student marks history (cross-year)
     */
    public function studentHistory(int $studentId)
    {
        $history = $this->marksService->getStudentMarksHistory($studentId);

        return Inertia::render('marks/StudentHistory', [
            'studentId' => $studentId,
            'history' => $history,
        ]);
    }
}
