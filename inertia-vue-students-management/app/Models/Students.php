<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Students extends Model
{
    use SoftDeletes;
    protected $table = "students";
    protected $primaryKey = "id";
    public $timestamps = true;

    protected $appends = ['photo_url'];

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'age',
        'date_of_birth',
        'class_id',
        'section_id',
        'mother_name',
        'father_name',
        'guardian_name',
        'contact_number',
        'photo',
        'joined_date',
        'address',
        'state_id',
        'district_id',
        'municipality_id',
        'created_by',
    ];
    

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

    public function getClassNameAttribute(): ?string
    {
        return $this->class?->name;
    }

    public function getSectionNameAttribute(): ?string
    {
        return $this->section?->name;
    }

    public function guardians()
    {
        return $this->hasMany(Guardian::class, 'student_id');
    }
    public function primaryGuardian()
    {
        return $this->hasOne(Guardian::class, 'student_id')
            ->where('is_primary_contact', true);
    }
}
