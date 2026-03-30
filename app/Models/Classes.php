<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    //
    protected $table = "tbl_classes";
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public function sections()
    {
        return $this->belongsToMany(
            Section::class,
            'tbl_class_section',   // pivot table
            'class_id',            // foreign key on pivot for this model
            'section_id'           // foreign key on pivot for related model
        )
        ->wherePivot('is_active', 1)   // only active links
        ->withTimestamps();
    }
}
