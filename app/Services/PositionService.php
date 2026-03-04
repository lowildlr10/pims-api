<?php

namespace App\Services;

use App\Interfaces\PositionRepositoryInterface;
use App\Models\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PositionService
{
    public function __construct(
        protected PositionRepositoryInterface $repository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?Position
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): Position
    {
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Position
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
