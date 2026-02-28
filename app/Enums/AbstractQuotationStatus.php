<?php

namespace App\Enums;

enum AbstractQuotationStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case AWARDED = 'awarded';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::AWARDED => 'Awarded',
        };
    }
}
