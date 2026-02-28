<?php

namespace App\Services;

use App\Interfaces\ProcurementModeRepositoryInterface;
use App\Models\ProcurementMode;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProcurementModeService
{
    public function __construct(
        protected ProcurementModeRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?ProcurementMode
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): ProcurementMode
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $procurementMode = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Mode of procurement created successfully.',
            'log_id' => $procurementMode->id,
            'log_module' => 'lib-mode-proc',
            'data' => $procurementMode,
        ]);

        return $procurementMode;
    }

    public function update(string $id, array $data): ProcurementMode
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $procurementMode = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Mode of procurement updated successfully.',
            'log_id' => $procurementMode->id,
            'log_module' => 'lib-mode-proc',
            'data' => $procurementMode,
        ]);

        return $procurementMode;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-mode-proc',
            'data' => $data,
        ], isError: true);
    }
}
