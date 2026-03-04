<?php

namespace App\Services;

use App\Interfaces\FundingSourceRepositoryInterface;
use App\Models\FundingSource;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FundingSourceService
{
    public function __construct(
        protected FundingSourceRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?FundingSource
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): FundingSource
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $fundingSource = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Funding source/project created successfully.',
            'log_id' => $fundingSource->id,
            'log_module' => 'lib-fund-source',
            'data' => $fundingSource,
        ]);

        return $fundingSource;
    }

    public function update(string $id, array $data): FundingSource
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $fundingSource = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Funding source/project updated successfully.',
            'log_id' => $fundingSource->id,
            'log_module' => 'lib-fund-source',
            'data' => $fundingSource,
        ]);

        return $fundingSource;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-fund-source',
            'data' => $data,
        ], isError: true);
    }
}
