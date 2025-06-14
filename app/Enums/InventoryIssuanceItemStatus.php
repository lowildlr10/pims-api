<?php

namespace App\Enums;

enum InventoryIssuanceItemStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DISPOSED = 'disposed';
}
