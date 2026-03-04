<?php

namespace App\Enums;

enum InspectionAcceptanceReportStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case INSPECTED = 'inspected';
    case PARTIALLY_ACCEPTED = 'partially_accepted';
    case ACCEPTED = 'accepted';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending for Inspection',
            self::INSPECTED => 'Inspected',
            self::PARTIALLY_ACCEPTED => 'Partially Accepted',
            self::ACCEPTED => 'Accepted',
        };
    }
}
