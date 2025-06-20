<?php

namespace App\Interfaces;

use App\Models\InventorySupply;

interface InventorySupplyInterface
{
    public function storeUpdate(array $data, ?InventorySupply $inventorySupply);
}
