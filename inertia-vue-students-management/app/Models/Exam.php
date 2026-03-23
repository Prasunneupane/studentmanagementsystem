<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $table = 'tbl_exams';

    protected $fillable = [
        'name',
        'exam_type',
        'academic_year_id',
        'term_id',
        'start_date',
        'end_date',
        'weightage',
        'created_by',
        'is_published'
    ];

    public function examClasses()
    {
        return $this->hasMany(ExamClass::class, 'exam_id');
    }
}
