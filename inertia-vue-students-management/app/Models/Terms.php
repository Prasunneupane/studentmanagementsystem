<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Terms extends Model
{
    protected $table='tbl_terms';


    protected $fillable = [
        'name',
        'academic_year_id',
        'term_number',
        'start_date',
        'end_date',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYears::class, 'academic_year_id');
    }
}
