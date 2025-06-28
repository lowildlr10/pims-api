<?php

namespace App\Enums;

enum InspectionAcceptanceReportStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case INSPECTED = 'inspected';
    case PARTIALLY_ACCEPTED = 'partially_accepted';
    case ACCEPTED = 'accepted';
}
