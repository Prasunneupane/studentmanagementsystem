<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMark extends Model
{
    protected $table = 'tbl_student_marks';

    protected $fillable = [
        'exam_id',
        'exam_schedule_id',
        'student_id',
        'class_id',
        'section_id',
        'subject_id',
        'academic_year_id',
        'theory_marks',
        'practical_marks',
        'total_marks',
        'is_absent',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_absent' => 'boolean',
        'theory_marks' => 'decimal:2',
        'practical_marks' => 'decimal:2',
        'total_marks' => 'decimal:2',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function examSchedule()
    {
        return $this->belongsTo(ExamSchedule::class, 'exam_schedule_id');
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

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYears::class, 'academic_year_id');
    }
}
