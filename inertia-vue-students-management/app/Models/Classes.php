<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    //
    protected $table = "classes";
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
}
