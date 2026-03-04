<?php

namespace App\Services;

use App\Interfaces\LocationRepositoryInterface;
use App\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LocationService
{
    public function __construct(
        protected LocationRepositoryInterface $repository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?Location
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): Location
    {
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Location
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
