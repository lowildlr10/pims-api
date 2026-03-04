<?php

namespace App\Services;

use App\Interfaces\BidsAwardsCommitteeRepositoryInterface;
use App\Models\BidsAwardsCommittee;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BidsAwardsCommitteeService
{
    public function __construct(
        protected BidsAwardsCommitteeRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?BidsAwardsCommittee
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): BidsAwardsCommittee
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $committee = $this->repository->create($data);
        $this->logRepository->create([
            'message' => 'Bids and Awards Committee created successfully.',
            'log_id' => $committee->id,
            'log_module' => 'lib-bac',
            'data' => $committee,
        ]);

        return $committee;
    }

    public function update(string $id, array $data): BidsAwardsCommittee
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $committee = $this->repository->update($id, $data);
        $this->logRepository->create([
            'message' => 'Bids and Awards Committee updated successfully.',
            'log_id' => $committee->id,
            'log_module' => 'lib-bac',
            'data' => $committee,
        ]);

        return $committee;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-bac',
            'data' => $data,
        ], isError: true);
    }
}
