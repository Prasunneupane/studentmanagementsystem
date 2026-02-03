<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSubject extends Model
{
    use HasFactory;

    protected $table = 'tbl_class_subject';

    protected $fillable = [
        'class_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'is_optional',
        'periods_per_week',
        'max_marks',
        'pass_marks',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'periods_per_week' => 'integer',
        'max_marks' => 'decimal:2',
        'pass_marks' => 'decimal:2',
    ];

    protected $appends = [
    'class_name',
    'section_name',
    'subject_name',
    'teacher_name',
    ];




    public $timestamps = true; // Since we only have created_at

    // Relationships
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

    public function teacher()
    {
        return $this->belongsTo(Teachers::class, 'teacher_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYears::class, 'academic_year_id', 'id');
    }

    // Scopes
    public function scopeForAcademicYear($query, $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_optional', false);
    }

    public function getClassNameAttribute()
    {
        return $this->class?->name;
    }

    public function getSectionNameAttribute()
    {
        return $this->section?->name;
    }

    public function getSubjectNameAttribute()
    {
        return $this->subject?->name;
    }
    public function getTeacherNameAttribute()
    {
        return $this->teacher?->name;
    }
}