<?php

namespace App\Interfaces;

use App\Models\TaxWithholding;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TaxWithholdingRepositoryInterface
{
    public function getAll(array $filters): LengthAwarePaginator|Collection;

    public function getById(string $id): ?TaxWithholding;

    public function create(array $data): TaxWithholding;

    public function update(string $id, array $data): TaxWithholding;
}
