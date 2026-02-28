<?php

namespace App\Services;

use App\Interfaces\ResponsibilityCenterRepositoryInterface;
use App\Models\ResponsibilityCenter;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ResponsibilityCenterService
{
    public function __construct(
        protected ResponsibilityCenterRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?ResponsibilityCenter
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): ResponsibilityCenter
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $responsibilityCenter = $this->repository->create($data);
        $this->logRepository->create([
            'message' => 'Responsibility center created successfully.',
            'log_id' => $responsibilityCenter->id,
            'log_module' => 'lib-resp-center',
            'data' => $responsibilityCenter,
        ]);

        return $responsibilityCenter;
    }

    public function update(string $id, array $data): ResponsibilityCenter
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $responsibilityCenter = $this->repository->update($id, $data);
        $this->logRepository->create([
            'message' => 'Responsibility center updated successfully.',
            'log_id' => $responsibilityCenter->id,
            'log_module' => 'lib-resp-center',
            'data' => $responsibilityCenter,
        ]);

        return $responsibilityCenter;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-resp-center',
            'data' => $data,
        ], isError: true);
    }
}
