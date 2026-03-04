<?php

namespace App\Enums;

enum DisbursementVoucherStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case DISAPPROVED = 'disapproved';
    case FOR_PAYMENT = 'for_payment';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending for Disbursement',
            self::DISAPPROVED => 'Disapproved',
            self::FOR_PAYMENT => 'For Payment',
            self::PAID => 'Paid',
        };
    }
}
