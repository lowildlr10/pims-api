<?php

namespace App\Interfaces;

use App\Enums\DocumentPrintType;
use App\Models\InventoryIssuance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InventoryIssuanceRepositoryInterface
{
    public function getAll(array $filters): LengthAwarePaginator|Collection;

    public function getById(string $id): ?InventoryIssuance;

    public function storeUpdate(array $data, ?InventoryIssuance $inventoryIssuance): InventoryIssuance;

    public function generateNewInventoryNumber(string $documentType): string;

    public function print(array $pageConfig, string $invId, DocumentPrintType $documentType);
}
