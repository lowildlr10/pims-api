<?php

namespace App\Enums;

enum DisbursementVoucherStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case DISAPPROVED = 'disapproved';
    case FOR_PAYMENT = 'for_payment';
    case PAID = 'paid';
}
