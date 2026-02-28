<?php

namespace App\Services;

use App\Interfaces\FunctionProgramProjectRepositoryInterface;
use App\Models\FunctionProgramProject;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FunctionProgramProjectService
{
    public function __construct(
        protected FunctionProgramProjectRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?FunctionProgramProject
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): FunctionProgramProject
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $functionProgramProject = $this->repository->create($data);
        $this->logRepository->create([
            'message' => 'Function/Program/Project created successfully.',
            'log_id' => $functionProgramProject->id,
            'log_module' => 'lib-fpp',
            'data' => $functionProgramProject,
        ]);

        return $functionProgramProject;
    }

    public function update(string $id, array $data): FunctionProgramProject
    {
        $data['active'] = filter_var($data['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $functionProgramProject = $this->repository->update($id, $data);
        $this->logRepository->create([
            'message' => 'Function/Program/Project updated successfully.',
            'log_id' => $functionProgramProject->id,
            'log_module' => 'lib-fpp',
            'data' => $functionProgramProject,
        ]);

        return $functionProgramProject;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-fpp',
            'data' => $data,
        ], isError: true);
    }
}
