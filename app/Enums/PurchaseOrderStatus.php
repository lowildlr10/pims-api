<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ISSUED = 'issued';
    case FOR_DELIVERY = 'for_delivery';
    case DELIVERED = 'delivered';
    case FOR_INSPECTION = 'for_inspection';
    case INSPECTED = 'inspected';
    case FOR_OBLIGATION = 'for_obligation';
    case OBLIGATED = 'obligated';
    case FOR_DISBURSEMENT = 'for_disbursement';
    case FOR_PAYMENT = 'for_payment';
    case COMPLETED = 'completed';
}
