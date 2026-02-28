<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface PurchaseRequestRepositoryInterface
{
    public function getAll(array $filters, ?string $userId): LengthAwarePaginator|Collection;

    public function getById(string $id): ?Model;

    public function create(array $data): Model;

    public function update(string $id, array $data): Model;

    public function generateNewPrNumber(): string;

    public function print(array $pageConfig, string $prId);
}
