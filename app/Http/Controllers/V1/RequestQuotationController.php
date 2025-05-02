<?php

namespace App\Http\Controllers\V1;

use App\Enums\PurchaseRequestStatus;
use App\Enums\RequestQuotationStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RequestQuotation;
use App\Models\RequestQuotationCanvasser;
use App\Models\RequestQuotationItem;
use App\Models\User;
use App\Repositories\LogRepository;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RequestQuotationController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse | LengthAwarePaginator
    {
        $user = auth()->user();

        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'pr_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $purchaseRequests = PurchaseRequest::query()
            ->select('id', 'pr_no', 'pr_date', 'funding_source_id', 'purpose', 'status', 'requested_by_id')
            ->with([
                'funding_source:id,title',
                'requestor:id,firstname,lastname',

                'rfqs' => function ($query) {
                    $query->select(
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
                        ->orderByRaw("CAST(REPLACE(rfq_no, '-', '') AS VARCHAR) asc");
                },
                'rfqs.supplier:id,supplier_name',
                'rfqs.canvassers',
                'rfqs.canvassers.user:id,firstname,lastname'
            ])->whereIn('status', [
                PurchaseRequestStatus::APPROVED,
                PurchaseRequestStatus::FOR_CANVASSING,
                PurchaseRequestStatus::FOR_RECANVASSING,
                PurchaseRequestStatus::FOR_ABSTRACT,
                PurchaseRequestStatus::PARTIALLY_AWARDED,
                PurchaseRequestStatus::AWARDED,
                PurchaseRequestStatus::COMPLETED
            ]);

        if ($user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('supply:*')
            || $user->tokenCan('budget:*')
            || $user->tokenCan('accounting:*')
        ) {} else {
            $purchaseRequests = $purchaseRequests->where('requested_by_id', $user->id);
        }

        if (!empty($search)) {
            $purchaseRequests = $purchaseRequests->where(function($query) use ($search){
                $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                    ->orWhere('pr_no', 'ILIKE', "%{$search}%")
                    ->orWhere('pr_date', 'ILIKE', "%{$search}%")
                    ->orWhere('sai_no', 'ILIKE', "%{$search}%")
                    ->orWhere('sai_date', 'ILIKE', "%{$search}%")
                    ->orWhere('alobs_no', 'ILIKE', "%{$search}%")
                    ->orWhere('alobs_date', 'ILIKE', "%{$search}%")
                    ->orWhere('purpose', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('funding_source', 'title', 'ILIKE' , "%{$search}%")
                    ->orWhereRelation('section', 'section_name', 'ILIKE' , "%{$search}%")
                    ->orWhereRelation('requestor', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('signatory_cash_available.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('signatory_approval.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('rfqs', function ($query) use ($search) {
                        $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                            ->orWhere('rfq_no', 'ILIKE', "%{$search}%")
                            ->orWhere('rfq_date', 'ILIKE', "%{$search}%")
                            ->orWhere('status', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('rfqs.canvassers.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('rfqs.signatory_approval.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    });
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'pr_no':
                    $purchaseRequests = $purchaseRequests->orderByRaw("CAST(REPLACE(pr_no, '-', '') AS INTEGER) {$sortDirection}");
                    break;

                case 'pr_date_formatted':
                    $purchaseRequests = $purchaseRequests->orderBy('pr_date', $sortDirection);
                    break;

                case 'funding_source_title':
                    $purchaseRequests = $purchaseRequests->orderBy(
                        FundingSource::select('title')->whereColumn('funding_sources.id', 'purchase_requests.funding_source_id'),
                        $sortDirection
                    );
                    break;

                case 'purpose_formatted':
                    $purchaseRequests = $purchaseRequests->orderBy('purpose', $sortDirection);
                    break;

                case 'requestor_fullname':
                    $purchaseRequests = $purchaseRequests->orderBy(
                        User::select('firstname')->whereColumn('users.id', 'purchase_requests.requested_by_id'),
                        $sortDirection
                    );
                    break;

                case 'status_formatted':
                    $purchaseRequests = $purchaseRequests->orderBy('status', $sortDirection);
                    break;

                default:
                    $purchaseRequests = $purchaseRequests->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $purchaseRequests->paginate($perPage);
        } else {
            $purchaseRequests = $showAll
                ? $purchaseRequests->get()
                : $purchaseRequests = $purchaseRequests->limit($perPage)->get();

            return response()->json([
                'data' => $purchaseRequests
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'rfq_no' => 'required',
            'purchase_request_id' => 'required',
            'copies' => 'required|numeric|min:1|max:10',
            'signed_type' => 'required|string',
            'rfq_date' => 'required',
            'supplier_id' => 'nullable',
            'opening_dt' => 'nullable',
            'sig_approval_id' => 'required',
            'canvassers' => 'nullable|string',
            'items' => 'required|string',
            'vat_registered' =>  'nullable|in:true,false',
        ]);

        $copies = $validated['copies'] ?? 1;
        $validated['vat_registered'] = !empty($validated['vat_registered'])
            ? filter_var($validated['vat_registered'], FILTER_VALIDATE_BOOLEAN)
            : NULL;

        try {
            for ($copy = 1; $copy <= $copies; $copy++) {
                $message = 'Request for quotation created successfully.';
                $purchaseRequest = PurchaseRequest::find($validated['purchase_request_id']);

                $existingSupplierCount = !empty($validated['supplier_id'])
                    ? RequestQuotation::where('supplier_id', $validated['supplier_id'])
                        ->where('purchase_request_id', $validated['purchase_request_id'])
                        ->where('batch', $purchaseRequest->rfq_batch)
                        ->where('status', '!=', RequestQuotationStatus::CANCELLED)
                        ->count()
                    : 0;

                if ($existingSupplierCount > 0) {
                    $message = 'Request quotation creation failed due to an existing RFQ with the supplier.';

                    $this->logRepository->create([
                        'message' => $message,
                        'log_module' => 'rfq',
                        'data' => $validated
                    ], isError: true);

                    return response()->json([
                        'message' => $message
                    ], 422);
                }

                $items = json_decode($validated['items']);
                $canvassers = json_decode($validated['canvassers']);

                $requestQuotation = RequestQuotation::create(array_merge(
                    $validated,
                    [
                        // 'rfq_no' => $this->generateNewRfqNumber(),
                        'batch' => $purchaseRequest->rfq_batch,
                        'status' => RequestQuotationStatus::DRAFT,
                        'status_timestamps' => json_encode((Object) [])
                    ]
                ));

                foreach ($items ?? [] as $key => $item) {
                    RequestQuotationItem::create([
                        'request_quotation_id' => $requestQuotation->id,
                        'pr_item_id' => $item->pr_item_id,
                        'supplier_id' => $validated['supplier_id'],
                        'included' => $item->included
                    ]);
                }

                foreach ($canvassers ?? [] as $key => $userId) {
                    RequestQuotationCanvasser::create([
                        'request_quotation_id' => $requestQuotation->id,
                        'user_id' => $userId
                    ]);
                }

                $requestQuotation->items = json_decode($validated['items']) ?? [];
                $requestQuotation->canvassers = User::select('id', 'firstname', 'middlename', 'lastname')
                    ->whereIn('id', json_decode($validated['canvassers']) ?? [])
                    ->get();

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $requestQuotation->id,
                    'log_module' => 'rfq',
                    'data' => $requestQuotation
                ]);
            }

            return response()->json([
                'data' => [
                    'data' => $requestQuotation,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Request quotation creation failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_module' => 'rfq',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => "$message Please try again."
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RequestQuotation $requestQuotation): JsonResponse
    {
        $requestQuotation->load([
            'supplier:id,supplier_name,address',
            'signatory_approval:id,user_id',
            'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_approval.detail' => function ($query) {
                $query->where('document', 'rfq')
                    ->where('signatory_type', 'approval');
            },
            'canvassers',
            'canvassers.user:id,firstname,lastname,position_id,allow_signature,signature',
            'canvassers.user.position:id,position_name',
            'items' => function($query) {
                $query->orderBy(
                    PurchaseRequestItem::select('item_sequence')
                        ->whereColumn(
                            'request_quotation_items.pr_item_id', 'purchase_request_items.id'
                        ),
                    'asc'
                );
            },
            'items.pr_item:id,item_sequence,quantity,description,stock_no,awarded_to_id',
            'purchase_request:id,pr_no,purpose,funding_source_id',
            'purchase_request.funding_source:id,title,location_id',
            'purchase_request.funding_source.location:id,location_name'
        ]);

        return response()->json([
            'data' => [
                'data' => $requestQuotation
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RequestQuotation $requestQuotation): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'rfq_no' => 'required',
            'signed_type' => 'required|string',
            'rfq_date' => 'required',
            'supplier_id' => 'nullable',
            'opening_dt' => 'nullable',
            'sig_approval_id' => 'required',
            'canvassers' => 'nullable|string',
            'items' => 'required|string',
            'vat_registered' =>  'nullable|in:true,false',
        ]);

        $validated['vat_registered'] = !empty($validated['vat_registered'])
            ? filter_var($validated['vat_registered'], FILTER_VALIDATE_BOOLEAN)
            : NULL;

        try {
            $message = 'Request for quotation updated successfully.';
            $currentStatus = RequestQuotationStatus::from($requestQuotation->status);
            $purchaseRequest = PurchaseRequest::find($requestQuotation->purchase_request_id);

            $existingSupplierCount = !empty($validated['supplier_id'])
                ? RequestQuotation::where('supplier_id', $validated['supplier_id'])
                    ->where('purchase_request_id', $requestQuotation->purchase_request_id)
                    ->where('batch', $purchaseRequest->rfq_batch)
                    ->where('status', '!=', RequestQuotationStatus::CANCELLED)
                    ->count()
                : 0;

            if ($existingSupplierCount > 0
                && (!empty($validated['supplier_id']) && $requestQuotation->supplier_id !== $validated['supplier_id'] )
                && $currentStatus !== RequestQuotationStatus::COMPLETED) {
                $message = 'Request quotation update failed due to an existing RFQ with the supplier.';

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $requestQuotation->id,
                    'log_module' => 'rfq',
                    'data' => $validated
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            if ($currentStatus === RequestQuotationStatus::CANCELLED) {
                $message = 'Request for quotation update failed, already cancelled.';

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $requestQuotation->id,
                    'log_module' => 'rfq',
                    'data' => $validated
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            $items = json_decode($validated['items']);
            $canvassers = json_decode($validated['canvassers']);
            $grandTotalCost = 0;

            RequestQuotationItem::where('request_quotation_id', $requestQuotation->id)
                ->delete();
            RequestQuotationCanvasser::where('request_quotation_id', $requestQuotation->id)
                ->delete();

            foreach ($items ?? [] as $key => $item) {
                $quantity = intval($item->quantity);
                $unitCost =
                    isset($item->unit_cost) && !empty($item->unit_cost)
                        ? floatval($item->unit_cost)
                        : NULL;
                $cost = round($quantity * ($unitCost ?? 0), 2);

                RequestQuotationItem::create([
                    'request_quotation_id' => $requestQuotation->id,
                    'pr_item_id' => $item->pr_item_id,
                    'supplier_id' => $validated['supplier_id'],
                    'brand_model' => $item->brand_model,
                    'unit_cost' => $unitCost,
                    'total_cost' => $cost,
                    'included' => $item->included
                ]);

                $grandTotalCost += $cost;
            }

            foreach ($canvassers ?? [] as $key => $userId) {
                RequestQuotationCanvasser::create([
                    'request_quotation_id' => $requestQuotation->id,
                    'user_id' => $userId
                ]);
            }

            $requestQuotation->update(array_merge(
                $validated,
                [
                    'supplier_id' => $currentStatus === RequestQuotationStatus::COMPLETED
                        ? $requestQuotation->supplier_id
                        : $validated['supplier_id'],
                    'grand_total_cost' => $grandTotalCost
                ]
            ));

            $requestQuotation->items = json_decode($validated['items']) ?? [];
            $requestQuotation->canvassers = User::select('id', 'firstname', 'middlename', 'lastname')
                ->whereIn('id', json_decode($validated['canvassers']) ?? [])
                ->get();

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $requestQuotation->id,
                'log_module' => 'rfq',
                'data' => $requestQuotation
            ]);

            return response()->json([
                'data' => [
                    'data' => $requestQuotation,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Request quotation update failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $requestQuotation->id,
                'log_module' => 'rfq',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => "$message Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function issueCanvassing(RequestQuotation $requestQuotation): JsonResponse
    {
        try {
            $message = 'Request for quotation successfully marked as "Canvassing".';

            $requestQuotation->update([
                'status' => RequestQuotationStatus::CANVASSING,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'canvassing_at', $requestQuotation->status_timestamps
                )
            ]);

            $purchaseRequest = PurchaseRequest::find($requestQuotation->purchase_request_id);
            $prCurrentStatus = PurchaseRequestStatus::from($purchaseRequest->status);

            if ($purchaseRequest
                && ($prCurrentStatus === PurchaseRequestStatus::APPROVED
                    || $prCurrentStatus === PurchaseRequestStatus::FOR_ABSTRACT
                    || $prCurrentStatus === PurchaseRequestStatus::PARTIALLY_AWARDED)) {
                $newStatus = PurchaseRequestStatus::FOR_CANVASSING;
                $prMessage = 'Purchase request successfully marked as "For Canvassing".';

                if ($prCurrentStatus === PurchaseRequestStatus::FOR_ABSTRACT
                    || $prCurrentStatus === PurchaseRequestStatus::PARTIALLY_AWARDED) {
                    $prMessage = 'Purchase request successfully marked as "For Recanvassing".';
                    $newStatus = PurchaseRequestStatus::FOR_RECANVASSING;
                }

                $purchaseRequest->update([
                    'status' => $newStatus,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'canvassing_at', $purchaseRequest->status_timestamps
                    )
                ]);

                $this->logRepository->create([
                    'message' => $prMessage,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest
                ]);
            }

            $requestQuotation->purchase_request = $purchaseRequest;

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $requestQuotation->id,
                'log_module' => 'rfq',
                'data' => $requestQuotation
            ]);

            return response()->json([
                'data' => [
                    'data' => $requestQuotation,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Request for quotation issue for canvassing failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $requestQuotation->id,
                'log_module' => 'rfq',
                'data' => $requestQuotation
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function canvassComplete(RequestQuotation $requestQuotation): JsonResponse
    {
        try {
            $message = 'Request for quotation successfully marked as "Completed".';

            if (!$requestQuotation->supplier_id) {
                $message = 'Request for quotation could not be marked as completed. Supplier not set.';

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $requestQuotation->id,
                    'log_module' => 'rfq',
                    'data' => $requestQuotation
                ], isError: true);

                return response()->json([
                    'message' => "{$message} Please try again."
                ], 422);
            }

            $requestQuotation->update([
                'status' => RequestQuotationStatus::COMPLETED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'completed_at', $requestQuotation->status_timestamps
                )
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $requestQuotation->id,
                'log_module' => 'rfq',
                'data' => $requestQuotation
            ]);

            return response()->json([
                'data' => [
                    'data' => $requestQuotation,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Request for quotation failed to marked as completed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $requestQuotation->id,
                'log_module' => 'rfq',
                'data' => $requestQuotation
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function cancel(RequestQuotation $requestQuotation): JsonResponse
    {
        try {
            $message = 'Request for quotation successfully marked as "Cancelled".';

            $requestQuotation->update([
                'status' => RequestQuotationStatus::CANCELLED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'cancelled_at', $requestQuotation->status_timestamps
                )
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $requestQuotation->id,
                'log_module' => 'rfq',
                'data' => $requestQuotation
            ]);

            return response()->json([
                'data' => [
                    'data' => $requestQuotation,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Request for quotation cancellation failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $requestQuotation->id,
                'log_module' => 'rfq',
                'data' => $requestQuotation
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    private function generateNewRfqNumber($tag = 'NP'): string
    {
        $sequence = RequestQuotation::count() + 1;

        return $tag ? "{$tag}-{$sequence}" : $sequence;
    }
}
