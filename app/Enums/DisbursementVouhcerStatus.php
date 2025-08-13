<?php

namespace App\Enums;

enum DisbursementVouhcerStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case DISAPPROVED = 'disapproved';
    case DISBURSED = 'disbursed';
    case FOR_PAYMENT = 'for_payment';
    case PAID = 'paid';
}
