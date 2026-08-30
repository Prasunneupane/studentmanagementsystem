<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventsGallery extends Model
{
    protected $table = 'tbl_event_gallery';

    // protected $fillable = [
    //     'event_id',
    //     'image_path',
    //     'caption',
    // ];

    public function event()
    {
        return $this->belongsTo(Events::class, 'event_id');
    }
}
