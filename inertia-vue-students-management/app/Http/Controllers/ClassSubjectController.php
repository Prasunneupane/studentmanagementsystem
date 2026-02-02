<?php

namespace App\Http\Controllers;

use App\Models\Classes;
use App\Models\ClassSubject;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\AcademicYear;
use App\Models\Teachers;
use App\Repositories\Validation;
use App\Services\StudentService;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassSubjectController extends Controller
{
    /**
     * Display list of class-subject assignments
     */
    private $studentService;
    private $validation;
    public function __construct(
        StudentService $studentService,
        Validation $validation
    )
    {   
        $this->studentService = $studentService;
        $this->validation = $validation;
    }
    public function index(Request $request)
    {
        $academicYearId = $request->input('academic_year_id', DB::table('tbl_academic_years')->where('is_active', 1)->first()?->id);
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');

        $query = ClassSubject::with(['class', 'section', 'subject', 'teacher', 'academicYear'])
            ->forAcademicYear($academicYearId);

        if ($classId) {
            $query->forClass($classId);
        }

        if ($sectionId) {
            $query->forSection($sectionId);
        }

        $assignments = $query->orderBy('class_id')
            ->orderBy('section_id')
            ->orderBy('subject_id')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'class_id' => $assignment->class_id,
                    'class_name' => $assignment->class?->name,
                    'section_id' => $assignment->section_id,
                    'section_name' => $assignment->section?->name,
                    'subject_id' => $assignment->subject_id,
                    'subject_name' => $assignment->subject?->name,
                    'teacher_id' => $assignment->teacher_id,
                    'teacher_name' => $assignment->teacher ? $assignment->teacher->first_name . ' ' . $assignment->teacher->last_name : 'Unassigned',
                    'academic_year_id' => $assignment->academic_year_id,
                    'academic_year_name' => $assignment->academicYear?->name,
                    'is_optional' => $assignment->is_optional,
                    'periods_per_week' => $assignment->periods_per_week,
                    'max_marks' => $assignment->max_marks,
                    'pass_marks' => $assignment->pass_marks,
                ];
            });

        $classes = Classes::where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['value' => (string)$c->id, 'label' => $c->name]);

        $academicYears = DB::table('tbl_academic_years')->orderBy('start_date', 'desc')
            ->get()
            ->map(fn($y) => ['value' => (string)$y->id, 'label' => $y->name]);

        return Inertia::render('ClassSubjects/Index', [
            'assignments' => $assignments,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'currentAcademicYearId' => $academicYearId,
            'filters' => [
                'class_id' => $classId,
                'section_id' => $sectionId,
            ],
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $classes = Classes::where('is_active', 1)
            ->select('id as value','name as label')
            ->orderBy('name')
            ->get();
            

        $subjects = Subject::where('is_active', 1)
            ->select('id as value','name as label')
            ->orderBy('name')
            ->get();
            

        $teachers = Teachers::where('is_active', 1)
            ->select('id as value','name as label')->get();
            

        $academicYears = DB::table('tbl_academic_years')
            ->orderBy('start_date', 'desc')
            ->select('id as value','academic_year as label')
            ->get();
           

        $currentAcademicYear = DB::table('tbl_academic_years')
             ->select('id as value','academic_year as label')
             ->where('is_active', operator: 1)->first();

        return Inertia::render('classSubject/Create', [
            'classes' => $classes,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'academicYears' => $academicYears,
            'currentAcademicYear' => $currentAcademicYear??null,
        ]);
    }

    /**
     * Store new assignment
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->validation->classSubjectValidationRules($request));

        // dd($validated);
        // Validate pass_marks <= max_marks
        if ($validated['pass_marks'] > $validated['max_marks']) {
            return back()->withErrors(['pass_marks' => 'Pass marks cannot exceed max marks']);
        }

        // Check for duplicate
        $exists = ClassSubject::where('class_id', $validated['class_id'])
            ->where('section_id', $validated['section_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'subject_id' => 'This subject is already assigned to this class-section for the selected academic year.'
            ]);
        }
        // dd($validated);
        ClassSubject::create($validated);

        return redirect()->route('class-subjects.index')->with('success', 'Subject assigned successfully');
    }

    /**
     * Show edit form
     */
    public function edit(ClassSubject $classSubject)
    {
        $classes = Classes::where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->map(fn($c) => ['value' => (string)$c->id, 'label' => $c->name]);

        $sections = Section::where('class_id', $classSubject->class_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->map(fn($s) => ['value' => (string)$s->id, 'label' => $s->name]);

        $subjects = Subject::where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->map(fn($s) => ['value' => (string)$s->id, 'label' => $s->name]);

        $teachers = Teachers::where('is_active', 1)
            ->orderBy('first_name')
            ->get()
            ->map(fn($t) => [
                'value' => (string)$t->id,
                'label' => $t->first_name . ' ' . $t->last_name
            ]);

        $academicYears = DB::table('tbl_academic_years')->orderBy('start_date', 'desc')
            ->get()
            ->map(fn($y) => ['value' => (string)$y->id, 'label' => $y->name]);

        return Inertia::render('ClassSubjects/Edit', [
            'classSubject' => [
                'id' => $classSubject->id,
                'class_id' => (string)$classSubject->class_id,
                'section_id' => (string)$classSubject->section_id,
                'subject_id' => (string)$classSubject->subject_id,
                'teacher_id' => $classSubject->teacher_id ? (string)$classSubject->teacher_id : null,
                'academic_year_id' => (string)$classSubject->academic_year_id,
                'is_optional' => $classSubject->is_optional,
                'periods_per_week' => $classSubject->periods_per_week,
                'max_marks' => $classSubject->max_marks,
                'pass_marks' => $classSubject->pass_marks,
            ],
            'classes' => $classes,
            'sections' => $sections,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update assignment
     */
    public function update(Request $request, ClassSubject $classSubject)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'required|exists:tbl_sections,id',
            'subject_id' => 'required|exists:tbl_subjects,id',
            'teacher_id' => 'nullable|exists:tbl_teachers,id',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'is_optional' => 'boolean',
            'periods_per_week' => 'required|integer|min:0|max:50',
            'max_marks' => 'required|numeric|min:0|max:1000',
            'pass_marks' => 'required|numeric|min:0|max:1000',
        ]);
         dd($validated);
        // Validate pass_marks <= max_marks
        if ($validated['pass_marks'] > $validated['max_marks']) {
            return back()->withErrors(['pass_marks' => 'Pass marks cannot exceed max marks']);
        }
       

        // Check for duplicate (excluding current record)
        $exists = ClassSubject::where('class_id', $validated['class_id'])
            ->where('section_id', $validated['section_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('id', '!=', $classSubject->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'subject_id' => 'This subject is already assigned to this class-section for the selected academic year.'
            ]);
        }

        $classSubject->update($validated);

        return redirect()->route('class-subjects.index')->with('success', 'Assignment updated successfully');
    }

    /**
     * Delete assignment
     */
    public function destroy(ClassSubject $classSubject)
    {
        $classSubject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully'
        ]);
    }

    /**
     * Get sections by class ID (for dynamic loading)
     */
    public function getSectionsByClass(Request $request)
    {
        $sections = $this->studentService->getSectionList($request->input('class_id'));

        return response()->json($sections);
        //
    }
}