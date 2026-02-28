<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface PositionRepositoryInterface
{
    public function getAll(array $filters): LengthAwarePaginator;

    public function getById(string $id): ?Model;

    public function create(array $data): Model;

    public function update(string $id, array $data): Model;

    public function delete(string $id): bool;

    public function getModel(): string;
}
