<?php

namespace App\Services;

use App\Interfaces\DeliveryTermRepositoryInterface;
use App\Models\DeliveryTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryTermService
{
    public function __construct(
        protected DeliveryTermRepositoryInterface $repository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?DeliveryTerm
    {
        return $this->repository->getById($id);
    }
}
