<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    protected $table = 'tbl_exam_schedules';
    protected $fillable = [
        'exam_id',
        'class_id',
        'section_id',
        'subject_id',
        'exam_date',
        'start_time',
        'end_time',
        'room_no',
        'max_theory_marks',
        'max_practical_marks',
        'max_total_marks',
        'pass_marks',
    ];
}
