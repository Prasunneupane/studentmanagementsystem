<?php

namespace App\Enums;

enum EventCategory: string
{
    case ACADEMIC = 'academic';
    case EXAMINATION = 'examination';
    case HOLIDAY = 'holiday';
    case SPORTS = 'sports';
    case MEETING = 'meeting';

    public function label(): string
    {
        return match ($this) {
            self::ACADEMIC => 'Academic',
            self::EXAMINATION => 'Examination',
            self::HOLIDAY => 'Holiday',
            self::SPORTS => 'Sports',
            self::MEETING => 'Meeting',
        };
    }
}
