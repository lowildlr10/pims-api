<?php

namespace App\Interfaces;

use App\Models\AbstractQuotation;

interface AbstractQuotationRepositoryInterface
{
    public function storeUpdate(array $data, ?AbstractQuotation $abstractQuotation);
    public function print(array $pageConfig, string $aoqId);
}
