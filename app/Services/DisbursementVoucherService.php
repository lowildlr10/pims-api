<?php

namespace App\Services;

use App\Enums\DisbursementVoucherStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\DisbursementVoucherInterface;
use App\Models\DisbursementVoucher;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Repositories\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class DisbursementVoucherService
{
    public function __construct(
        protected DisbursementVoucherInterface $repository,
        protected LogRepository $logRepository
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

    public function getById(string $id): ?DisbursementVoucher
    {
        return $this->repository->getById($id);
    }

    public function create(array $data): DisbursementVoucher
    {
        $dv = $this->repository->storeUpdate($data);

        $this->logRepository->create([
            'message' => 'Disbursement voucher created successfully.',
            'log_id' => $dv->id,
            'log_module' => 'dv',
            'data' => $dv,
        ]);

        return $dv;
    }

    public function update(DisbursementVoucher $dv, array $data): DisbursementVoucher
    {
        $currentStatus = $dv->status;
        $status = $currentStatus;

        if ($currentStatus === DisbursementVoucherStatus::DRAFT
            || $currentStatus === DisbursementVoucherStatus::DISAPPROVED) {
            $status = DisbursementVoucherStatus::DRAFT;
        }

        $dv = $this->repository->storeUpdate(
            array_merge($data, [
                'status' => $status,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'draft_at', null
                ),
            ]),
            $dv
        );

        $this->logRepository->create([
            'message' => 'Disbursement voucher updated successfully.',
            'log_id' => $dv->id,
            'log_module' => 'dv',
            'data' => $dv,
        ]);

        return $dv;
    }

    public function pending(DisbursementVoucher $dv): DisbursementVoucher
    {
        $currentStatus = $dv->status;

        if ($currentStatus !== DisbursementVoucherStatus::DRAFT) {
            $message = 'Failed to set the disbursement voucher to pending for disbursement. It may already be set to pending or processing status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $dv->id,
                'log_module' => 'dv',
                'data' => $dv,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseOrder = PurchaseOrder::find($dv->purchase_order_id);

        if ($purchaseOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::FOR_DISBURSEMENT,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'for_disbursement_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' order successfully marked as for disbursement.',
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);
        }

        $dv->update([
            'disapproved_reason' => null,
            'status' => DisbursementVoucherStatus::PENDING,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'pending_at', $dv->status_timestamps
            ),
        ]);

        $this->logRepository->create([
            'message' => 'Disbursement voucher successfully marked as pending for disbursement.',
            'log_id' => $dv->id,
            'log_module' => 'dv',
            'data' => $dv,
        ]);

        return $dv;
    }

    public function disapprove(DisbursementVoucher $dv, ?string $reason = null): DisbursementVoucher
    {
        $currentStatus = $dv->status;

        if ($currentStatus !== DisbursementVoucherStatus::PENDING) {
            $message = 'Failed to set the disbursement voucher to "Disapproved". It may already be obligated or still in draft status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $dv->id,
                'log_module' => 'dv',
                'data' => $dv,
            ], isError: true);

            throw new \Exception($message);
        }

        $dv->update([
            'disapproved_reason' => $reason,
            'status' => DisbursementVoucherStatus::DISAPPROVED,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'disapproved_at', $dv->status_timestamps
            ),
        ]);

        $this->logRepository->create([
            'message' => 'Disbursement voucher successfully marked as "Disapproved".',
            'log_id' => $dv->id,
            'log_module' => 'dv',
            'data' => $dv,
        ]);

        return $dv;
    }

    public function disburse(DisbursementVoucher $dv): DisbursementVoucher
    {
        $currentStatus = $dv->status;

        if ($currentStatus !== DisbursementVoucherStatus::PENDING) {
            $message = 'Failed to set the Disbursement Voucher to "For Payment". It may already be set to payment or still in draft status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $dv->id,
                'log_module' => 'dv',
                'data' => $dv,
            ], isError: true);

            throw new \Exception($message);
        }

        $purchaseOrder = PurchaseOrder::find($dv->purchase_order_id);

        if ($purchaseOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::FOR_PAYMENT,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'for_payment_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' order successfully marked as "For Payment".',
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);
        }

        $dv->update([
            'status' => DisbursementVoucherStatus::FOR_PAYMENT,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'for_payment_at', $dv->status_timestamps
            ),
        ]);

        $this->logRepository->create([
            'message' => 'Disbursement voucher successfully marked as "For Payment".',
            'log_id' => $dv->id,
            'log_module' => 'dv',
            'data' => $dv,
        ]);

        return $dv;
    }

    public function paid(DisbursementVoucher $dv): DisbursementVoucher
    {
        $currentStatus = $dv->status;

        if ($currentStatus !== DisbursementVoucherStatus::FOR_PAYMENT) {
            $message = 'Failed to set the Disbursement Voucher to "Paid". It may already be paid or still in draft status.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $dv->id,
                'log_module' => 'dv',
                'data' => $dv,
            ], isError: true);

            throw new \Exception($message);
        }

        $dv->update([
            'status' => DisbursementVoucherStatus::PAID,
            'status_timestamps' => StatusTimestampsHelper::generate(
                'paid_at', $dv->status_timestamps
            ),
        ]);

        $this->logRepository->create([
            'message' => 'Disbursement voucher successfully marked as "Paid".',
            'log_id' => $dv->id,
            'log_module' => 'dv',
            'data' => $dv,
        ]);

        $purchaseOrder = PurchaseOrder::find($dv->purchase_order_id);

        if ($purchaseOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::COMPLETED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'completed_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' order successfully marked as "Completed".',
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);
        }

        $po = PurchaseOrder::selectRaw('
            COUNT(*) as po_total_count,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as po_completed_count
        ', [PurchaseOrderStatus::COMPLETED])
            ->where('purchase_request_id', $dv->purchase_request_id)
            ->first();

        if ($po->po_total_count === $po->po_completed_count) {
            $purchaseRequest = PurchaseRequest::find($dv->purchase_request_id);

            if (! empty($purchaseRequest)) {
                $purchaseRequest->update([
                    'status' => PurchaseRequestStatus::COMPLETED,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'completed_at', $purchaseRequest->status_timestamps
                    ),
                ]);
                $this->logRepository->create([
                    'message' => 'Purchase request successfully marked as "Completed"',
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest,
                ]);
            }
        }

        return $dv;
    }

    public function logError(string $message, \Throwable $th, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'dv',
            'data' => $data,
        ], isError: true);
    }

    public function log(string $message, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'log_module' => 'dv',
            'data' => $data,
        ]);
    }
}
