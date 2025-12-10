<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    //
    protected $table = "tbl_subjects";

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
        'description',
    ];
}
