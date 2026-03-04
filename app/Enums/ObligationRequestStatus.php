<?php

namespace App\Enums;

enum ObligationRequestStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case DISAPPROVED = 'disapproved';
    case OBLIGATED = 'obligated';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending for Obligation',
            self::DISAPPROVED => 'Disapproved',
            self::OBLIGATED => 'Obligated',
        };
    }
}
