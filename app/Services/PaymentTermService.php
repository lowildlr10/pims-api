<?php

namespace App\Services;

use App\Interfaces\PaymentTermRepositoryInterface;
use App\Models\PaymentTerm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentTermService
{
    public function __construct(
        protected PaymentTermRepositoryInterface $repository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?PaymentTerm
    {
        return $this->repository->getById($id);
    }
}
