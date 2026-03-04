<?php

namespace App\Services;

use App\Interfaces\LogRepositoryInterface;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LogService
{
    public function __construct(
        protected LogRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters, ?string $userId, bool $isSuper): LengthAwarePaginator
    {
        return $this->repository->getAll($filters, $userId, $isSuper);
    }

    public function log(string $message, ?string $logId = null, string $logModule = 'system', array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'log_id' => $logId,
            'log_module' => $logModule,
            'data' => $data,
        ]);
    }

    public function logError(string $message, \Throwable $th, ?string $logId = null, string $logModule = 'system', array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_id' => $logId,
            'log_module' => $logModule,
            'data' => $data,
        ], isError: true);
    }
}
