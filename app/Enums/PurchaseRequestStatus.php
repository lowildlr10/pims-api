<?php

namespace App\Enums;

enum PurchaseRequestStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED_CASH_AVAILABLE = 'approved_cash_available';
    case APPROVED = 'approved';
    case DISAPPROVED = 'disapproved';
    case CANCELLED = 'cancelled';
    case FOR_CANVASSING = 'for_canvassing';
    case FOR_ABSTRACT = 'for_abstract';
    case FOR_PO = 'for_po';
    case COMPLETED = 'completed';
}
