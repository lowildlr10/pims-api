<?php

namespace App\Services;

use App\Interfaces\UnitIssueRepositoryInterface;
use App\Models\UnitIssue;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UnitIssueService
{
    public function __construct(
        protected UnitIssueRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?UnitIssue
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): UnitIssue
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $unitIssue = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Unit of issue created successfully.',
            'log_id' => $unitIssue->id,
            'log_module' => 'lib-unit-issue',
            'data' => $unitIssue,
        ]);

        return $unitIssue;
    }

    public function update(string $id, array $data): UnitIssue
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $unitIssue = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Unit of issue updated successfully.',
            'log_id' => $unitIssue->id,
            'log_module' => 'lib-unit-issue',
            'data' => $unitIssue,
        ]);

        return $unitIssue;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-unit-issue',
            'data' => $data,
        ], isError: true);
    }
}
