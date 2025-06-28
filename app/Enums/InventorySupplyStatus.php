<?php

namespace App\Enums;

enum InventorySupplyStatus: string
{
    case IN_STOCK = 'in_stock';
    case OUT_OF_STOCK = 'out_of_stock';
}
