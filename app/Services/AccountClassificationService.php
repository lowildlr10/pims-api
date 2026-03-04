<?php

namespace App\Services;

use App\Interfaces\AccountClassificationRepositoryInterface;
use App\Models\AccountClassification;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AccountClassificationService
{
    public function __construct(
        protected AccountClassificationRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?AccountClassification
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): AccountClassification
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $accountClassification = $this->repository->create($data);
        $this->logRepository->create([
            'message' => 'Account classification created successfully.',
            'log_id' => $accountClassification->id,
            'log_module' => 'lib-account-class',
            'data' => $accountClassification,
        ]);

        return $accountClassification;
    }

    public function update(string $id, array $data): AccountClassification
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $accountClassification = $this->repository->update($id, $data);
        $this->logRepository->create([
            'message' => 'Account classification updated successfully.',
            'log_id' => $accountClassification->id,
            'log_module' => 'lib-account-class',
            'data' => $accountClassification,
        ]);

        return $accountClassification;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-account-class',
            'data' => $data,
        ], isError: true);
    }
}
