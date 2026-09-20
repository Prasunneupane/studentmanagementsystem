<?php

namespace App\Enums;

enum PaymentMethod:string
{
    case CASH = 'cash';
    case CARD = 'card';
    case CHEQUE = 'cheque';
    // case PAYPAL = 'paypal';
    case BANK_TRANSFER = 'bank_transfer';
    case ONLINE_PAYMENT = 'online_payment';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CARD => 'Card',
            self::CHEQUE => 'Cheque',
            // self::PAYPAL => 'PayPal',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::ONLINE_PAYMENT => 'Online Payment',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CASH => 'fa-solid fa-money-bill',
            self::CARD => 'fa-solid fa-credit-card',
            self::CHEQUE => 'fa-solid fa-file-invoice',
            // self::PAYPAL => 'fa-brands fa-paypal',
            self::BANK_TRANSFER => 'fa-solid fa-university',
            self::ONLINE_PAYMENT => 'fa-solid fa-globe',
        };
    }
}
