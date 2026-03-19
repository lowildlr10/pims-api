<?php

namespace App\Services;

use App\Enums\InspectionAcceptanceReportStatus;
use App\Enums\NotificationType;
use App\Enums\PurchaseOrderStatus;
use App\Helpers\RequiredFieldsValidationHelper;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\InspectionAcceptanceReportInterface;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Repositories\InventorySupplyRepository;
use App\Repositories\LogRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ObligationRequestRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class InspectionAcceptanceReportService
{
    public function __construct(
        protected InspectionAcceptanceReportInterface $repository,
        protected LogRepository $logRepository,
        protected InventorySupplyRepository $inventorySupplyRepository,
        protected ObligationRequestRepository $obligationRequestRepository,
        protected NotificationRepository $notificationRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
    {
        $user = Auth::user();

        $hasFullAccess = in_array(true, [
            $user->tokenCan('super:*'),
            $user->tokenCan('head:*'),
            $user->tokenCan('supply:*'),
            $user->tokenCan('budget:*'),
            $user->tokenCan('accountant:*'),
            $user->tokenCan('treasurer:*'),
        ]);

        return $this->repository->getAll($filters, $hasFullAccess ? null : $user->id);
    }

    public function getById(string $id): ?InspectionAcceptanceReport
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): InspectionAcceptanceReport
    {
        $iar = $this->repository->storeUpdate($data);

        $this->logRepository->create([
            'message' => 'Inspection and acceptance report created successfully.',
            'log_id' => $iar->id,
            'log_module' => 'iar',
            'data' => $iar,
        ]);

        return $iar;
    }

    public function update(InspectionAcceptanceReport $iar, array $data): InspectionAcceptanceReport
    {
        $iar->update($data);
        $iar->load('items');

        $this->logRepository->create([
            'message' => 'Inspection and acceptance report updated successfully.',
            'log_id' => $iar->id,
            'log_module' => 'iar',
            'data' => $iar,
        ]);

        return $iar;
    }

    public function pending(InspectionAcceptanceReport $iar): InspectionAcceptanceReport
    {
        $currentStatus = $iar->status;

        if ($currentStatus !== InspectionAcceptanceReportStatus::DRAFT) {
            $message = 'Failed to set the inspection & acceptance report to pending for inspection. It may already be set to pending or processing status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $iar->id,
                'log_module' => 'iar',
                'data' => $iar,
            ], isError: true);

            throw new \Exception($message);
        }

        $requiredFields = [
            'iar_date' => 'IAR Date',
            'invoice_no' => 'Invoice Number',
            'invoice_date' => 'Invoice Date',
        ];

        $missingFields = RequiredFieldsValidationHelper::getMissingFields($requiredFields, $iar);

        if (! empty($missingFields)) {
            $this->logRepository->create([
                'message' => 'Cannot set inspection & acceptance report to pending. Missing required fields.',
                'log_id' => $iar->id,
                'log_module' => 'iar',
                'data' => ['missing_fields' => $missingFields],
            ], isError: true);

            throw new \Exception('Cannot set inspection & acceptance report to pending. Please fill out the following fields first: '.implode(', ', $missingFields));
        }

        $purchaseOrder = PurchaseOrder::find($iar->purchase_order_id);

        if ($purchaseOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::FOR_INSPECTION,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'for_inspection_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' order successfully marked as for inspection.',
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);

            $this->notificationRepository->notify(NotificationType::PO_FOR_INSPECTION, ['po' => $purchaseOrder]);
        }

        $iar->update([
            'status' => InspectionAcceptanceReportStatus::PENDING,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'pending_at', $iar->status_timestamps
            ),
        ]);

        $iar->load('items');

        $this->logRepository->create([
            'message' => 'Inspection & acceptance report successfully marked as pending for inspection.',
            'log_id' => $iar->id,
            'log_module' => 'iar',
            'data' => $iar,
        ]);

        return $iar;
    }

    public function inspect(InspectionAcceptanceReport $iar, array $items): InspectionAcceptanceReport
    {
        $iar->load([
            'purchase_order',
            'purchase_order.supplier',
        ]);

        $currentStatus = $iar->status;

        if ($currentStatus !== InspectionAcceptanceReportStatus::PENDING) {
            $message = 'Failed to set the inspection & acceptance report to inspected. It may still be on draft or already on processing status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $iar->id,
                'log_module' => 'iar',
                'data' => $iar,
            ], isError: true);

            throw new \Exception($message);
        }

        $requiredFields = [
            'inspected_date' => 'Inspected Date',
            'inspected' => 'Inspected By',
            'sig_inspection_id' => 'Inspection Signatory',
        ];

        $missingFields = RequiredFieldsValidationHelper::getMissingFields($requiredFields, $iar);

        if (! empty($missingFields)) {
            $this->logRepository->create([
                'message' => 'Cannot set inspection & acceptance report to inspected. Missing required fields.',
                'log_id' => $iar->id,
                'log_module' => 'iar',
                'data' => ['missing_fields' => $missingFields, 'items' => $items],
            ], isError: true);

            throw new \Exception('Cannot set inspection & acceptance report to inspected. Please fill out the following fields first: '.implode(', ', $missingFields));
        }

        foreach ($items ?? [] as $key => $item) {
            if (isset($item['included']) && ! $item['included']) {
                continue;
            }

            $supply = $this->inventorySupplyRepository->storeUpdate(array_merge(
                $item,
                ['item_sequence' => $key]
            ));

            $this->logRepository->create([
                'message' => 'Supply created successfully.',
                'log_id' => $supply->id,
                'log_module' => 'inv-supply',
                'data' => $supply,
            ]);
        }

        $obligationRequest = $this->obligationRequestRepository->storeUpdate([
            'purchase_request_id' => $iar->purchase_request_id,
            'purchase_order_id' => $iar->purchase_order_id,
            'payee_id' => $iar->purchase_order->supplier_id,
            'payee_type' => \App\Models\Supplier::class,
            'address' => $iar->purchase_order->supplier->address ?? null,
            'total_amount' => $iar->purchase_order->total_amount ?? 0.00,
        ]);

        $this->logRepository->create([
            'message' => 'Obligation request created successfully.',
            'log_id' => $obligationRequest->id,
            'log_module' => 'obr',
            'data' => $obligationRequest,
        ]);

        $this->notificationRepository->notify(NotificationType::OBR_CREATED, ['obr' => $obligationRequest]);

        $purchaseOrder = PurchaseOrder::find($iar->purchase_order_id);

        if ($purchaseOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::INSPECTED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'inspected_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' order successfully marked as inspected.',
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);

            $this->notificationRepository->notify(NotificationType::PO_INSPECTED, ['po' => $purchaseOrder]);
        }

        $iar->update([
            'status' => InspectionAcceptanceReportStatus::INSPECTED,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'inspected_at', $iar->status_timestamps
            ),
        ]);

        $iar->load('items');

        $this->logRepository->create([
            'message' => 'Inspection & acceptance report successfully marked as inspected.',
            'log_id' => $iar->id,
            'log_module' => 'iar',
            'data' => $iar,
        ]);

        return $iar;
    }

    public function logError(string $message, \Throwable $th, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'iar',
            'data' => $data,
        ], isError: true);
    }

    public function log(string $message, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'log_module' => 'iar',
            'data' => $data,
        ]);
    }
}
