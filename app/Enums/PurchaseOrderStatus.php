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
    case INSPECTION = 'inspection';
    case OBLIGATION = 'obligation';
    case DISBURSEMENT = 'disbursement';
    case PAYMENT = 'payment';
    case COMPLETED = 'completed';
}
