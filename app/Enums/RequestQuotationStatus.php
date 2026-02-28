<?php

namespace App\Enums;

enum RequestQuotationStatus: string
{
    case DRAFT = 'draft';
    case CANVASSING = 'canvassing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::CANVASSING => 'Canvassing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }
}
