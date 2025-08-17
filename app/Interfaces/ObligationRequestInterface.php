<?php

namespace App\Interfaces;

use App\Models\ObligationRequest;

interface ObligationRequestInterface
{
    public function storeUpdate(array $data, ?ObligationRequest $obligationRequest);

    public function print(array $pageConfig, string $obrId);
}
