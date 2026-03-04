<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\PurchaseRequestStatus;
use App\Enums\RequestQuotationStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\RequestQuotationRepositoryInterface;
use App\Models\FundingSource;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RequestQuotation;
use App\Models\RequestQuotationCanvasser;
use App\Models\RequestQuotationItem;
use App\Models\User;
use App\Repositories\LogRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class RequestQuotationService
{
    public function __construct(
        protected RequestQuotationRepositoryInterface $repository,
        protected LogRepository $logRepository,
        protected NotificationRepository $notificationRepository
    ) {}

    public function getAll(array $filters): LengthAwarePaginator
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
                'rfqs' => fn ($query) => $query->select(
                    'id',
                    'purchase_request_id',
                    'batch',
                    'rfq_no',
                    'rfq_date',
                    'signed_type',
                    'supplier_id',
                    'status'
                )
                    ->orderBy('batch')
                    ->orderByRaw("CAST(REPLACE(rfq_no, '-', '') AS VARCHAR) asc"),
                'rfqs.supplier:id,supplier_name',
                'rfqs.canvassers',
                'rfqs.canvassers.user:id,firstname,lastname',
            ])->whereIn('status', [
                PurchaseRequestStatus::APPROVED,
                PurchaseRequestStatus::FOR_CANVASSING,
                PurchaseRequestStatus::FOR_RECANVASSING,
                PurchaseRequestStatus::FOR_ABSTRACT,
                PurchaseRequestStatus::PARTIALLY_AWARDED,
                PurchaseRequestStatus::AWARDED,
                PurchaseRequestStatus::COMPLETED,
            ]);

        if (! empty($search)) {
            $purchaseRequests = $purchaseRequests->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('pr_no', 'ILIKE', "%{$search}%")
                    ->orWhere('pr_date', 'ILIKE', "%{$search}%")
                    ->orWhere('sai_no', 'ILIKE', "%{$search}%")
                    ->orWhere('sai_date', 'ILIKE', "%{$search}%")
                    ->orWhere('alobs_no', 'ILIKE', "%{$search}%")
                    ->orWhere('alobs_date', 'ILIKE', "%{$search}%")
                    ->orWhere('purpose', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('funding_source', 'title', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('section', 'section_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('requestor', fn ($query) => $query->where('firstname', 'ILIKE', "%{$search}%")
                        ->orWhere('lastname', 'ILIKE', "%{$search}%"))
                    ->orWhereRelation('signatory_cash_available.user', fn ($query) => $query->where('firstname', 'ILIKE', "%{$search}%")
                        ->orWhere('lastname', 'ILIKE', "%{$search}%"))
                    ->orWhereRelation('signatory_approval.user', fn ($query) => $query->where('firstname', 'ILIKE', "%{$search}%")
                        ->orWhere('lastname', 'ILIKE', "%{$search}%"))
                    ->orWhereRelation('rfqs', fn ($query) => $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                        ->orWhere('rfq_no', 'ILIKE', "%{$search}%")
                        ->orWhere('rfq_date', 'ILIKE', "%{$search}%"))
                    ->orWhereRelation('rfqs.canvassers.user', fn ($query) => $query->where('firstname', 'ILIKE', "%{$search}%")
                        ->orWhere('lastname', 'ILIKE', "%{$search}%"))
                    ->orWhereRelation('rfqs.signatory_approval.user', fn ($query) => $query->where('firstname', 'ILIKE', "%{$search}%")
                        ->orWhere('lastname', 'ILIKE', "%{$search}%"));
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

    public function getById(string $id): ?RequestQuotation
    {
        return RequestQuotation::with([
            'supplier:id,supplier_name,address',
            'signatory_approval:id,user_id',
            'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_approval.detail' => fn ($query) => $query->where('document', 'rfq')
                ->where('signatory_type', 'approval'),
            'canvassers',
            'canvassers.user:id,firstname,lastname,position_id,allow_signature,signature',
            'canvassers.user.position:id,position_name',
            'items' => fn ($query) => $query->orderBy(
                PurchaseRequestItem::select('item_sequence')
                    ->whereColumn('request_quotation_items.pr_item_id', 'purchase_request_items.id'),
                'asc'
            ),
            'items.pr_item:id,item_sequence,quantity,description,stock_no,awarded_to_id',
            'purchase_request:id,pr_no,purpose,funding_source_id',
            'purchase_request.funding_source:id,title,location_id',
            'purchase_request.funding_source.location:id,location_name',
        ])->find($id);
    }

    public function create(array $data): array
    {
        $purchaseRequest = PurchaseRequest::find($data['purchase_request_id']);

        if ($purchaseRequest->rfq_batch > 1) {
            throw new \Exception('Cannot create RFQ: Abstract of Bids and Quotation is ongoing or completed.');
        }

        $copies = $data['copies'] ?? 1;
        $data['vat_registered'] = isset($data['vat_registered'])
            ? filter_var($data['vat_registered'], FILTER_VALIDATE_BOOLEAN)
            : null;

        $createdRfqs = [];

        for ($copy = 1; $copy <= $copies; $copy++) {
            $existingSupplierCount = ! empty($data['supplier_id'])
                ? RequestQuotation::where('supplier_id', $data['supplier_id'])
                    ->where('purchase_request_id', $data['purchase_request_id'])
                    ->where('batch', $purchaseRequest->rfq_batch)
                    ->where('status', '!=', RequestQuotationStatus::CANCELLED)
                    ->count()
                : 0;

            if ($existingSupplierCount > 0) {
                throw new \Exception('RFQ creation failed due to an existing RFQ with the supplier.');
            }

            $requestQuotation = RequestQuotation::create(array_merge(
                $data,
                [
                    'batch' => $purchaseRequest->rfq_batch,
                    'status' => RequestQuotationStatus::DRAFT,
                    'status_timestamps' => StatusTimestampsHelper::generate('draft_at', null),
                ]
            ));

            foreach ($data['items'] ?? [] as $item) {
                RequestQuotationItem::create([
                    'request_quotation_id' => $requestQuotation->id,
                    'pr_item_id' => $item['pr_item_id'],
                    'supplier_id' => $data['supplier_id'],
                    'included' => $item['included'],
                ]);
            }

            foreach ($data['canvassers'] ?? [] as $userId) {
                RequestQuotationCanvasser::create([
                    'request_quotation_id' => $requestQuotation->id,
                    'user_id' => $userId,
                ]);
            }

            $createdRfqs[] = $requestQuotation->id;

            $this->logRepository->create([
                'message' => 'Request for quotation created successfully.',
                'log_id' => $requestQuotation->id,
                'log_module' => 'rfq',
                'data' => $requestQuotation,
            ]);
        }

        $purchaseRequest->load([
            'rfqs' => fn ($query) => $query->where('batch', $purchaseRequest->rfq_batch),
            'rfqs.items',
        ]);

        return [
            'purchase_request' => $purchaseRequest,
            'rfq_ids' => $createdRfqs,
        ];
    }

    public function update(RequestQuotation $requestQuotation, array $data): RequestQuotation
    {
        $currentStatus = $requestQuotation->status instanceof RequestQuotationStatus
            ? $requestQuotation->status
            : RequestQuotationStatus::from($requestQuotation->status);
        $purchaseRequest = PurchaseRequest::find($requestQuotation->purchase_request_id);

        $existingSupplierCount = ! empty($data['supplier_id'])
            ? RequestQuotation::where('supplier_id', $data['supplier_id'])
                ->where('purchase_request_id', $requestQuotation->purchase_request_id)
                ->where('batch', $purchaseRequest->rfq_batch)
                ->where('status', '!=', RequestQuotationStatus::CANCELLED)
                ->count()
            : 0;

        if ($existingSupplierCount > 0
            && (! empty($data['supplier_id']) && $requestQuotation->supplier_id !== $data['supplier_id'])
            && $currentStatus !== RequestQuotationStatus::COMPLETED) {
            throw new \Exception('Request quotation update failed due to an existing RFQ with the supplier.');
        }

        if ($currentStatus === RequestQuotationStatus::CANCELLED) {
            throw new \Exception('Request for quotation update failed, already cancelled.');
        }

        $data['vat_registered'] = isset($data['vat_registered'])
            ? filter_var($data['vat_registered'], FILTER_VALIDATE_BOOLEAN)
            : null;

        $grandTotalCost = 0;

        RequestQuotationItem::where('request_quotation_id', $requestQuotation->id)->delete();
        RequestQuotationCanvasser::where('request_quotation_id', $requestQuotation->id)->delete();

        foreach ($data['items'] ?? [] as $item) {
            $quantity = intval($item['quantity']);
            $unitCost = isset($item['unit_cost']) && ! empty($item['unit_cost'])
                ? floatval($item['unit_cost'])
                : null;
            $cost = round($quantity * ($unitCost ?? 0), 2);

            RequestQuotationItem::create([
                'request_quotation_id' => $requestQuotation->id,
                'pr_item_id' => $item['pr_item_id'],
                'supplier_id' => $data['supplier_id'],
                'brand_model' => $item['brand_model'],
                'unit_cost' => $unitCost,
                'total_cost' => $cost,
                'included' => $item['included'],
            ]);

            $grandTotalCost += $cost;
        }

        foreach ($data['canvassers'] ?? [] as $userId) {
            RequestQuotationCanvasser::create([
                'request_quotation_id' => $requestQuotation->id,
                'user_id' => $userId,
            ]);
        }

        $requestQuotation->update(array_merge(
            $data,
            [
                'supplier_id' => $currentStatus === RequestQuotationStatus::COMPLETED
                    ? $requestQuotation->supplier_id
                    : $data['supplier_id'],
                'grand_total_cost' => $grandTotalCost,
            ]
        ));

        $requestQuotation->load(['items', 'canvassers']);

        $this->logRepository->create([
            'message' => 'Request for quotation updated successfully.',
            'log_id' => $requestQuotation->id,
            'log_module' => 'rfq',
            'data' => $requestQuotation,
        ]);

        return $requestQuotation;
    }

    public function issueCanvassing(RequestQuotation $requestQuotation): array
    {
        $requestQuotation->update([
            'status' => RequestQuotationStatus::CANVASSING,
            'status_timestamps' => StatusTimestampsHelper::generate('canvassing_at', $requestQuotation->status_timestamps),
        ]);

        $purchaseRequest = PurchaseRequest::find($requestQuotation->purchase_request_id);
        $prCurrentStatus = $purchaseRequest->status instanceof PurchaseRequestStatus
            ? $purchaseRequest->status
            : PurchaseRequestStatus::from($purchaseRequest->status);

        $prMessage = null;

        if ($purchaseRequest
            && ($prCurrentStatus === PurchaseRequestStatus::APPROVED
                || $prCurrentStatus === PurchaseRequestStatus::FOR_ABSTRACT)) {
            $newStatus = PurchaseRequestStatus::FOR_CANVASSING;
            $prMessage = 'Purchase request successfully marked as "For Canvassing".';

            $purchaseRequest->update([
                'status' => $newStatus,
                'status_timestamps' => StatusTimestampsHelper::generate('canvassing_at', $purchaseRequest->status_timestamps),
            ]);

            $this->notificationRepository->notify(NotificationType::PR_CAMVASSING, [
                'pr' => $purchaseRequest, 'rfq' => $requestQuotation,
            ]);

            $this->logRepository->create([
                'message' => $prMessage,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ]);
        }

        $this->logRepository->create([
            'message' => 'Request for quotation successfully marked as "Canvassing".',
            'log_id' => $requestQuotation->id,
            'log_module' => 'rfq',
            'data' => $requestQuotation,
        ]);

        return [
            'request_quotation' => $requestQuotation->fresh('purchase_request'),
            'pr_updated' => $prMessage !== null,
            'pr_message' => $prMessage,
        ];
    }

    public function canvassComplete(RequestQuotation $requestQuotation): RequestQuotation
    {
        if (! $requestQuotation->supplier_id) {
            throw new \Exception('Request for quotation could not be marked as completed. Supplier not set.');
        }

        $requestQuotation->update([
            'status' => RequestQuotationStatus::COMPLETED,
            'status_timestamps' => StatusTimestampsHelper::generate('completed_at', $requestQuotation->status_timestamps),
        ]);

        $this->logRepository->create([
            'message' => 'Request for quotation successfully marked as "Completed".',
            'log_id' => $requestQuotation->id,
            'log_module' => 'rfq',
            'data' => $requestQuotation,
        ]);

        return $requestQuotation;
    }

    public function cancel(RequestQuotation $requestQuotation): RequestQuotation
    {
        $requestQuotation->update([
            'status' => RequestQuotationStatus::CANCELLED,
            'status_timestamps' => StatusTimestampsHelper::generate('cancelled_at', $requestQuotation->status_timestamps),
        ]);

        $this->logRepository->create([
            'message' => 'Request for quotation successfully marked as "Cancelled".',
            'log_id' => $requestQuotation->id,
            'log_module' => 'rfq',
            'data' => $requestQuotation,
        ]);

        return $requestQuotation;
    }

    public function logError(string $message, \Throwable $th, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'details' => $th->getMessage(),
            'log_module' => 'rfq',
            'data' => $data,
        ], isError: true);
    }

    public function log(string $message, array $data = []): void
    {
        $this->logRepository->create([
            'message' => $message,
            'log_module' => 'rfq',
            'data' => $data,
        ]);
    }
}
