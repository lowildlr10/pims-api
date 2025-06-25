<?php

namespace App\Interfaces;

interface PurchaseRequestRepositoryInterface
{
    public function generateNewPrNumber(): string;

    public function print(array $pageConfig, string $prId);
}
