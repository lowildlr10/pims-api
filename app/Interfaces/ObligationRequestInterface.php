<?php

namespace App\Interfaces;

use App\Models\ObligationRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ObligationRequestInterface
{
    public function getAll(array $filters, ?string $userId = null): LengthAwarePaginator;

    public function getById(string $id): ?ObligationRequest;

    public function storeUpdate(array $data, ?ObligationRequest $obligationRequest = null): ObligationRequest;

    public function print(array $pageConfig, string $obrId);
}
