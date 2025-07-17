<?php

namespace App\Enums;

enum NotificationType: string
{
    case PR_PENDING = 'pr-pending';
    case PR_APPROVED_CASH_AVAILABLE = 'pr-approved-cash-available';
    case PR_APPROVED = 'pr-approved';
    case PR_DISAPPROVED = 'pr-disapproved';
    case PR_CANCELLED = 'pr-cancelled';
    case PR_CAMVASSING = 'pr-canvassing';
    case PR_FOR_ABSTRACT = 'pr-for-abstract';
    case PR_PARTIALLY_AWARDED = 'pr-partially-awarded';
    case PR_AWARDED = 'pr-awarded';
    case UNDEFINED = '';
}
