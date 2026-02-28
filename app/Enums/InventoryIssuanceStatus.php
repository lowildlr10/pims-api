<?php

namespace App\Enums;

enum InventoryIssuanceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ISSUED = 'issued';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::ISSUED => 'Issued',
            self::CANCELLED => 'Cancelled',
        };
    }
}
