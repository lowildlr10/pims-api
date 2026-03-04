<?php

namespace App\Services;

use App\Interfaces\AccountRepositoryInterface;
use App\Models\Account;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AccountService
{
    public function __construct(
        protected AccountRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?Account
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): Account
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $account = $this->repository->create($data);
        $this->logRepository->create([
            'message' => 'Account created successfully.',
            'log_id' => $account->id,
            'log_module' => 'lib-account',
            'data' => $account,
        ]);

        return $account;
    }

    public function update(string $id, array $data): Account
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $account = $this->repository->update($id, $data);
        $this->logRepository->create([
            'message' => 'Account updated successfully.',
            'log_id' => $account->id,
            'log_module' => 'lib-account',
            'data' => $account,
        ]);

        return $account;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-account',
            'data' => $data,
        ], isError: true);
    }
}
