<?php

namespace App\Enums;

enum TransactionType: string
{
    case PROCUREMENT = 'procurement';
    case BILLS_PAYMENT = 'bills_payment';

    public function label(): string
    {
        return match ($this) {
            self::PROCUREMENT => 'Procurement',
            self::BILLS_PAYMENT => 'Bills Payment',
        };
    }
}
