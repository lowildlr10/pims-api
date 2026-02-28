<?php

namespace App\Services;

use App\Interfaces\ItemClassificationRepositoryInterface;
use App\Models\ItemClassification;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ItemClassificationService
{
    public function __construct(
        protected ItemClassificationRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?ItemClassification
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): ItemClassification
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $itemClassification = $this->repository->create($data);
        $this->logRepository->create([
            'message' => 'Item classification created successfully.',
            'log_id' => $itemClassification->id,
            'log_module' => 'lib-item-class',
            'data' => $itemClassification,
        ]);

        return $itemClassification;
    }

    public function update(string $id, array $data): ItemClassification
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $itemClassification = $this->repository->update($id, $data);
        $this->logRepository->create([
            'message' => 'Item classification updated successfully.',
            'log_id' => $itemClassification->id,
            'log_module' => 'lib-item-class',
            'data' => $itemClassification,
        ]);

        return $itemClassification;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-item-class',
            'data' => $data,
        ], isError: true);
    }
}
