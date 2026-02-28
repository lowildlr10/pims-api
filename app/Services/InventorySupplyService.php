<?php

namespace App\Services;

use App\Interfaces\InventorySupplyInterface;
use App\Models\InventorySupply;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InventorySupplyService
{
    public function __construct(
        protected InventorySupplyInterface $repository,
        protected LogRepository $logRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator|Collection
    {
        $parsedFilters = $this->parseFilters($filters);

        return $this->repository->getAll($parsedFilters);
    }

    public function getById(string $id): ?InventorySupply
    {
        return $this->repository->getById($id);
    }

    public function update(string $id, array $data): InventorySupply
    {
        $inventorySupply = $this->repository->update($id, $data);

        $this->logRepository->create([
            'message' => 'Inventory supply updated successfully.',
            'log_id' => $inventorySupply->id,
            'log_module' => 'inv-supply',
            'data' => $inventorySupply,
        ]);

        return $inventorySupply;
    }

    public function logError(string $message, \Throwable $th, array $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'inv-supply',
            'data' => $data,
        ], isError: true);
    }

    protected function parseFilters(array $filters): array
    {
        return [
            'search' => $filters['search'] ?? '',
            'per_page' => $filters['per_page'] ?? 10,
            'grouped' => filter_var($filters['grouped'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'document_type' => $filters['document_type'] ?? null,
            'search_by_po' => filter_var($filters['search_by_po'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'show_all' => filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'column_sort' => $filters['column_sort'] ?? 'pr_no',
            'sort_direction' => $filters['sort_direction'] ?? 'desc',
            'paginated' => filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
