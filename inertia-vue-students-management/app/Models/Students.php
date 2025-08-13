<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Students extends Model
{
    protected $table = "tbl_students";
    protected $primaryKey = "student_id";
    public $timestamps = true;
    

    public function getFullNameAttribute()
    {
        return $this->middle_name ? "{$this->first_name} {$this->middle_name} {$this->last_name}" : "{$this->first_name} {$this->last_name}";
    }
    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id','id');
    }
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'id');
    }
    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'municipality_id', 'id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/' . $this->photo) : asset('images/default-avatar.png');
    }
}
