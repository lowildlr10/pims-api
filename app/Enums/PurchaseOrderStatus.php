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

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::ISSUED => 'Issued',
            self::FOR_DELIVERY => 'For Delivery',
            self::DELIVERED => 'Delivered',
            self::FOR_INSPECTION => 'For Inspection',
            self::INSPECTED => 'Inspected',
            self::FOR_OBLIGATION => 'For Obligation',
            self::OBLIGATED => 'Obligated',
            self::FOR_DISBURSEMENT => 'For Disbursement',
            self::FOR_PAYMENT => 'For Payment',
            self::COMPLETED => 'Completed',
        };
    }
}
