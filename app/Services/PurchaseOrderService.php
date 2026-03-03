<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Helpers\RequiredFieldsValidationHelper;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\PurchaseOrderRepositoryInterface;
use App\Models\FundingSource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Repositories\InspectionAcceptanceReportRepository;
use App\Repositories\LogRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseOrderService
{
    public function __construct(
        protected PurchaseOrderRepositoryInterface $repository,
        protected LogRepository $logRepository,
        protected InspectionAcceptanceReportRepository $iarRepository
    ) {}

    public function getAll(array $filters, ?User $user = null): LengthAwarePaginator
    {
        $search = trim($filters['search'] ?? '');
        $perPage = $filters['per_page'] ?? 50;
        $columnSort = $filters['column_sort'] ?? 'pr_no';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $purchaseRequests = PurchaseRequest::query()
            ->select('id', 'pr_no', 'pr_date', 'funding_source_id', 'purpose', 'status', 'requested_by_id')
            ->with([
                'funding_source:id,title',
                'requestor:id,firstname,lastname',
                'pos' => fn ($query) => $query->select(
                    'id',
                    'purchase_request_id',
                    'po_no',
                    'po_date',
                    'mode_procurement_id',
                    'supplier_id',
                    'total_amount',
                    'status'
                )
                    ->orderByRaw("CAST(REPLACE(po_no, '-', '') AS VARCHAR) asc"),
                'pos.mode_procurement:id,mode_name',
                'pos.supplier:id,supplier_name',
            ])->whereIn('status', [
                PurchaseRequestStatus::PARTIALLY_AWARDED,
                PurchaseRequestStatus::AWARDED,
                PurchaseRequestStatus::COMPLETED,
            ]);

        if ($user && ! $user->tokenCan('super:*')
            && ! $user->tokenCan('head:*')
            && ! $user->tokenCan('supply:*')
            && ! $user->tokenCan('budget:*')
            && ! $user->tokenCan('accountant:*')
        ) {
            $purchaseRequests = $purchaseRequests->where('requested_by_id', $user->id);
        }

        if (! empty($search)) {
            $purchaseRequests = $purchaseRequests->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('pr_no', 'ILIKE', "%{$search}%")
                    ->orWhere('purpose', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('funding_source', 'title', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('requestor', fn ($query) => $query->where('firstname', 'ILIKE', "%{$search}%")
                        ->orWhere('lastname', 'ILIKE', "%{$search}%"))
                    ->orWhereRelation('pos', fn ($query) => $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                        ->orWhere('po_no', 'ILIKE', "%{$search}%"))
                    ->orWhereRelation('pos.supplier', fn ($query) => $query->where('supplier_name', 'ILIKE', "%{$search}%"));
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            match ($columnSort) {
                'pr_no' => $purchaseRequests->orderByRaw("CAST(REPLACE(pr_no, '-', '') AS INTEGER) {$sortDirection}"),
                'pr_date_formatted' => $purchaseRequests->orderBy('pr_date', $sortDirection),
                'funding_source_title' => $purchaseRequests->orderBy(
                    FundingSource::select('title')->whereColumn('funding_sources.id', 'purchase_requests.funding_source_id'),
                    $sortDirection
                ),
                'purpose_formatted' => $purchaseRequests->orderBy('purpose', $sortDirection),
                'requestor_fullname' => $purchaseRequests->orderBy(
                    User::select('firstname')->whereColumn('users.id', 'purchase_requests.requested_by_id'),
                    $sortDirection
                ),
                'status_formatted' => $purchaseRequests->orderBy('status', $sortDirection),
                default => $purchaseRequests->orderBy($columnSort, $sortDirection),
            };
        }

        return $purchaseRequests->paginate($perPage);
    }

    public function getAllUngrouped(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $search = trim($filters['search'] ?? '');
        $perPage = $filters['per_page'] ?? 50;
        $hasSuppliesOnly = $filters['has_supplies_only'] ?? false;
        $showAll = $filters['show_all'] ?? false;

        $purchaseOrders = PurchaseOrder::query()
            ->select('id', 'po_no')
            ->whereNotIn('status', [
                PurchaseOrderStatus::PENDING,
                PurchaseOrderStatus::APPROVED,
                PurchaseOrderStatus::ISSUED,
                PurchaseOrderStatus::FOR_DELIVERY,
                PurchaseOrderStatus::DELIVERED,
            ]);

        if (! empty($search)) {
            $purchaseOrders = $purchaseOrders->where(fn ($query) => $query->where('po_no', 'ILIKE', "%{$search}%"));
        }

        if ($hasSuppliesOnly) {
            $purchaseOrders = $purchaseOrders->has('supplies');
        }

        return $showAll ? $purchaseOrders->get() : $purchaseOrders->limit($perPage)->get();
    }

    public function getById(string $id): ?PurchaseOrder
    {
        return PurchaseOrder::with([
            'supplier:id,supplier_name,address,tin_no',
            'mode_procurement:id,mode_name',
            'place_delivery:id,location_name',
            'delivery_term:id,term_name',
            'payment_term:id,term_name',
            'items' => fn ($query) => $query->orderBy(
                PurchaseRequestItem::select('item_sequence')
                    ->whereColumn('purchase_order_items.pr_item_id', 'purchase_request_items.id'),
                'asc'
            ),
            'items.pr_item:id,unit_issue_id,item_sequence,quantity,description,stock_no',
            'items.pr_item.unit_issue:id,unit_name',
            'signatory_approval:id,user_id',
            'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_approval.detail' => fn ($query) => $query->where('document', 'po')
                ->where('signatory_type', 'authorized_official'),
            'purchase_request:id,department_id,section_id,sai_no,sai_date,requested_by_id,purpose',
            'purchase_request.department:id,department_name',
            'purchase_request.section:id,section_name',
            'purchase_request.requestor:id,firstname,middlename,lastname',
            'obligation_request',
            'disbursement_voucher',
        ])->find($id);
    }

    public function createOrUpdate(array $data, ?PurchaseOrder $purchaseOrder = null): PurchaseOrder
    {
        return $this->repository->storeUpdate($data, $purchaseOrder);
    }

    public function pending(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $currentStatus = $purchaseOrder->status instanceof PurchaseOrderStatus
            ? $purchaseOrder->status
            : PurchaseOrderStatus::from($purchaseOrder->status);

        if ($currentStatus !== PurchaseOrderStatus::DRAFT) {
            throw new \Exception('Failed to set the Purchase Order to pending. It may already be set to pending or processing status.');
        }

        $requiredFields = [
            'po_date' => 'PO Date',
            'place_delivery_id' => 'Place of Delivery',
            'delivery_date' => 'Delivery Date',
            'delivery_term_id' => 'Delivery Term',
            'payment_term_id' => 'Payment Term',
            'total_amount_words' => 'Total Amount in Words',
            'sig_approval_id' => 'Approval Signatory',
        ];

        $missingFields = RequiredFieldsValidationHelper::getMissingFields($requiredFields, $purchaseOrder);

        if (! empty($missingFields)) {
            $this->logRepository->create([
                'message' => 'Cannot set purchase order to pending. Missing required fields.',
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => ['missing_fields' => $missingFields],
            ], isError: true);

            throw new \Exception('Cannot set purchase order to pending. Please fill out the following fields first: '.implode(', ', $missingFields));
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::PENDING,
            'status_timestamps' => StatusTimestampsHelper::generate('pending_at', $purchaseOrder->status_timestamps),
        ]);

        $this->logRepository->create([
            'message' => 'Purchase order successfully marked as pending for approval.',
            'log_id' => $purchaseOrder->id,
            'log_module' => 'po',
            'data' => $purchaseOrder,
        ]);

        return $purchaseOrder;
    }

    public function approve(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $currentStatus = $purchaseOrder->status instanceof PurchaseOrderStatus
            ? $purchaseOrder->status
            : PurchaseOrderStatus::from($purchaseOrder->status);

        if ($currentStatus !== PurchaseOrderStatus::PENDING) {
            throw new \Exception('Failed to set the Purchase Order to approved. It may already be approved or still in draft status.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::APPROVED,
            'status_timestamps' => StatusTimestampsHelper::generate('approved_at', $purchaseOrder->status_timestamps),
        ]);

        $this->logRepository->create([
            'message' => 'Purchase order successfully marked as "Approved".',
            'log_id' => $purchaseOrder->id,
            'log_module' => 'po',
            'data' => $purchaseOrder,
        ]);

        return $purchaseOrder;
    }

    public function issue(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $currentStatus = $purchaseOrder->status instanceof PurchaseOrderStatus
            ? $purchaseOrder->status
            : PurchaseOrderStatus::from($purchaseOrder->status);

        if ($currentStatus !== PurchaseOrderStatus::APPROVED) {
            throw new \Exception('Failed to set the Purchase Order to issued. It may already be issued or still in draft status.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::ISSUED,
            'status_timestamps' => StatusTimestampsHelper::generate('issued_at', $purchaseOrder->status_timestamps),
        ]);

        $this->logRepository->create([
            'message' => 'Purchase order successfully issued to supplier.',
            'log_id' => $purchaseOrder->id,
            'log_module' => 'po',
            'data' => $purchaseOrder,
        ]);

        return $purchaseOrder;
    }

    public function receive(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $currentStatus = $purchaseOrder->status instanceof PurchaseOrderStatus
            ? $purchaseOrder->status
            : PurchaseOrderStatus::from($purchaseOrder->status);

        if ($currentStatus !== PurchaseOrderStatus::ISSUED) {
            throw new \Exception('Failed to receive and set the Purchase Order to For Delivery. It may already be for delivery or still in draft status.');
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::FOR_DELIVERY,
            'status_timestamps' => StatusTimestampsHelper::generate('for_delivery_at', $purchaseOrder->status_timestamps),
        ]);

        $this->logRepository->create([
            'message' => 'Purchase order successfully received and marked as "For Delivery".',
            'log_id' => $purchaseOrder->id,
            'log_module' => 'po',
            'data' => $purchaseOrder,
        ]);

        return $purchaseOrder;
    }

    public function delivered(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $currentStatus = $purchaseOrder->status instanceof PurchaseOrderStatus
            ? $purchaseOrder->status
            : PurchaseOrderStatus::from($purchaseOrder->status);

        if ($currentStatus !== PurchaseOrderStatus::FOR_DELIVERY) {
            throw new \Exception('Failed to set the Purchase Order to delivered. It may already be delivered or still in draft status.');
        }

        $purchaseOrder->load('items');

        $inspectionAcceptanceReport = $this->iarRepository->storeUpdate([
            'purchase_request_id' => $purchaseOrder->purchase_request_id,
            'purchase_order_id' => $purchaseOrder->id,
            'supplier_id' => $purchaseOrder->supplier_id,
            'items' => $purchaseOrder->items->map(fn ($item) => [
                'pr_item_id' => $item->pr_item_id,
                'po_item_id' => $item->id,
            ]),
        ]);

        $this->logRepository->create([
            'message' => 'Inspection Acceptance Report created successfully.',
            'log_id' => $inspectionAcceptanceReport->id,
            'log_module' => 'iar',
            'data' => $inspectionAcceptanceReport,
        ]);

        $purchaseOrder->update([
            'status' => PurchaseOrderStatus::DELIVERED,
            'status_timestamps' => StatusTimestampsHelper::generate('delivered_at', $purchaseOrder->status_timestamps),
        ]);

        $this->logRepository->create([
            'message' => 'Purchase order successfully set to "Delivered".',
            'log_id' => $purchaseOrder->id,
            'log_module' => 'po',
            'data' => $purchaseOrder,
        ]);

        return $purchaseOrder;
    }

    public function getLogger(): LogRepository
    {
        return $this->logRepository;
    }

    public function getIarRepository(): InspectionAcceptanceReportRepository
    {
        return $this->iarRepository;
    }

    public function logError(string $message, \Throwable $th, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'po',
            'data' => $data,
        ], isError: true);
    }

    public function log(string $message, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'log_module' => 'po',
            'data' => $data,
        ]);
    }
}
