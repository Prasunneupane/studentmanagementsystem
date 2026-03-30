<?php

namespace App\Http\Controllers;

use App\Interface\ClassTeacherInterface;
use App\Interface\CommonServiceInterface;
use App\Models\ClassTeacher;
use App\Repositories\Validation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassTeacherController extends Controller
{
    private $commonService;
    private $validation;
    private $classTeacherService;

    public function __construct(
        CommonServiceInterface $commonService,
        Validation $validation,
        ClassTeacherInterface $classTeacherService
    ) {
        $this->commonService = $commonService;
        $this->validation = $validation;
        $this->classTeacherService = $classTeacherService;
    }

    /**
     * Display list of class-teacher assignments
     */
    public function index(Request $request)
    {
        $academicYearId = $this->commonService->academicYearById($request->academic_year_id);
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        // 
        $query = $this->classTeacherService->getClassTeacherForAcademicYear($academicYearId);
        // dd($query);
        if ($classId) {
            $query->forClass($classId);
        }

        if ($sectionId) {
            $query->forSection($sectionId);
        }
        // dd($query);
        $assignments = $this->classTeacherService->getClassTeacherDataWithFilters($query)
            ->orderBy('id', 'desc')
            ->get()
            ->makeHidden([
                'class',
                'section',
                'teacher',
                'academicYear',
            ]);
        //  dd($assignments);
        
        $classes = $this->commonService->getClassList();
        $academicYears = $this->commonService->getAcademicYearList();

        return Inertia::render('classTeacher/Index', [
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
        $classes = $this->commonService->getClassList();
        $teachers = $this->commonService->getTeacherList();
        $academicYears = $this->commonService->getAcademicYearList();
        $currentAcademicYear = $this->commonService->getCurrentAcademicYear();
        return Inertia::render('classTeacher/Create', [
            'classes' => $classes,
            'teachers' => $teachers,
            'academicYears' => $academicYears,
            'currentAcademicYear' => $currentAcademicYear ?? null,
        ]);
    }

    /**
     * Store new assignment
     */
    public function store(Request $request)
    {
        // Validation rules
       

        $validated = $request->validate($this->validation->classTeacherValidationRules($request));
        // Check for duplicate
        $this->classTeacherService->checkDuplicateAssignment(
           $validated
        );
        // If this teacher is marked as class teacher, unset any existing class teacher
        if ($validated['is_class_teacher']) {
            $this->classTeacherService->unsetExistingClassTeacher(
                $validated['class_id'],
                $validated['section_id'],
                $validated['academic_year_id']
            );
        }

        $this->classTeacherService->create($validated);

        return redirect()->route('class-teacher.index')->with('success', 'Teacher assigned successfully');
    }

    /**
     * Show edit form
     */
    public function edit(ClassTeacher $classTeacher)
    {
        $classes = $this->commonService->getClassList();
        $sections = $this->commonService->getSectionList($classTeacher->class_id);
        $teachers = $this->commonService->getTeacherList();
        $academicYears = $this->commonService->getAcademicYearList();
        $classTeacherData = $this->commonService->getClassTeacherById($classTeacher->id);

        return Inertia::render('classTeacher/Edit', [
            'classTeacher' => $classTeacherData,
            'classes' => $classes,
            'sections' => $sections,
            'teachers' => $teachers,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update assignment
     */
    public function update(Request $request, ClassTeacher $classTeacher)
    {
        // Validation rules
        $rules = $this->validation->classTeacherUpdateValidationRules($request, $classTeacher->id);   
        $validated = $request->validate($rules);
        // Check for duplicate (excluding current record)
        $exists = $this->classTeacherService->checkDuplicateAssignmentForUpdate(
            $validated,$classTeacher->id
        );
        // If this teacher is marked as class teacher, unset any existing class teacher
        if ($validated['is_class_teacher']) {
            $this->classTeacherService->unsetExistingClassTeacherForUpdate(
                $validated,$classTeacher->id);
        }

        $this->classTeacherService->update($classTeacher->id, $validated);

        return redirect()->route('class-teacher.index')->with('success', 'Assignment updated successfully');
    }

    /**
     * Delete assignment
     */
    public function destroy(ClassTeacher $classTeacher)
    {
        $classTeacher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully'
        ]);
    }

    /**
     * Get sections by class ID
     */
    public function getSectionsByClass(Request $request)
    {
        // dd($request->all());
        $sections = $this->commonService->getSectionList($request->input('class_id'));
        return response()->json($sections);
    }
}