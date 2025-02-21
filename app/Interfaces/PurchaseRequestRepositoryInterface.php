<?php

namespace App\Interfaces;

interface PurchaseRequestRepositoryInterface
{
    public function print(array $pageConfig, string $prId);
}
