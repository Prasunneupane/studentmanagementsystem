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
        $data = $request->validate([
            'schedules'                        => 'required|array|min:1',
            'schedules.*.class_id'             => 'required|exists:classes,id',
            'schedules.*.section_id'           => 'nullable|exists:sections,id',
            'schedules.*.subject_id'           => 'required|exists:subjects,id',
            'schedules.*.exam_date'            => 'required|date',
            'schedules.*.start_time'           => 'nullable|date_format:H:i',
            'schedules.*.end_time'             => 'nullable|date_format:H:i',
            'schedules.*.room_no'              => 'nullable|string|max:50',
            'schedules.*.max_theory_marks'     => 'nullable|numeric|min:0',
            'schedules.*.max_practical_marks'  => 'nullable|numeric|min:0',
            'schedules.*.max_total_marks'      => 'nullable|numeric|min:0',
            'schedules.*.pass_marks'           => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $exam) {
            // Delete existing schedules for this exam before re-inserting
            ExamSchedule::where('exam_id', $exam->id)->delete();

            $rows = collect($data['schedules'])->map(fn($s) => [
                'exam_id'              => $exam->id,
                'class_id'             => $s['class_id'],
                'section_id'           => $s['section_id'] ?? null,
                'subject_id'           => $s['subject_id'],
                'exam_date'            => $s['exam_date'],
                'start_time'           => $s['start_time'] ?? null,
                'end_time'             => $s['end_time'] ?? null,
                'room_no'              => $s['room_no'] ?? null,
                'max_theory_marks'     => $s['max_theory_marks'] ?? 80,
                'max_practical_marks'  => $s['max_practical_marks'] ?? 20,
                'max_total_marks'      => $s['max_total_marks'] ?? 100,
                'pass_marks'           => $s['pass_marks'] ?? 40,
                'created_at'           => now(),
                'updated_at'           => now(),
            ])->toArray();

            ExamSchedule::insert($rows);
        });

        return redirect()->route('exams.show', $exam->id)
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
