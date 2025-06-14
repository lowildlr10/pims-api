<?php

namespace App\Interfaces;

use App\Models\Supply;

interface SupplyInterface
{
    public function storeUpdate(array $data, ?Supply $supply);
}
