<?php

namespace App\Services;

use App\Interfaces\TaxWithholdingRepositoryInterface;
use App\Models\TaxWithholding;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TaxWithholdingService
{
    public function __construct(
        protected TaxWithholdingRepositoryInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator|Collection
    {
        return $this->repository->getAll($this->parseFilters($filters));
    }

    public function getById(string $id): ?TaxWithholding
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): TaxWithholding
    {
        $taxWithholding = $this->repository->create($data);

        $this->logRepository->create([
            'message' => 'Tax withholding created successfully.',
            'log_id' => $taxWithholding->id,
            'log_module' => 'lib-tax-withholding',
            'data' => $taxWithholding,
        ]);

        return $taxWithholding;
    }

    public function update(string $id, array $data): TaxWithholding
    {
        $taxWithholding = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Tax withholding updated successfully.',
            'log_id' => $taxWithholding->id,
            'log_module' => 'lib-tax-withholding',
            'data' => $taxWithholding,
        ]);

        return $taxWithholding;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'lib-tax-withholding',
            'data' => $data,
        ], isError: true);
    }

    protected function parseFilters(array $filters): array
    {
        return [
            'search' => $filters['search'] ?? '',
            'per_page' => $filters['per_page'] ?? 50,
            'show_all' => filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'column_sort' => $filters['column_sort'] ?? 'name',
            'sort_direction' => $filters['sort_direction'] ?? 'asc',
            'paginated' => filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
