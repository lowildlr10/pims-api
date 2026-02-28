<?php

namespace App\Services;

use App\Interfaces\PaperSizeRepositoryInterface;
use App\Models\PaperSize;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaperSizeService
{
    public function __construct(
        protected PaperSizeRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        return $this->repository->getAll($filters);
    }

    public function getById(string $id): ?PaperSize
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): PaperSize
    {
        $paperSize = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Paper type created successfully.',
            'log_id' => $paperSize->id,
            'log_module' => 'lib-paper-size',
            'data' => $paperSize,
        ]);

        return $paperSize;
    }

    public function update(string $id, array $data): PaperSize
    {
        $paperSize = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Paper type updated successfully.',
            'log_id' => $paperSize->id,
            'log_module' => 'lib-paper-size',
            'data' => $paperSize,
        ]);

        return $paperSize;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-paper-size',
            'data' => $data,
        ], isError: true);
    }
}
