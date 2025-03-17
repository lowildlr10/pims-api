<?php

namespace App\Http\Controllers\V1;

use App\Enums\PurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\FundingSource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Repositories\LogRepository;
use App\Repositories\PurchaseOrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseOrderController extends Controller
{
    private LogRepository $logRepository;
    private PurchaseOrderRepository $purchaseOrderRepository;

    public function __construct(
        LogRepository $logRepository,
        PurchaseOrderRepository $purchaseOrderRepository
    )
    {
        $this->logRepository = $logRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
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
                'pos' => function ($query) {
                    $query->select(
                            'id',
                            'purchase_request_id',
                            'po_no',
                            'po_date',
                            'mode_procurement_id',
                            'supplier_id',
                            'total_amount',
                            'status'
                        )
                        ->orderByRaw("CAST(REPLACE(po_no, '-', '') AS VARCHAR) asc");
                },
                'pos.mode_procurement:id,mode_name',
                'pos.supplier:id,supplier_name'
            ])->whereIn('status', [
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
                $query->where('id', $search)
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
                    ->orWhereRelation('pos', function ($query) use ($search) {
                        $query->where('id', $search)
                            ->orWhere('po_no', 'ILIKE', "%{$search}%")
                            ->orWhere('document_type', 'ILIKE', "%{$search}%")
                            ->orWhere('status', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('pos.supplier', function ($query) use ($search) {
                        $query->where('supplier_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('pos.mode_procurement', function ($query) use ($search) {
                        $query->where('mode_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('pos.place_delivery', function ($query) use ($search) {
                        $query->where('location_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('pos.delivery_term', function ($query) use ($search) {
                        $query->where('term_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('pos.payment_term', function ($query) use ($search) {
                        $query->where('term_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('pos.signatory_approval.user', function ($query) use ($search) {
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

    // /**
    //  * Store a newly created resource in storage.
    //  */
    // public function store(Request $request)
    // {
    //     //
    // }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier:id,supplier_name,address,tin_no',
            'mode_procurement:id,mode_name',
            'place_delivery:id,location_name',
            'delivery_term:id,term_name',
            'payment_term:id,term_name',
            'items' => function($query) {
                $query->orderBy(
                    PurchaseRequestItem::select('item_sequence')
                        ->whereColumn(
                            'purchase_order_items.pr_item_id', 'purchase_request_items.id'
                        ),
                    'asc'
                );
            },
            'items.pr_item:id,unit_issue_id,item_sequence,quantity,description,stock_no',
            'items.pr_item.unit_issue:id,unit_name',
            'signatory_approval:id,user_id',
            'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_approval.detail' => function ($query) {
                $query->where('document', 'po')
                    ->where('signatory_type', '	authorized_official');
            },
            'purchase_request:id,purpose'
        ]);

        return response()->json([
            'data' => [
                'data' => $purchaseOrder
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'po_date' => 'required',
            'place_delivery' => 'required',
            'delivery_date' => 'required',
            'delivery_term' => 'required',
            'payment_term' => 'required',
            'total_amount_words' => 'string|required',
            'sig_approval_id' => 'required',
            'items' => 'required|string'
        ]);

        try {
            $message = $purchaseOrder->document_type === 'po'
                ? 'Purchase order updated successfully.'
                : 'Job order updated successfully.';

            $this->purchaseOrderRepository->storeUpdate($validated, $purchaseOrder);

            $purchaseOrder->load('items');

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseOrder,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = $purchaseOrder->document_type === 'po'
                ? 'Purchase order update failed.'
                : 'Job order update failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
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
    public function pending(PurchaseOrder $purchaseOrder)
    {

    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function approve(PurchaseOrder $purchaseOrder)
    {

    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function issue(PurchaseOrder $purchaseOrder)
    {

    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function receive(PurchaseOrder $purchaseOrder)
    {

    }
}
