<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    use HasFactory;
    protected $table = "tbl_guardians";
    protected $fillable = [
        'student_id',
        'name',
        'relation',
        'phone',
        'email',
        'occupation',
        'address',
        'is_primary_contact',
    ];

    public function student()
    {
        return $this->belongsTo(Students::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
