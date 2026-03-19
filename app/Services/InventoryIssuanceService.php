<?php

namespace App\Services;

use App\Enums\InventoryIssuanceStatus;
use App\Enums\NotificationType;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\InventoryIssuanceRepositoryInterface;
use App\Models\InventoryIssuance;
use App\Models\InventorySupply;
use App\Repositories\LogRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InventoryIssuanceService
{
    public function __construct(
        protected InventoryIssuanceRepositoryInterface $repository,
        protected LogRepository $logRepository,
        protected NotificationRepository $notificationRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator|Collection
    {
        $parsedFilters = $this->parseFilters($filters);

        return $this->repository->getAll($parsedFilters);
    }

    public function getById(string $id): ?InventoryIssuance
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): InventoryIssuance
    {
        $this->validateItemAvailability($data['items'] ?? []);

        $inventoryIssuance = $this->repository->storeUpdate($data, null);
        $inventoryIssuance->load('items');

        $this->logRepository->create([
            'message' => 'Inventory issuance created successfully.',
            'log_id' => $inventoryIssuance->id,
            'log_module' => 'inv-issuance',
            'data' => $inventoryIssuance,
        ]);

        return $inventoryIssuance;
    }

    public function update(InventoryIssuance $inventoryIssuance, array $data): InventoryIssuance
    {
        $this->validateItemAvailability($data['items'] ?? [], $inventoryIssuance->id);

        $this->repository->storeUpdate($data, $inventoryIssuance);
        $inventoryIssuance->load('items');

        $this->logRepository->create([
            'message' => 'Inventory issuance updated successfully.',
            'log_id' => $inventoryIssuance->id,
            'log_module' => 'inv-issuance',
            'data' => $inventoryIssuance,
        ]);

        return $inventoryIssuance;
    }

    protected function validateItemAvailability(array $items, ?string $excludeIssuanceId = null): void
    {
        foreach ($items as $item) {
            $supply = InventorySupply::find($item['inventory_supply_id']);

            if (! $supply) {
                throw new \Exception("Supply not found: {$item['inventory_supply_id']}");
            }

            $requestedQuantity = $item['quantity'];
            $availableQuantity = $supply->available;

            $previouslyIssuedQuantity = $this->getPreviouslyIssuedQuantity(
                $item['inventory_supply_id'],
                $excludeIssuanceId
            );

            $remainingAvailable = $availableQuantity + $previouslyIssuedQuantity;

            if ($requestedQuantity > $remainingAvailable) {
                throw new \Exception(
                    "Insufficient stock for item '{$supply->name}'. ".
                    "Requested: {$requestedQuantity}, Available: {$remainingAvailable}"
                );
            }
        }
    }

    protected function getPreviouslyIssuedQuantity(string $supplyId, ?string $excludeIssuanceId): int
    {
        if (! $excludeIssuanceId) {
            return 0;
        }

        return \App\Models\InventoryIssuanceItem::where('inventory_supply_id', $supplyId)
            ->whereHas('issuance', function ($query) use ($excludeIssuanceId) {
                $query->where('id', '!=', $excludeIssuanceId)
                    ->whereIn('status', [
                        InventoryIssuanceStatus::PENDING,
                        InventoryIssuanceStatus::ISSUED,
                    ]);
            })
            ->sum('quantity');
    }

    public function pending(InventoryIssuance $inventoryIssuance): InventoryIssuance
    {
        $issuanceCurrentStatus = $inventoryIssuance->status;

        if ($issuanceCurrentStatus !== InventoryIssuanceStatus::DRAFT) {
            $message = 'Failed to set inventory issuance to pending. This document has already been issued or is still processing.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $inventoryIssuance->id,
                'log_module' => 'inv-issuance',
                'data' => $inventoryIssuance,
            ], isError: true);

            throw new \Exception($message);
        }

        if (empty($inventoryIssuance->received_by_id) || empty($inventoryIssuance->sig_issued_by_id)) {
            $message = 'Failed to set inventory issuance to pending. Both a receiver and an issuer must be specified.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $inventoryIssuance->id,
                'log_module' => 'inv-issuance',
                'data' => $inventoryIssuance,
            ], isError: true);

            throw new \Exception($message);
        }

        $inventoryIssuance->update([
            'status' => InventoryIssuanceStatus::PENDING,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'pending_at', $inventoryIssuance->status_timestamps
            ),
        ]);

        $inventoryIssuance->load('items');

        $this->logRepository->create([
            'message' => 'Inventory issuance successfully marked as "Pending".',
            'log_id' => $inventoryIssuance->id,
            'log_module' => 'inv-issuance',
            'data' => $inventoryIssuance,
        ]);

        return $inventoryIssuance;
    }

    public function issue(InventoryIssuance $inventoryIssuance): InventoryIssuance
    {
        $issuanceCurrentStatus = $inventoryIssuance->status;

        if ($issuanceCurrentStatus !== InventoryIssuanceStatus::PENDING) {
            $message = 'Failed to set inventory issuance to issued. This document has already been issued or is still draft.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $inventoryIssuance->id,
                'log_module' => 'inv-issuance',
                'data' => $inventoryIssuance,
            ], isError: true);

            throw new \Exception($message);
        }

        if (empty($inventoryIssuance->received_by_id) || empty($inventoryIssuance->sig_issued_by_id)) {
            $message = 'Failed to set inventory issuance to pending. Both a receiver and an issuer must be specified.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $inventoryIssuance->id,
                'log_module' => 'inv-issuance',
                'data' => $inventoryIssuance,
            ], isError: true);

            throw new \Exception($message);
        }

        $inventoryIssuance->update([
            'status' => InventoryIssuanceStatus::ISSUED,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'issued_at', $inventoryIssuance->status_timestamps
            ),
        ]);

        $inventoryIssuance->load('items');

        $this->logRepository->create([
            'message' => 'Inventory issuance successfully marked as "Issued".',
            'log_id' => $inventoryIssuance->id,
            'log_module' => 'inv-issuance',
            'data' => $inventoryIssuance,
        ]);

        $this->notificationRepository->notify(NotificationType::INV_ISSUANCE_ISSUED, [
            'issuance' => $inventoryIssuance,
        ]);

        return $inventoryIssuance;
    }

    public function cancel(InventoryIssuance $inventoryIssuance): InventoryIssuance
    {
        $inventoryIssuance->update([
            'status' => InventoryIssuanceStatus::CANCELLED,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'cancelled_at', $inventoryIssuance->status_timestamps
            ),
        ]);

        $inventoryIssuance->load('items');

        $this->logRepository->create([
            'message' => 'Inventory issuance successfully marked as "Cancelled".',
            'log_id' => $inventoryIssuance->id,
            'log_module' => 'inv-issuance',
            'data' => $inventoryIssuance,
        ]);

        return $inventoryIssuance;
    }

    public function getRepository(): InventoryIssuanceRepositoryInterface
    {
        return $this->repository;
    }

    public function getLogger(): LogRepository
    {
        return $this->logRepository;
    }

    public function logError(string $message, \Throwable $th, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'inv-issuance',
            'data' => $data,
        ], isError: true);
    }

    public function log(string $message, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'log_module' => 'inv-issuance',
            'data' => $data,
        ]);
    }

    protected function parseFilters(array $filters): array
    {
        return [
            'search' => trim($filters['search'] ?? ''),
            'per_page' => $filters['per_page'] ?? 10,
            'show_all' => filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'column_sort' => $filters['column_sort'] ?? 'po_no',
            'sort_direction' => $filters['sort_direction'] ?? 'desc',
            'paginated' => filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
