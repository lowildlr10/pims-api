<?php

namespace App\Services;

use App\Enums\AbstractQuotationStatus;
use App\Enums\NotificationType;
use App\Enums\PurchaseRequestStatus;
use App\Enums\RequestQuotationStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\PurchaseRequestRepositoryInterface;
use App\Models\AbstractQuotation;
use App\Models\AbstractQuotationDetail;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RequestQuotation;
use App\Repositories\AbstractQuotationRepository;
use App\Repositories\LogRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PurchaseOrderRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestService
{
    public function __construct(
        protected PurchaseRequestRepositoryInterface $repository,
        protected LogRepository $logRepository,
        protected NotificationRepository $notificationRepository,
        protected AbstractQuotationRepository $abstractQuotationRepository,
        protected PurchaseOrderRepository $purchaseOrderRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator|Collection
    {
        $user = Auth::user();

        $hasFullAccess = in_array(true, [
            $user->tokenCan('super:*'),
            $user->tokenCan('head:*'),
            $user->tokenCan('supply:*'),
            $user->tokenCan('budget:*'),
            $user->tokenCan('accountant:*'),
        ]);

        $userId = $user->id;

        return $this->repository->getAll($filters, $userId, $hasFullAccess);
    }

    public function getById(string $id): ?PurchaseRequest
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): PurchaseRequest
    {
        $user = Auth::user();

        $canAccess = in_array(true, [
            $user->tokenCan('super:*'),
            $user->tokenCan('supply:*'),
        ]);

        if (! $canAccess && $data['requested_by_id'] !== $user->id) {
            $message = 'Purchase request creation failed. User is not authorized to create purchase requests for others.';
            $this->logRepository->create([
                'message' => $message,
                'log_module' => 'pr',
                'data' => $data,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseRequest = $this->repository->create(array_merge(
            $data,
            [
                'pr_no' => $this->repository->generateNewPrNumber(),
                'status' => PurchaseRequestStatus::DRAFT,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'draft_at', null
                ),
            ]
        ));

        $totalEstimatedCost = 0;

        foreach ($data['items'] ?? [] as $key => $item) {
            $quantity = intval($item['quantity']);
            $unitCost = floatval($item['estimated_unit_cost']);
            $cost = round($quantity * $unitCost, 2);

            PurchaseRequestItem::create([
                'purchase_request_id' => $purchaseRequest->id,
                'item_sequence' => $key,
                'quantity' => $quantity,
                'unit_issue_id' => $item['unit_issue_id'],
                'description' => $item['description'],
                'stock_no' => (int) $item['stock_no'] ?? $key + 1,
                'estimated_unit_cost' => $unitCost,
                'estimated_cost' => $cost,
            ]);

            $totalEstimatedCost += $cost;
        }

        $purchaseRequest->update([
            'total_estimated_cost' => $totalEstimatedCost,
        ]);

        $purchaseRequest->items = $data['items'] ?? [];

        $this->logRepository->create([
            'message' => 'Purchase request created successfully.',
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function update(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        $user = Auth::user();

        $canAccess = in_array(true, [
            $user->tokenCan('super:*'),
            $user->tokenCan('supply:*'),
        ]);

        if (! $canAccess && $purchaseRequest->requested_by_id !== $user->id) {
            $message = 'Purchase request update failed. User is not authorized to update purchase requests for others.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $data,
            ], isError: true);

            throw new \Exception($message);
        }

        $currentStatus = $purchaseRequest->status instanceof PurchaseRequestStatus
            ? $purchaseRequest->status
            : PurchaseRequestStatus::from($purchaseRequest->status);

        if (in_array($currentStatus, [
            PurchaseRequestStatus::CANCELLED,
            PurchaseRequestStatus::FOR_CANVASSING,
            PurchaseRequestStatus::FOR_RECANVASSING,
            PurchaseRequestStatus::FOR_ABSTRACT,
            PurchaseRequestStatus::PARTIALLY_AWARDED,
            PurchaseRequestStatus::AWARDED,
            PurchaseRequestStatus::COMPLETED,
        ])) {
            $message = 'Purchase request update failed, already processing or cancelled.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $data,
            ], isError: true);

            throw new \Exception($message);
        }

        if ($currentStatus === PurchaseRequestStatus::DRAFT
            || $currentStatus === PurchaseRequestStatus::DISAPPROVED) {
            $totalEstimatedCost = 0;

            PurchaseRequestItem::where('purchase_request_id', $purchaseRequest->id)
                ->delete();

            foreach ($data['items'] ?? [] as $key => $item) {
                $quantity = intval($item['quantity']);
                $unitCost = floatval($item['estimated_unit_cost']);
                $cost = round($quantity * $unitCost, 2);

                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_sequence' => $key,
                    'quantity' => $quantity,
                    'unit_issue_id' => $item['unit_issue_id'],
                    'description' => $item['description'],
                    'stock_no' => (int) $item['stock_no'] ?? $key + 1,
                    'estimated_unit_cost' => $unitCost,
                    'estimated_cost' => $cost,
                ]);

                $totalEstimatedCost += $cost;
            }

            $purchaseRequest->update([
                'total_estimated_cost' => $totalEstimatedCost,
                'status' => PurchaseRequestStatus::DRAFT,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'draft_at', null
                ),
            ]);
        }

        $purchaseRequest->update($data);
        $purchaseRequest->load('items');

        $this->logRepository->create([
            'message' => 'Purchase request updated successfully.',
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function submitForApproval(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        $user = Auth::user();

        $canAccess = in_array(true, [
            $user->tokenCan('super:*'),
            $user->tokenCan('supply:*'),
        ]);

        if (! $canAccess && $purchaseRequest->requested_by_id !== $user->id) {
            $message = 'Purchase request submit failed. User is not authorized to submit purchase requests for others.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseRequest->update([
            'disapproved_reason' => null,
            'status' => PurchaseRequestStatus::PENDING,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'pending_at', $purchaseRequest->status_timestamps
            ),
        ]);

        $this->notificationRepository->notify(NotificationType::PR_PENDING, [
            'pr' => $purchaseRequest,
        ]);

        $this->logRepository->create([
            'message' => 'Purchase request has been successfully marked as "Pending".',
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function approveForCashAvailability(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        $user = Auth::user();

        $canAccess = in_array(true, [
            $user->tokenCan('super:*'),
            $user->tokenCan('supply:*'),
            $user->tokenCan('budget:*'),
            $user->tokenCan('accountant:*'),
            $user->tokenCan('cashier:*'),
            $user->tokenCan('pr:*'),
            $user->tokenCan('pr:approve-cash-available'),
        ]);

        if (! $canAccess) {
            $message = 'Purchase request approval for cash availability failed. User is not authorized.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseRequest->update([
            'status' => PurchaseRequestStatus::APPROVED_CASH_AVAILABLE,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'approved_cash_available_at', $purchaseRequest->status_timestamps
            ),
        ]);

        $this->notificationRepository->notify(NotificationType::PR_APPROVED_CASH_AVAILABLE, [
            'pr' => $purchaseRequest,
        ]);

        $this->logRepository->create([
            'message' => 'Purchase request has been successfully marked as "Approved for Cash Availability".',
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function approve(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        $user = Auth::user();

        $canAccess = in_array(true, [
            $user->tokenCan('super:*'),
            $user->tokenCan('supply:*'),
            $user->tokenCan('head:*'),
            $user->tokenCan('pr:*'),
            $user->tokenCan('pr:approve'),
        ]);

        if (! $canAccess) {
            $message = 'Purchase request approve failed. User is not authorized.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseRequest->update([
            'status' => PurchaseRequestStatus::APPROVED,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'approved_at', $purchaseRequest->status_timestamps
            ),
        ]);

        $this->notificationRepository->notify(NotificationType::PR_APPROVED, [
            'pr' => $purchaseRequest,
        ]);

        $this->logRepository->create([
            'message' => 'Purchase request has been successfully marked as "Approved".',
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function disapprove(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        $user = Auth::user();

        $canAccess = in_array(true, [
            $user->tokenCan('super:*'),
            $user->tokenCan('supply:*'),
            $user->tokenCan('head:*'),
            $user->tokenCan('pr:*'),
            $user->tokenCan('pr:disapprove'),
        ]);

        if (! $canAccess) {
            $message = 'Purchase request disapprove failed. User is not authorized.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseRequest->update([
            'disapproved_reason' => $data['disapproved_reason'] ?? null,
            'status' => PurchaseRequestStatus::DISAPPROVED,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'disapproved_at', $purchaseRequest->status_timestamps
            ),
        ]);

        $this->notificationRepository->notify(NotificationType::PR_DISAPPROVED, [
            'pr' => $purchaseRequest,
        ]);

        $this->logRepository->create([
            'message' => 'Purchase request has been successfully marked as "Disapproved".',
            'details' => ! empty($data['disapproved_reason']) ? 'Reason: '.$data['disapproved_reason'] : null,
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function cancel(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        $user = Auth::user();

        $canAccess = in_array(true, [
            $user->tokenCan('super:*'),
            $user->tokenCan('supply:*'),
            $user->tokenCan('pr:*'),
            $user->tokenCan('pr:cancel'),
        ]);

        if (! $canAccess && $purchaseRequest->requested_by_id !== $user->id) {
            $message = 'Purchase request cancel failed. User is not authorized to cancel purchase requests for others.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseRequest->update([
            'status' => PurchaseRequestStatus::CANCELLED,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'cancelled_at', $purchaseRequest->status_timestamps
            ),
        ]);

        $this->notificationRepository->notify(NotificationType::PR_CANCELLED, [
            'pr' => $purchaseRequest,
        ]);

        $this->logRepository->create([
            'message' => 'Purchase request successfully marked as "Cancelled".',
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function issueAllDraftRfq(PurchaseRequest $purchaseRequest): \Illuminate\Database\Eloquent\Collection
    {
        $currentStatus = $purchaseRequest->status instanceof PurchaseRequestStatus
            ? $purchaseRequest->status
            : PurchaseRequestStatus::from($purchaseRequest->status);

        $rfqDraft = RequestQuotation::where('purchase_request_id', $purchaseRequest->id)
            ->whereIn('status', [RequestQuotationStatus::DRAFT]);
        $rfqDraftCount = $rfqDraft->count();
        $rfqDraft = $rfqDraft->get();

        if ($rfqDraftCount === 0) {
            $message = 'No RFQs to issue because all have been given to canvassers or none were created from this PR.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $rfqDraft,
            ], isError: true);

            throw new \Exception($message);
        }

        foreach ($rfqDraft as $rfq) {
            $rfq->update([
                'status' => RequestQuotationStatus::CANVASSING,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'canvassing_at', $rfq->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => 'Request for quotation successfully marked as "Canvassing".',
                'log_id' => $rfq->id,
                'log_module' => 'rfq',
                'data' => $rfq,
            ]);
        }

        if (in_array($currentStatus, [
            PurchaseRequestStatus::APPROVED,
            PurchaseRequestStatus::FOR_ABSTRACT,
            PurchaseRequestStatus::PARTIALLY_AWARDED,
        ])) {
            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::FOR_CANVASSING,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'canvassing_at', $purchaseRequest->status_timestamps
                ),
            ]);
        }

        return $rfqDraft;
    }

    public function approveRequestQuotations(PurchaseRequest $purchaseRequest, array $data): PurchaseRequest
    {
        $currentStatus = $purchaseRequest->status instanceof PurchaseRequestStatus
            ? $purchaseRequest->status
            : PurchaseRequestStatus::from($purchaseRequest->status);

        if ($currentStatus !== PurchaseRequestStatus::FOR_CANVASSING
            && $currentStatus !== PurchaseRequestStatus::FOR_RECANVASSING) {
            $message = 'Failed to mark the purchase request as "For Abstract" because it is already set to this status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            throw new \Exception($message);
        }

        $rfqProcessing = RequestQuotation::where('purchase_request_id', $purchaseRequest->id)
            ->whereIn('status', [
                RequestQuotationStatus::CANVASSING,
                RequestQuotationStatus::DRAFT,
            ])
            ->where('batch', $purchaseRequest->rfq_batch);
        $rfqProcessingCount = $rfqProcessing->count();
        $rfqProcessing = $rfqProcessing->get();

        if ($rfqProcessingCount > 0) {
            $message = 'Failed to mark the purchase request as "For Abstract" due to pending RFQs in canvassing or draft status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $rfqProcessing,
            ], isError: true);

            throw new \Exception($message);
        }

        $rfqCompleted = RequestQuotation::where('purchase_request_id', $purchaseRequest->id)
            ->where('status', RequestQuotationStatus::COMPLETED)
            ->where('batch', $purchaseRequest->rfq_batch);
        $rfqCompletedCount = $rfqCompleted->count();
        $rfqCompleted = $rfqCompleted->get();

        if ($rfqCompletedCount === 0) {
            $message = 'Cannot mark as "For Abstract" because no RFQ has been completed';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $rfqCompleted,
            ], isError: true);

            throw new \Exception($message);
        }

        $rfqNumbers = $rfqCompleted->pluck('rfq_no')->unique();

        if ($rfqNumbers->count() > 1) {
            $message = 'Cannot mark as "For Abstract" because RFQs have different RFQ numbers.';
            $this->logRepository->create([
                'message' => $message,
                'details' => $rfqNumbers->implode(', '),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $rfqCompleted,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseRequest->update([
            'rfq_batch' => $purchaseRequest->rfq_batch + 1,
            'approved_rfq_at' => Carbon::now(),
            'status' => PurchaseRequestStatus::FOR_ABSTRACT,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'approved_rfq_at', $purchaseRequest->status_timestamps
            ),
        ]);

        $prReturnData = $purchaseRequest;
        $purchaseRequest->load('items');

        $abstractQuotation = $this->abstractQuotationRepository->storeUpdate([
            'purchase_request_id' => $purchaseRequest->id,
            'mode_procurement_id' => $data['mode_procurement_id'] ?? null,
            'solicitation_no' => isset($rfqCompleted[0]->rfq_no)
                ? $rfqCompleted[0]->rfq_no : '',
            'solicitation_date' => Carbon::now()->toDateString(),
            'items' => $purchaseRequest->items->map(function (PurchaseRequestItem $item) use ($rfqCompleted) {
                return [
                    'pr_item_id' => $item->id,
                    'included' => empty($item->awarded_to) ? true : false,
                    'document_type' => 'po',
                    'details' => $rfqCompleted->map(function (RequestQuotation $rfq) use ($item) {
                        $rfq->load([
                            'items' => function ($query) use ($item) {
                                $query->where('pr_item_id', $item->id);
                            },
                        ]);
                        $rfqItem = $rfq->items[0];

                        return [
                            'quantity' => $item->quantity,
                            'supplier_id' => $rfqItem->supplier_id,
                            'brand_model' => $rfqItem->brand_model,
                            'unit_cost' => $rfqItem->unit_cost,
                        ];
                    }),
                ];
            }),
        ]);

        $this->notificationRepository->notify(NotificationType::PR_FOR_ABSTRACT, [
            'pr' => $purchaseRequest, 'rfq' => $rfqCompleted[0],
        ]);

        $this->logRepository->create([
            'message' => 'Abstract of quotation created successfully.',
            'log_id' => $abstractQuotation->id,
            'log_module' => 'aoq',
            'data' => $abstractQuotation,
        ]);

        $this->logRepository->create([
            'message' => 'Purchase request successfully marked as "For Abstract".',
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $prReturnData,
        ]);

        return $prReturnData;
    }

    public function awardAbstractQuotations(PurchaseRequest $purchaseRequest): PurchaseRequest
    {
        $currentStatus = $purchaseRequest->status instanceof PurchaseRequestStatus
            ? $purchaseRequest->status
            : PurchaseRequestStatus::from($purchaseRequest->status);

        if ($currentStatus !== PurchaseRequestStatus::FOR_ABSTRACT
            && $currentStatus !== PurchaseRequestStatus::PARTIALLY_AWARDED) {
            $message = 'Failed to award the approved Abstract of Quotation(s) because it is already set to this status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            throw new \Exception($message);
        }

        $prStatus = PurchaseRequestStatus::AWARDED;
        $aoqApproved = AbstractQuotation::with('items')
            ->where('purchase_request_id', $purchaseRequest->id)
            ->where('status', AbstractQuotationStatus::APPROVED);
        $aoqApprovedCount = $aoqApproved->count();
        $aoqApproved = $aoqApproved->get();

        if ($aoqApprovedCount === 0) {
            $message = 'Nothing to award. The Abstract of Quotation(s) may still be pending or have already been awarded.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
            ], isError: true);

            throw new \Exception($message);
        }

        foreach ($aoqApproved ?? [] as $aoq) {
            $poData = [];
            $poItems = [];

            foreach ($aoq->items ?? [] as $item) {
                if (empty($item->awardee_id)) {
                    continue;
                }

                $prItem = PurchaseRequestItem::find($item->pr_item_id);
                $prItem->update([
                    'awarded_to_id' => $item->awardee_id,
                ]);

                $aorItemDetail = AbstractQuotationDetail::where('abstract_quotation_id', $aoq->id)
                    ->where('aoq_item_id', $item->id)
                    ->where('supplier_id', $item->awardee_id)
                    ->first();

                $poItems[$item->awardee_id][$item->document_type][] = [
                    'pr_item_id' => $prItem->id,
                    'brand_model' => $aorItemDetail->brand_model,
                    'description' => $aorItemDetail->brand_model
                        ? "{$prItem->description}\n{$aorItemDetail->brand_model}"
                        : $prItem->description,
                    'unit_cost' => $aorItemDetail->unit_cost,
                    'total_cost' => $aorItemDetail->total_cost,
                ];

                $poData[$item->awardee_id][$item->document_type] = [
                    'purchase_request_id' => $purchaseRequest->id,
                    'mode_procurement_id' => $aoq->mode_procurement_id,
                    'supplier_id' => $item->awardee_id,
                    'document_type' => $item?->document_type ?? 'po',
                    'items' => $poItems[$item->awardee_id][$item->document_type],
                ];
            }

            foreach ($poData ?? [] as $poDocs) {
                foreach ($poDocs as $data) {
                    $purchaseOrder = $this->purchaseOrderRepository->storeUpdate($data);
                    $this->logRepository->create([
                        'message' => 'Purchase Order created successfully.',
                        'log_id' => $purchaseOrder->id,
                        'log_module' => 'po',
                        'data' => $purchaseOrder,
                    ]);
                }
            }

            $aoq->update([
                'status' => AbstractQuotationStatus::AWARDED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'awarded_at', $aoq->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => 'Abstract of quotation awarded successfully.',
                'log_id' => $aoq->id,
                'log_module' => 'aoq',
                'data' => $aoq,
            ]);
        }

        $countItems = PurchaseRequestItem::where('purchase_request_id', $purchaseRequest->id)
            ->count();
        $countAwardedItems = PurchaseRequestItem::where('purchase_request_id', $purchaseRequest->id)
            ->whereNotNull('awarded_to_id')
            ->count();

        if ($countAwardedItems !== $countItems) {
            $prStatus = PurchaseRequestStatus::PARTIALLY_AWARDED;
        }

        $purchaseRequest->update([
            'status' => $prStatus,
            'status_timestamps' => StatusTimestampsHelper::generate(
                $prStatus === PurchaseRequestStatus::PARTIALLY_AWARDED
                    ? 'partially_awarded_at' : 'awarded_at',
                $purchaseRequest->status_timestamps
            ),
        ]);

        if ($prStatus === PurchaseRequestStatus::PARTIALLY_AWARDED) {
            $this->notificationRepository->notify(NotificationType::PR_PARTIALLY_AWARDED, [
                'pr' => $purchaseRequest, 'aoq' => $aoqApproved[0],
            ]);
        } else {
            $this->notificationRepository->notify(NotificationType::PR_AWARDED, [
                'pr' => $purchaseRequest, 'aoq' => $aoqApproved[0],
            ]);
        }

        $statusLabel = $prStatus === PurchaseRequestStatus::PARTIALLY_AWARDED
            ? '"Partially Awarded"' : '"Awarded"';

        $this->logRepository->create([
            'message' => "Purchase request successfully marked as {$statusLabel}.",
            'log_id' => $purchaseRequest->id,
            'log_module' => 'pr',
            'data' => $purchaseRequest,
        ]);

        return $purchaseRequest;
    }

    public function print(array $pageConfig, string $prId): array
    {
        return $this->repository->print($pageConfig, $prId);
    }

    public function logError(string $message, \Throwable $th, mixed $data): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'pr',
            'data' => $data,
        ], isError: true);
    }
}
