<?php

namespace App\Interfaces;

use App\Models\PurchaseOrder;

interface PurchaseOrderRepositoryInterface
{
    public function storeUpdate(array $data, ?PurchaseOrder $purchaseOrder);

    public function print(array $pageConfig, string $prId);
}
