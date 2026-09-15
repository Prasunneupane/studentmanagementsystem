<?php

namespace App\Enums;

enum PaymentMethod:string
{
    case CASH = 'cash';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case PAYPAL = 'paypal';
    case BANK_TRANSFER = 'bank_transfer';
    case ONLINE_PAYMENT = 'online_payment';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CREDIT_CARD => 'Credit Card',
            self::DEBIT_CARD => 'Debit Card',
            self::PAYPAL => 'PayPal',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::ONLINE_PAYMENT => 'Online Payment',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CASH => 'fa-solid fa-money-bill',
            self::CREDIT_CARD => 'fa-solid fa-credit-card',
            self::DEBIT_CARD => 'fa-solid fa-credit-card',
            self::PAYPAL => 'fa-brands fa-paypal',
            self::BANK_TRANSFER => 'fa-solid fa-university',
            self::ONLINE_PAYMENT => 'fa-solid fa-globe',
        };
    }
}
