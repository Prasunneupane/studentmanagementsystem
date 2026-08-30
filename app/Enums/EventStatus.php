<?php

namespace App\Enums;

enum EventStatus: string
{
    case DRAFT = 'draft';
    case UPCOMING = 'upcoming';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case POSTPONED = 'postponed';

    // Get a human-readable label
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::UPCOMING => 'Upcoming',
            self::ONGOING => 'Ongoing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::POSTPONED => 'Postponed',
        };
    }

    // Get Bootstrap/Tailwind color classes for UI
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::UPCOMING => 'primary',
            self::ONGOING => 'success',
            self::COMPLETED => 'info',
            self::CANCELLED => 'danger',
            self::POSTPONED => 'warning',
        };
    }
}
