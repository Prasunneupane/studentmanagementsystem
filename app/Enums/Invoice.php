<?php

namespace App\Enums;

enum Invoice:string
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::PARTIAL => 'Partial',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::UNPAID => 'red',
            self::PARTIAL => 'orange',
            self::PAID => 'green',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
        };
    }
}
