<?php

namespace App\Enums;

enum PurchaseRequestStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED_CASH_AVAILABLE = 'approved_cash_available';
    case APPROVED = 'approved';
    case DISAPPROVED = 'disapproved';
    case FOR_CANVASSING = 'for_canvassing';
    case FOR_RECANVASSING = 'for_recanvassing';
    case FOR_ABSTRACT = 'for_abstract';
    case PARTIALLY_AWARDED = 'partially_awarded';
    case AWARDED = 'awarded';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
