<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ISSUED = 'issued';
    case RECEIVED = 'received';
    case FOR_INSPECTION = 'for_inspection';
    case FOR_OBLIGATION = 'for_obligation';
    case FOR_DISBURSEMENT = 'for_disbursement';
    case FOR_PAYMENT = 'for_payment';
    case COMPLETED = 'completed';
}
