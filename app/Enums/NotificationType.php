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
    case PR_COMPLETED = 'pr-completed';
    case PO_PENDING = 'po-pending';
    case PO_APPROVED = 'po-approved';
    case PO_ISSUED = 'po-issued';
    case PO_FOR_DELIVERY = 'po-for-delivery';
    case PO_DELIVERED = 'po-delivered';
    case PO_FOR_INSPECTION = 'po-for-inspection';
    case PO_INSPECTED = 'po-inspected';
    case PO_FOR_OBLIGATION = 'po-for-obligation';
    case PO_OBLIGATED = 'po-obligated';
    case PO_FOR_DISBURSEMENT = 'po-for-disbursement';
    case PO_FOR_PAYMENT = 'po-for-payment';
    case PO_COMPLETED = 'po-completed';
    case OBR_CREATED = 'obr-created';
    case DV_CREATED = 'dv-created';
    case DV_FOR_PAYMENT = 'dv-for-payment';
    case INV_ISSUANCE_ISSUED = 'inv-issuance-issued';
    case UNDEFINED = '';
}
