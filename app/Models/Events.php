<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\EventStatus;
use App\Enums\EventCategory;
class Events extends Model
{
    protected $table = 'tbl_events';

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'event_type',
        'status',
        'banner_image',
        'created_by',
        'is_active',
    ];

    // Automatically casts string to Enum
    protected $casts = [
        'status' => EventStatus::class,    
        'enum_type' => EventCategory::class, 
    ];
    public function gallery()
    {
        return $this->hasMany(EventsGallery::class, 'event_id');
    }

    // Define any relationships or additional methods as needed
}
