<?php

namespace App\Interfaces;

use App\Models\AbstractQuotation;
use Illuminate\Http\Request;

interface AbstractQuotationRepositoryInterface
{
    public function storeUpdate(array $data, ?AbstractQuotation $abstractQuotation);
    public function print(array $pageConfig, string $aoqId);
}
