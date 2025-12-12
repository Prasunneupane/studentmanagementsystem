<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teachers extends Model
{
    protected $table = 'tbl_teachers';

    protected $fillable = [
        'name',
        'email',
        'subject',
        'status',
    ];
}
