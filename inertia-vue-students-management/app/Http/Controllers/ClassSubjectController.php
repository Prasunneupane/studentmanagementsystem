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
use App\Services\ClassSubjectService;
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
    private $classSubjectService;
    public function __construct(
        StudentService $studentService,
        Validation $validation,
        ClassSubjectService $classSubjectService
    )
    {   
        $this->studentService = $studentService;
        $this->validation = $validation;
        $this->classSubjectService = $classSubjectService;
    }
    public function index(Request $request)
    {
        // $academicYearId = $request->input('academic_year_id', DB::table('tbl_academic_years')->where('is_active', 1)->first()?->id);
        // dd($academicYearId);
        $academicYearId = $this->classSubjectService->academicYearById($request);
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $query = $this->classSubjectService->getClassSubjectForAcademicYear($academicYearId);
        // dd($query);
        if ($classId) {
            $query->forClass($classId);
        }

        if ($sectionId) {
            $query->forSection($sectionId);
        }
       
        $assignments = $this->classSubjectService->GetClassSubjectDataWithFilters($query)
                        ->orderBy('id', 'desc')
                        ->get() 
                        ->makeHidden([
                            'class',
                            'section',
                            'subject',
                            'teacher',
                            'academicYear',
                        ]);

        $classes = $this->studentService->getClassList();
           

        $academicYears = $this->studentService->getAcademicYearList();
        return Inertia::render('classSubject/Index', [
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
        $classes = $this->studentService->getClassList();
        $subjects = $this->classSubjectService->getSubjectList();
        $teachers = $this->classSubjectService->getTeacherList();
        $academicYears = $this->studentService->getAcademicYearList(); 
        $currentAcademicYear = $this->classSubjectService->getCurrentAcademicYear();
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
        $this->classSubjectService->create($validated);
        // ClassSubject::create($validated);

        return redirect()->route('class-subjects.index')->with('success', 'Subject assigned successfully');
    }

    /**
     * Show edit form
     */
    public function edit(ClassSubject $classSubject)
    {
        $classes = $this->studentService->getClassList();

        $sections = $this->studentService->getSectionList($classSubject->class_id);

        $subjects = $this->classSubjectService->getSubjectList();

        $teachers = $this->classSubjectService->getTeacherList();

        $academicYears = $this->studentService->getAcademicYearList();
        $classSubjectList = $this->classSubjectService->getClassSubjectById($classSubject->id);
                              
        return Inertia::render('classSubject/Edit', [
            'classSubject' => $classSubjectList,
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
        $validated = $request->validate($this->validation->classSubjectUpdateValidationRules($request, $classSubject->id));
        if ($validated['pass_marks'] > $validated['max_marks']) {
            return back()->withErrors(['pass_marks' => 'Pass marks cannot exceed max marks']);
        }
        $exists = $this->classSubjectService->checkIfDuplicateRecordExistById($classSubject->id);
        if ($exists) {
            return back()->withErrors([
                'subject_id' => 'This subject is already assigned to this class-section for the selected academic year.'
            ]);
        }

       $this->classSubjectService->update($classSubject->id, $validated);

        return redirect()->route('class-subjects.index')->with('success', 'Assignment updated successfully');
    }

    /**
     * Delete assignment
     */
    public function destroy(ClassSubject $classSubject)
    {
        
        $classSubject->is_active = false;
        $classSubject->update();
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