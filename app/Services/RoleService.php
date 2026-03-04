<?php

namespace App\Services;

use App\Interfaces\RoleRepositoryInterface;
use App\Models\Role;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService
{
    public function __construct(
        protected RoleRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?Role
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): Role
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['permissions'] = json_decode($data['permissions'] ?? '[]');

        $role = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Role created successfully.',
            'log_id' => $role->id,
            'log_module' => 'account-role',
            'data' => $role,
        ]);

        return $role;
    }

    public function update(string $id, array $data): Role
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['permissions'] = json_decode($data['permissions'] ?? '[]');

        $role = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Role updated successfully.',
            'log_id' => $role->id,
            'log_module' => 'account-role',
            'data' => $role,
        ]);

        return $role;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'account-role',
            'data' => $data,
        ], isError: true);
    }
}
