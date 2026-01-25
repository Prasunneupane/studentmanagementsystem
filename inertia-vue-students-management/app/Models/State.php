<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    //
    protected $table = "tbl_states";

    public function districts()
    {
        return $this->hasMany(District::class, 'state_id');
    }
}
