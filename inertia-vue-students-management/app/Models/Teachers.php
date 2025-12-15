<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Storage;

class Teachers extends Model
{
    protected $table = 'tbl_teachers';

    protected $fillable = [
        'name',
        'email',
        'subject',
        'status',
        'phone',
        'address',
        'subject_specializtion',
        'joining_date',
        'leaving_date',
        'photo',
        'date_of_birth',
        'is_active',
        'qualification',
        'created_by',
    ];
    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute()
{
    return $this->photo
        ? Storage::disk('public')->url($this->photo)
        : asset('images/default-avatar.png');
}
}
