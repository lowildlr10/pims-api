<?php

namespace App\Interfaces;

use App\Models\InventorySupply;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InventorySupplyInterface
{
    public function storeUpdate(array $data, ?InventorySupply $inventorySupply = null): InventorySupply;

    public function getAll(array $filters): LengthAwarePaginator|Collection;

    public function getById(string $id): ?InventorySupply;

    public function update(string $id, array $data): InventorySupply;
}
