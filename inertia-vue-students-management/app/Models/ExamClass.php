<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamClass extends Model
{
    //
    protected $table = 'tbl_exam_classes';
    protected $fillable = [
        'exam_id',
        'class_id',
        'section_id',
    ];
}
