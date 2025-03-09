<?php

namespace App\Enums;

enum AbstractQuotationStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case AWARDED = 'awarded';
}
