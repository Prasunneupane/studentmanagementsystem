<?php

namespace App\Enums;

enum PaymentGateway:string
{
    case ESEWA = 'esewa';
    case KHALTI = 'khalti';
    // case PAYPAL = 'paypal';
    public function label(): string
    {
        return match ($this) {
            self::ESEWA => 'Esewa',
            self::KHALTI => 'Khalti',
            // self::PAYPAL => 'PayPal',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ESEWA => 'fa-solid fa-money-bill',
            self::KHALTI => 'fa-solid fa-credit-card',
        };
    }
}
