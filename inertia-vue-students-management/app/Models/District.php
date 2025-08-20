<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    //
    protected $table = "districts";

    public function municipalities(){
        return $this->hasMany(Municipality::class, 'district_id');
    }
}
