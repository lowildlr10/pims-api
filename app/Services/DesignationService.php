<?php

namespace App\Services;

use App\Interfaces\DesignationRepositoryInterface;
use App\Models\Designation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DesignationService
{
    public function __construct(
        protected DesignationRepositoryInterface $repository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?Designation
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): Designation
    {
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Designation
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
