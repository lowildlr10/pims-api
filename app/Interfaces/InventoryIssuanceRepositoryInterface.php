<?php

namespace App\Interfaces;

interface InventoryIssuanceRepositoryInterface
{
    public function generateNewInventoryNumber(string $documentType): string;

    public function print(array $pageConfig, string $prId);
}
