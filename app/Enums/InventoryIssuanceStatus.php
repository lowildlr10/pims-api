<?php

namespace App\Enums;

enum InventoryIssuanceStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case ISSUED = 'issued';
    case CANCELLED = 'cancelled';
}
