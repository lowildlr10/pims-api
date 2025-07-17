<?php

namespace App\Repositories;

use App\Interfaces\InventorySupplyInterface;
use App\Models\InventorySupply;

class InventorySupplyRepository implements InventorySupplyInterface
{
    public function storeUpdate(array $data, ?InventorySupply $inventorySupply = null): InventorySupply
    {
        if (! empty($inventorySupply)) {
            $inventorySupply->update($data);
        } else {
            $inventorySupply = InventorySupply::create($data);
        }

        return $inventorySupply;
    }
}
