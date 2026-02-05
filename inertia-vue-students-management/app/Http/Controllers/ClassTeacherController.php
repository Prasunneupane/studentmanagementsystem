<?php

namespace App\Http\Controllers;

use App\Models\ClassTeacher;
use App\Repositories\Validation;

use App\Services\ClassTeacherService;
use App\Services\CommonServices;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassTeacherController extends Controller
{
    private $commonService;
    private $validation;
    private $classTeacherService;

    public function __construct(
        CommonServices $commonService,
        Validation $validation,
        ClassTeacherService $classTeacherService
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

        $query = $this->classTeacherService->getClassTeacherForAcademicYear($academicYearId);

        if ($classId) {
            $query->forClass($classId);
        }

        if ($sectionId) {
            $query->forSection($sectionId);
        }

        $assignments = $this->classTeacherService->getClassTeacherDataWithFilters($query)
            ->orderBy('id', 'desc')
            ->get()
            ->makeHidden([
                'class',
                'section',
                'teacher',
                'academicYear',
            ]);

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
        $currentAcademicYear = $this->classTeacherService->getCurrentAcademicYear();

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
        $rules = [
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'required|exists:tbl_sections,id',
            'teacher_id' => 'required|exists:tbl_teachers,id',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'is_class_teacher' => 'boolean',
            'is_active' => 'boolean',
        ];

        $validated = $request->validate($rules);

        // Set defaults
        $validated['is_class_teacher'] = $validated['is_class_teacher'] ?? false;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['created_by'] = auth()->id();

        // Check for duplicate
        $exists = ClassTeacher::where('class_id', $validated['class_id'])
            ->where('section_id', $validated['section_id'])
            ->where('teacher_id', $validated['teacher_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'teacher_id' => 'This teacher is already assigned to this class-section for the selected academic year.'
            ]);
        }

        // If this teacher is marked as class teacher, unset any existing class teacher
        if ($validated['is_class_teacher']) {
            ClassTeacher::where('class_id', $validated['class_id'])
                ->where('section_id', $validated['section_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->update(['is_class_teacher' => false]);
        }

        $this->classTeacherService->create($validated);

        return redirect()->route('class-teachers.index')->with('success', 'Teacher assigned successfully');
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
        $classTeacherData = $this->classTeacherService->getClassTeacherById($classTeacher->id);

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
        $rules = [
            'class_id' => 'required|exists:tbl_classes,id',
            'section_id' => 'required|exists:tbl_sections,id',
            'teacher_id' => 'required|exists:tbl_teachers,id',
            'academic_year_id' => 'required|exists:tbl_academic_years,id',
            'is_class_teacher' => 'boolean',
            'is_active' => 'boolean',
        ];

        $validated = $request->validate($rules);
        $validated['updated_by'] = auth()->id();

        // Check for duplicate (excluding current record)
        $exists = ClassTeacher::where('class_id', $validated['class_id'])
            ->where('section_id', $validated['section_id'])
            ->where('teacher_id', $validated['teacher_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('id', '!=', $classTeacher->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'teacher_id' => 'This teacher is already assigned to this class-section for the selected academic year.'
            ]);
        }

        // If this teacher is marked as class teacher, unset any existing class teacher
        if ($validated['is_class_teacher']) {
            ClassTeacher::where('class_id', $validated['class_id'])
                ->where('section_id', $validated['section_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->where('id', '!=', $classTeacher->id)
                ->update(['is_class_teacher' => false]);
        }

        $this->classTeacherService->update($classTeacher->id, $validated);

        return redirect()->route('class-teachers.index')->with('success', 'Assignment updated successfully');
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
        $sections = $this->commonService->getSectionList($request->input('class_id'));
        return response()->json($sections);
    }
}