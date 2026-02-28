<?php

namespace App\Services;

use App\Enums\ObligationRequestStatus;
use App\Enums\PurchaseOrderStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\ObligationRequestInterface;
use App\Models\ObligationRequest;
use App\Models\PurchaseOrder;
use App\Repositories\DisbursementVoucherRepository;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ObligationRequestService
{
    public function __construct(
        protected ObligationRequestInterface $repository,
        protected LogRepository $logRepository,
        protected DisbursementVoucherRepository $dvRepository
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
            $user->tokenCan('cashier:*'),
        ]);

        return $this->repository->getAll($filters, $hasFullAccess ? null : $user->id);
    }

    public function getById(string $id): ?ObligationRequest
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): ObligationRequest
    {
        $obr = $this->repository->storeUpdate($data);

        $this->logRepository->create([
            'message' => 'Obligation request created successfully.',
            'log_id' => $obr->id,
            'log_module' => 'obr',
            'data' => $obr,
        ]);

        return $obr;
    }

    public function update(ObligationRequest $obr, array $data): ObligationRequest
    {
        $currentStatus = $obr->status;
        $status = $currentStatus;

        if ($currentStatus === ObligationRequestStatus::DRAFT
            || $currentStatus === ObligationRequestStatus::DISAPPROVED) {
            $status = ObligationRequestStatus::DRAFT;
        }

        $obr = $this->repository->storeUpdate(
            array_merge($data, [
                'status' => $status,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'draft_at', null
                ),
            ]),
            $obr
        );

        $obr->load(['fpps', 'accounts']);

        $this->logRepository->create([
            'message' => 'Obligation request updated successfully.',
            'log_id' => $obr->id,
            'log_module' => 'obr',
            'data' => $obr,
        ]);

        return $obr;
    }

    public function pending(ObligationRequest $obr): ObligationRequest
    {
        $currentStatus = $obr->status;

        if ($currentStatus !== ObligationRequestStatus::DRAFT) {
            $message = 'Failed to set the obligation request to pending for obligation. It may already be set to pending or processing status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $obr->id,
                'log_module' => 'obr',
                'data' => $obr,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseOrder = PurchaseOrder::find($obr->purchase_order_id);

        if ($purchaseOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::FOR_OBLIGATION,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'for_obligation_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' order successfully marked as for obligation.',
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);
        }

        $obr->update([
            'disapproved_reason' => null,
            'status' => ObligationRequestStatus::PENDING,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'pending_at', $obr->status_timestamps
            ),
        ]);

        $this->logRepository->create([
            'message' => 'Obligation request successfully marked as pending for obligation.',
            'log_id' => $obr->id,
            'log_module' => 'obr',
            'data' => $obr,
        ]);

        $obr->load(['fpps', 'accounts']);

        return $obr;
    }

    public function disapprove(ObligationRequest $obr, ?string $reason = null): ObligationRequest
    {
        $currentStatus = $obr->status;

        if ($currentStatus !== ObligationRequestStatus::PENDING) {
            $message = 'Failed to set the Obligation Request to "Disapproved". It may already be obligated or still in draft status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $obr->id,
                'log_module' => 'obr',
                'data' => $obr,
            ], isError: true);

            throw new \Exception($message);
        }

        $obr->update([
            'disapproved_reason' => $reason,
            'status' => ObligationRequestStatus::DISAPPROVED,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'disapproved_at', $obr->status_timestamps
            ),
        ]);

        $this->logRepository->create([
            'message' => 'Obligation request successfully marked as "Disapproved".',
            'log_id' => $obr->id,
            'log_module' => 'obr',
            'data' => $obr,
        ]);

        $obr->load(['fpps', 'accounts']);

        return $obr;
    }

    public function obligate(ObligationRequest $obr): ObligationRequest
    {
        $currentStatus = $obr->status;

        if ($currentStatus !== ObligationRequestStatus::PENDING) {
            $message = 'Failed to set the Obligation Request to "Obligated". It may already be obligated or still in draft status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $obr->id,
                'log_module' => 'obr',
                'data' => $obr,
            ], isError: true);

            throw new \Exception($message);
        }

        $disbursementVoucher = $this->dvRepository->storeUpdate([
            'purchase_request_id' => $obr->purchase_request_id,
            'purchase_order_id' => $obr->purchase_order_id,
            'obligation_request_id' => $obr->id,
            'payee_id' => $obr->payee_id,
            'office' => $obr->office,
            'address' => $obr->address ?? null,
            'responsibility_center_id' => $obr->responsibility_center_id,
            'total_amount' => $obr->total_amount ?? 0.00,
            'explanation' => $obr->particulars,
        ]);

        $this->logRepository->create([
            'message' => 'Disbursement voucher successfully created.',
            'log_id' => $disbursementVoucher->id,
            'log_module' => 'dv',
            'data' => $disbursementVoucher,
        ]);

        $purchaseOrder = PurchaseOrder::find($obr->purchase_order_id);

        if ($purchaseOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::OBLIGATED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'obligated_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' order successfully marked as "Obligated".',
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);
        }

        $obr->update([
            'status' => ObligationRequestStatus::OBLIGATED,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'obligated_at', $obr->status_timestamps
            ),
        ]);

        $this->logRepository->create([
            'message' => 'Obligation request successfully marked as "Obligated".',
            'log_id' => $obr->id,
            'log_module' => 'obr',
            'data' => $obr,
        ]);

        $obr->load(['fpps', 'accounts']);

        return $obr;
    }

    public function logError(string $message, \Throwable $th, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'obr',
            'data' => $data,
        ], isError: true);
    }

    public function log(string $message, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'log_module' => 'obr',
            'data' => $data,
        ]);
    }
}
