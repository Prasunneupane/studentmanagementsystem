<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NepaliCalendar extends Model
{
    protected $table = 'tbl_nepali_calendar';
      protected $guarded = ['id'];

      // app/Models/NepaliCalendar.php
    protected $casts = [
        'start_ad_date' => 'date:Y-m-d',   // ← this line
        'end_ad_date'   => 'date:Y-m-d',
    ];
}
