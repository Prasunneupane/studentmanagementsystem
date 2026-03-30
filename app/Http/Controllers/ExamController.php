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
     public function index(Request $request)
    {
        $exams = Exam::with(['academicYear', 'term'])
            ->when($request->academic_year_id, fn($q) => $q->where('academic_year_id', $request->academic_year_id))
            ->when($request->exam_type, fn($q) => $q->where('exam_type', $request->exam_type))
            ->when($request->status, function($q, $status) {
                if ($status !== 'all') {
                    $q->where('status', $status);
                }
            })
            ->orderBy('start_date', 'desc')
            ->paginate(12);
        
        // Get counts for dashboard stats
        $stats = [
            'total_exams' => Exam::count(),
            'ongoing_exams' => Exam::where('status', 'ongoing')->count(),
            'upcoming_exams' => Exam::where('status', 'upcoming')->count(),
            'completed_exams' => Exam::where('status', 'completed')->count(),
            'draft_exams' => Exam::where('status', 'draft')->count(),
            'published_exams' => Exam::where('is_published', true)->count(),
        ];
        
        // Get academic years for filter
        $academicYears = AcademicYears::orderBy('id', 'desc')->get();
        
        return Inertia::render('exams/Index', [
            'exams' => $exams,
            'stats' => $stats,
            'filters' => $request->only(['academic_year_id', 'exam_type', 'status']),
            'academicYears' => $academicYears,
        ]);
    }
    
    /**
     * Display the specified exam details with schedules
     */
    public function show(Exam $exam)
    {
        $exam->load([
            'academicYear:id,name',
            'term:id,name',
            'examClasses' => fn($q) => $q
                ->with([
                    'class:id,name',
                    'section:id,name',
                    'schedules' => fn($sq) => $sq
                        ->with('subject:id,name,code')
                        ->orderBy('exam_date')
                        ->orderBy('start_time'),
                ])
                ->orderBy('class_id')
                ->orderBy('section_id'),
        ]);
        
        // Bulk-load class_subjects (with teacher) for all relevant classes
        $classIds = $exam->examClasses->pluck('class_id')->unique();
        
        $classSubjectMap = ClassSubject::whereIn('class_id', $classIds)
            ->with('teacher:id,first_name,last_name')
            ->get()
            ->groupBy('class_id')
            ->map(fn($rows) => $rows->keyBy('subject_id'));
        
        // Group exam_classes by class for better frontend structure
        $examClasses = $exam->examClasses
            ->groupBy('class_id')
            ->map(function ($rows) use ($classSubjectMap) {
                $first = $rows->first();
                $classId = $first->class_id;
                $csMap = $classSubjectMap[$classId] ?? collect();
                
                // Group schedules by date for calendar view
                $allSchedules = $rows->flatMap(fn($ec) => $ec->schedules);
                $schedulesByDate = $allSchedules->groupBy(fn($sch) => $sch->exam_date->format('Y-m-d'));
                
                return [
                    'class_id' => $classId,
                    'class_name' => $first->class?->name ?? 'Unknown Class',
                    'sections' => $rows->map(function ($ec) use ($csMap) {
                        return [
                            'exam_class_id' => $ec->id,
                            'section_id' => $ec->section_id,
                            'section_name' => $ec->section?->name,
                            'schedules' => $ec->schedules->map(function ($sch) use ($csMap) {
                                $cs = $csMap[$sch->subject_id] ?? null;
                                $teacher = $cs?->teacher;
                                $teacherName = $teacher
                                    ? trim("{$teacher->first_name} {$teacher->last_name}")
                                    : null;
                                
                                return [
                                    'id' => $sch->id,
                                    'subject_id' => $sch->subject_id,
                                    'subject_name' => $sch->subject?->name ?? '—',
                                    'subject_code' => $sch->subject?->code ?? '',
                                    'teacher_name' => $teacherName,
                                    'exam_date' => $sch->exam_date?->format('Y-m-d'),
                                    'exam_date_formatted' => $sch->exam_date?->format('D, d M Y'),
                                    'start_time' => $sch->start_time
                                        ? Carbon::parse($sch->start_time)->format('h:i A')
                                        : null,
                                    'end_time' => $sch->end_time
                                        ? Carbon::parse($sch->end_time)->format('h:i A')
                                        : null,
                                    'full_marks' => $sch->max_total_marks,
                                    'pass_marks' => $sch->pass_marks,
                                    'room_no' => $sch->room_no,
                                ];
                            })->values(),
                        ];
                    })->values(),
                    'schedules_by_date' => $schedulesByDate->map(function ($schedules, $date) {
                        return [
                            'date' => $date,
                            'date_formatted' => Carbon::parse($date)->format('D, d M Y'),
                            'schedules' => $schedules->map(fn($sch) => [
                                'subject_name' => $sch->subject?->name,
                                'start_time' => Carbon::parse($sch->start_time)->format('h:i A'),
                                'end_time' => Carbon::parse($sch->end_time)->format('h:i A'),
                                'room_no' => $sch->room_no,
                            ]),
                        ];
                    })->values(),
                ];
            })
            ->values();
        
        return Inertia::render('exams/Show', [
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
                'exam_type' => $exam->exam_type,
                'exam_type_label' => $this->getExamTypeLabel($exam->exam_type),
                'status' => $exam->status,
                'status_label' => $this->getStatusLabel($exam->status),
                'status_color' => $this->getStatusColor($exam->status),
                'start_date' => $exam->start_date?->format('d M Y'),
                'start_date_raw' => $exam->start_date?->format('Y-m-d'),
                'end_date' => $exam->end_date?->format('d M Y'),
                'end_date_raw' => $exam->end_date?->format('Y-m-d'),
                'weightage' => $exam->weightage,
                'is_published' => (bool) $exam->is_published,
                'academic_year' => $exam->academicYear?->name,
                'term' => $exam->term?->name,
            ],
            'examClasses' => $examClasses,
        ]);
    }
    
    /**
     * Print-friendly view of exam schedule
     */
    public function print(Exam $exam)
    {
        $exam->load([
            'academicYear',
            'term',
            'examClasses.class',
            'examClasses.section',
            'examClasses.schedules.subject',
        ]);
        
        return Inertia::render('exams/Print', [
            'exam' => $exam,
            'examClasses' => $exam->examClasses->groupBy('class_id'),
        ]);
    }
    
    private function getExamTypeLabel($type)
    {
        return [
            'unit_test' => 'Unit Test',
            'midterm' => 'Midterm',
            'final' => 'Final',
            'semester' => 'Semester',
            'annual' => 'Annual',
        ][$type] ?? ucfirst($type);
    }
    
    private function getStatusLabel($status)
    {
        return [
            'draft' => 'Draft',
            'ongoing' => 'Ongoing',
            'upcoming' => 'Upcoming',
            'completed' => 'Completed',
        ][$status] ?? ucfirst($status);
    }
    
    private function getStatusColor($status)
    {
        return [
            'draft' => 'gray',
            'ongoing' => 'green',
            'upcoming' => 'blue',
            'completed' => 'gray',
        ][$status] ?? 'gray';
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
