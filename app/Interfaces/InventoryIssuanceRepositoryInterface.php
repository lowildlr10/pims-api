<?php

namespace App\Interfaces;

use App\Enums\DocumentPrintType;
use App\Models\InventoryIssuance;

interface InventoryIssuanceRepositoryInterface
{
    public function storeUpdate(array $data, ?InventoryIssuance $inventoryIssuance);

    public function generateNewInventoryNumber(string $documentType): string;

    public function print(array $pageConfig, string $invId, DocumentPrintType $documentType);
}
