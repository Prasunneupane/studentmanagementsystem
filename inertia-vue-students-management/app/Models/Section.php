<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table = "tbl_section";
    protected $primaryKey = 'id';

    public function classes(){
        return $this->belongsToMany(Classes::class,'tbl_class_section','section_id','class_id')
        ->wherePivot('is_active',true)
        ->withTimestamps();    
    }
    public function mapping(){
        return $this->hasMany('tbl_class_section','section_id','id')
        ->where('is_active',true);
    }
    
}
