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
            $inventorySupply = InventorySupply::updateOrCreate(
                [
                    'purchase_order_id' => $data['purchase_order_id'],
                    'po_item_id'        => $data['po_item_id'],
                ],
                $data
            );
        }

        return $inventorySupply;
    }
}
