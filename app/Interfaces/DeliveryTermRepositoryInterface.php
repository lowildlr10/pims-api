<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface DeliveryTermRepositoryInterface
{
    public function getAll(array $filters): LengthAwarePaginator;

    public function getById(string $id): ?Model;

    public function getModel(): string;
}
