<?php

namespace App\Enums;

enum PaymentGateway:string
{
    case ESEWA = 'esewa';
    case KHALTI = 'khalti';
    case FONEPAY = 'fonepay';

    public function label(): string
    {
        return match ($this) {
            self::ESEWA => 'eSewa',
            self::KHALTI => 'Khalti',
            self::FONEPAY => 'Fonepay',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ESEWA => 'fa-solid fa-money-bill',
            self::KHALTI => 'fa-solid fa-credit-card',
            self::FONEPAY => 'fa-solid fa-qrcode',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ESEWA => '#60BB46',
            self::KHALTI => '#5C2D91',
            self::FONEPAY => '#ED1C24',
        };
    }

    /**
     * How the payer completes the transaction: a hosted checkout opened in a
     * new tab, or an in-page QR to scan. Drives which UI the payment modal
     * renders without the frontend needing a per-gateway switch statement.
     */
    public function sessionType(): string
    {
        return match ($this) {
            self::ESEWA, self::KHALTI => 'redirect',
            self::FONEPAY => 'qr',
        };
    }
}
