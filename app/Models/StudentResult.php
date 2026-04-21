<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentResult extends Model
{
    protected $table = 'tbl_student_results';

    protected $fillable = [
        'exam_id',
        'student_id',
        'class_id',
        'section_id',
        'academic_year_id',
        'total_marks_obtained',
        'total_max_marks',
        'percentage',
        'grade',
        'gpa',
        'rank',
        'status',
        'is_finalized',
        'finalized_by',
        'finalized_at',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_finalized' => 'boolean',
        'total_marks_obtained' => 'decimal:2',
        'total_max_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
        'gpa' => 'decimal:2',
        'finalized_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYears::class, 'academic_year_id');
    }

    /**
     * Get all individual marks for this result's exam+student
     */
    public function marks()
    {
        return StudentMark::where('exam_id', $this->exam_id)
            ->where('student_id', $this->student_id)
            ->get();
    }
}
