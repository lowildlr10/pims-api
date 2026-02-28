<?php

namespace App\Services;

use App\Enums\AbstractQuotationStatus;
use App\Enums\PurchaseRequestStatus;
use App\Enums\RequestQuotationStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Interfaces\AbstractQuotationRepositoryInterface;
use App\Models\AbstractQuotation;
use App\Models\FundingSource;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RequestQuotation;
use App\Models\User;
use App\Repositories\LogRepository;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class AbstractQuotationService
{
    public function __construct(
        protected AbstractQuotationRepositoryInterface $repository,
        protected LogRepository $logRepository
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
                'aoqs' => function ($query) {
                    $query->select(
                        'id',
                        'purchase_request_id',
                        'solicitation_no',
                        'solicitation_date',
                        'abstract_no',
                        'opened_on',
                        'mode_procurement_id',
                        'bac_action',
                        'status'
                    )
                        ->orderByRaw("CAST(REPLACE(abstract_no, '-', '') AS VARCHAR) asc");
                },
                'aoqs.mode_procurement:id,mode_name',
            ])->whereIn('status', [
                PurchaseRequestStatus::FOR_RECANVASSING,
                PurchaseRequestStatus::FOR_ABSTRACT,
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
                    ->orWhere('pr_date', 'ILIKE', "%{$search}%")
                    ->orWhere('purpose', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('funding_source', 'title', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('requestor', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('aoqs', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                            ->orWhere('solicitation_no', 'ILIKE', "%{$search}%")
                            ->orWhere('abstract_no', 'ILIKE', "%{$search}%")
                            ->orWhere('bac_action', 'ILIKE', "%{$search}%");
                    });
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

    public function getById(string $id): ?AbstractQuotation
    {
        return AbstractQuotation::with([
            'bids_awards_committee:id,committee_name',
            'mode_procurement:id,mode_name',
            'signatory_twg_chairperson:id,user_id',
            'signatory_twg_chairperson.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_twg_chairperson.detail' => fn ($query) => $query->where('document', 'aoq')
                ->where('signatory_type', 'twg_chairperson'),
            'signatory_twg_member_1:id,user_id',
            'signatory_twg_member_1.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_twg_member_1.detail' => fn ($query) => $query->where('document', 'aoq')
                ->where('signatory_type', 'twg_member_1'),
            'signatory_twg_member_2:id,user_id',
            'signatory_twg_member_2.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_twg_member_2.detail' => fn ($query) => $query->where('document', 'aoq')
                ->where('signatory_type', 'twg_member_2'),
            'signatory_chairman:id,user_id',
            'signatory_chairman.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_chairman.detail' => fn ($query) => $query->where('document', 'aoq')
                ->where('signatory_type', 'chairman'),
            'signatory_vice_chairman:id,user_id',
            'signatory_vice_chairman.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_vice_chairman.detail' => fn ($query) => $query->where('document', 'aoq')
                ->where('signatory_type', 'vice_chairman'),
            'signatory_member_1:id,user_id',
            'signatory_member_1.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_member_1.detail' => fn ($query) => $query->where('document', 'aoq')
                ->where('signatory_type', 'member_1'),
            'signatory_member_2:id,user_id',
            'signatory_member_2.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_member_2.detail' => fn ($query) => $query->where('document', 'aoq')
                ->where('signatory_type', 'member_2'),
            'signatory_member_3:id,user_id',
            'signatory_member_3.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_member_3.detail' => fn ($query) => $query->where('document', 'aoq')
                ->where('signatory_type', 'member_3'),
            'items' => fn ($query) => $query->orderBy(
                PurchaseRequestItem::select('item_sequence')
                    ->whereColumn('abstract_quotation_items.pr_item_id', 'purchase_request_items.id'),
                'asc'
            ),
            'items.awardee:id,supplier_name',
            'items.pr_item:id,unit_issue_id,item_sequence,quantity,description,stock_no',
            'items.pr_item.unit_issue:id,unit_name',
            'items.details',
            'items.details.supplier:id,supplier_name',
            'purchase_request:id,purpose',
        ])->find($id);
    }

    public function createOrUpdate(array $data, ?AbstractQuotation $abstractQuotation = null): AbstractQuotation
    {
        return $this->repository->storeUpdate($data, $abstractQuotation);
    }

    public function pending(AbstractQuotation $abstractQuotation): AbstractQuotation
    {
        $currentStatus = $abstractQuotation->status instanceof AbstractQuotationStatus
            ? $abstractQuotation->status
            : AbstractQuotationStatus::from($abstractQuotation->status);

        if ($currentStatus !== AbstractQuotationStatus::DRAFT) {
            throw new \Exception('Failed to set the abstract of bids and quotation to pending. It is already pending or approved or awarded.');
        }

        $abstractQuotation->update([
            'status' => AbstractQuotationStatus::PENDING,
            'status_timestamps' => StatusTimestampsHelper::generate('pending_at', $abstractQuotation->status_timestamps),
        ]);

        $this->logRepository->create([
            'message' => 'Abstract of bids and quotation successfully marked as "Pending".',
            'log_id' => $abstractQuotation->id,
            'log_module' => 'aoq',
            'data' => $abstractQuotation,
        ]);

        return $abstractQuotation;
    }

    public function approve(AbstractQuotation $abstractQuotation): AbstractQuotation
    {
        $currentStatus = $abstractQuotation->status instanceof AbstractQuotationStatus
            ? $abstractQuotation->status
            : AbstractQuotationStatus::from($abstractQuotation->status);

        if ($currentStatus !== AbstractQuotationStatus::PENDING) {
            throw new \Exception('Failed to set the abstract of bids and quotation to approved. It may already be awarded or still in draft status.');
        }

        $abstractQuotation->update([
            'status' => AbstractQuotationStatus::APPROVED,
            'status_timestamps' => StatusTimestampsHelper::generate('approved_at', $abstractQuotation->status_timestamps),
        ]);

        $this->logRepository->create([
            'message' => 'Abstract of bids and quotation successfully marked as "Approved".',
            'log_id' => $abstractQuotation->id,
            'log_module' => 'aoq',
            'data' => $abstractQuotation,
        ]);

        return $abstractQuotation;
    }

    public function revert(AbstractQuotation $abstractQuotation): AbstractQuotation
    {
        $currentStatus = $abstractQuotation->status instanceof AbstractQuotationStatus
            ? $abstractQuotation->status
            : AbstractQuotationStatus::from($abstractQuotation->status);

        if ($currentStatus === AbstractQuotationStatus::APPROVED || $currentStatus === AbstractQuotationStatus::AWARDED) {
            throw new \Exception('Failed to revert changes from this abstract of bids and quotation. It may already be approved or awarded.');
        }

        $purchaseRequest = PurchaseRequest::with('items')->find($abstractQuotation->purchase_request_id);
        $rfqCompleted = RequestQuotation::where('purchase_request_id', $purchaseRequest->id)
            ->where('status', RequestQuotationStatus::COMPLETED)
            ->where('batch', $purchaseRequest->rfq_batch - 1)
            ->get();

        $updatedAoq = $this->repository->storeUpdate([
            'bids_awards_committee_id' => null,
            'mode_procurement_id' => null,
            'opened_on' => null,
            'bac_action' => null,
            'sig_twg_chairperson_id' => null,
            'sig_twg_member_1_id' => null,
            'sig_twg_member_2_id' => null,
            'sig_chairman_id' => null,
            'sig_vice_chairman_id' => null,
            'sig_member_1_id' => null,
            'sig_member_2_id' => null,
            'sig_member_3_id' => null,
            'status' => AbstractQuotationStatus::DRAFT,
            'status_timestamps' => StatusTimestampsHelper::generate('draft_at', $abstractQuotation->status_timestamps),
            'solicitation_no' => $rfqCompleted->first()?->rfq_no ?? '',
            'solicitation_date' => Carbon::now()->toDateString(),
            'items' => $purchaseRequest->items->map(fn (PurchaseRequestItem $item) => [
                'pr_item_id' => $item->id,
                'included' => empty($item->awarded_to),
                'document_type' => 'po',
                'details' => $rfqCompleted->map(fn (RequestQuotation $rfq) => [
                    'quantity' => $item->quantity,
                    'supplier_id' => $rfq->items->first()?->supplier_id,
                    'brand_model' => $rfq->items->first()?->brand_model,
                    'unit_cost' => $rfq->items->first()?->unit_cost,
                ]),
            ]),
        ], $abstractQuotation);

        $this->logRepository->create([
            'message' => 'Changes for this abstract of bids and quotation successfully reverted.',
            'log_id' => $abstractQuotation->id,
            'log_module' => 'aoq',
            'data' => $updatedAoq,
        ]);

        return $updatedAoq;
    }

    public function print(array $pageConfig, string $aoqId): array
    {
        return $this->repository->print($pageConfig, $aoqId);
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
            'log_module' => 'aoq',
            'data' => $data,
        ], isError: true);
    }

    public function log(string $message, ?string $logId = null, ?array $data = null): void
    {
        $this->logRepository->create([
            'message' => $message,
            'log_id' => $logId,
            'log_module' => 'aoq',
            'data' => $data,
        ]);
    }
}
