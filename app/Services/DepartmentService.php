<?php

namespace App\Services;

use App\Interfaces\DepartmentRepositoryInterface;
use App\Models\Department;
use App\Models\Section;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentService
{
    public function __construct(
        protected DepartmentRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?Department
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): Department
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $department = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Department created successfully.',
            'log_id' => $department->id,
            'log_module' => 'account-department',
            'data' => $department,
        ]);

        return $department;
    }

    public function update(string $id, array $data): Department
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        Section::where('department_id', $id)->update([
            'active' => $data['active'],
        ]);

        $department = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Department updated successfully.',
            'log_id' => $department->id,
            'log_module' => 'account-department',
            'data' => $department,
        ]);

        return $department;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'account-department',
            'data' => $data,
        ], isError: true);
    }
}
