<?php

namespace App\Services;

use App\Interfaces\SupplierRepositoryInterface;
use App\Models\Supplier;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function __construct(
        protected SupplierRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?Supplier
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): Supplier
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $supplier = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Supplier created successfully.',
            'log_id' => $supplier->id,
            'log_module' => 'lib-supplier',
            'data' => $supplier,
        ]);

        return $supplier;
    }

    public function update(string $id, array $data): Supplier
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $supplier = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Supplier updated successfully.',
            'log_id' => $supplier->id,
            'log_module' => 'lib-supplier',
            'data' => $supplier,
        ]);

        return $supplier;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-supplier',
            'data' => $data,
        ], isError: true);
    }
}
