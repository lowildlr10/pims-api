<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Http\Controllers\Controller;
use App\Models\FundingSource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Repositories\InspectionAcceptanceReportRepository;
use App\Repositories\LogRepository;
use App\Repositories\PurchaseOrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    private LogRepository $logRepository;

    private PurchaseOrderRepository $purchaseOrderRepository;

    private InspectionAcceptanceReportRepository $inspectionAcceptanceReportRepository;

    public function __construct(
        LogRepository $logRepository,
        PurchaseOrderRepository $purchaseOrderRepository,
        InspectionAcceptanceReportRepository $inspectionAcceptanceReportRepository
    ) {
        $this->logRepository = $logRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->inspectionAcceptanceReportRepository = $inspectionAcceptanceReportRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $user = Auth::user();

        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $grouped = filter_var($request->get('grouped', true), FILTER_VALIDATE_BOOLEAN);
        $hasSuppliesOnly = filter_var($request->get('has_supplies_only', false), FILTER_VALIDATE_BOOLEAN);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'pr_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        if (! $grouped) {
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
                $purchaseOrders = $purchaseOrders->where(function ($query) use ($search) {
                    $query->where('po_no', 'ILIKE', "%{$search}%");
                });
            }

            if ($hasSuppliesOnly) {
                $purchaseOrders = $purchaseOrders->has('supplies');
            }

            $purchaseOrders = $showAll
                ? $purchaseOrders->get()
                : $purchaseOrders = $purchaseOrders->limit($perPage)->get();

            return response()->json([
                'data' => $purchaseOrders,
            ]);
        }

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
                'pos.supplier:id,supplier_name',
            ])->whereIn('status', [
                PurchaseRequestStatus::PARTIALLY_AWARDED,
                PurchaseRequestStatus::AWARDED,
                PurchaseRequestStatus::COMPLETED,
            ]);

        if ($user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('supply:*')
            || $user->tokenCan('budget:*')
            || $user->tokenCan('accountant:*')
        ) {
        } else {
            $purchaseRequests = $purchaseRequests->where('requested_by_id', $user->id);
        }

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
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
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
                'data' => $purchaseRequests,
            ]);
        }
    }

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
            'items' => function ($query) {
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
            'purchase_request:id,section_id,requested_by_id,purpose',
            'purchase_request.section:id,section_name',
            'purchase_request.requestor:id,firstname,middlename,lastname',
        ]);

        return response()->json([
            'data' => [
                'data' => $purchaseOrder,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'po_date' => 'required',
            'place_delivery' => 'required',
            'delivery_date' => 'required',
            'delivery_term' => 'required',
            'payment_term' => 'required',
            'total_amount_words' => 'string|required',
            'sig_approval_id' => 'required',
            'items' => 'required|array|min:1',
        ]);

        try {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order updated successfully.';

            $this->purchaseOrderRepository->storeUpdate($validated, $purchaseOrder);

            $purchaseOrder->load('items');

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseOrder,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order update failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => "$message Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function pending(PurchaseOrder $purchaseOrder)
    {
        try {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order successfully marked as pending for approval.';

            $currentStatus = PurchaseOrderStatus::from($purchaseOrder->status);

            if ($currentStatus !== PurchaseOrderStatus::DRAFT) {
                $message =
                    'Failed to set the '.($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' Order to
                    pending for approval. It may already be set to pending or processing status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseOrder->id,
                    'log_module' => 'po',
                    'data' => $purchaseOrder,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::PENDING,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'pending_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);

            $purchaseOrder->load('items');

            return response()->json([
                'data' => [
                    'data' => $purchaseOrder,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order failed to marked as pending for approval.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function approve(PurchaseOrder $purchaseOrder)
    {
        try {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order successfully marked as "Approved".';

            $currentStatus = PurchaseOrderStatus::from($purchaseOrder->status);

            if ($currentStatus !== PurchaseOrderStatus::PENDING) {
                $message =
                    'Failed to set the '.($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' Order to
                    pending for approval. It may already be set to approved or processing or still in draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseOrder->id,
                    'log_module' => 'po',
                    'data' => $purchaseOrder,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::APPROVED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'approved_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);

            $purchaseOrder->load('items');

            return response()->json([
                'data' => [
                    'data' => $purchaseOrder,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order failed to marked as "Approved".';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function issue(PurchaseOrder $purchaseOrder)
    {
        try {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order successfully issued to supplier.';

            $currentStatus = PurchaseOrderStatus::from($purchaseOrder->status);

            if ($currentStatus !== PurchaseOrderStatus::APPROVED) {
                $message =
                    'Failed to set the '.($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' Order to
                    issued. It may already be issued or still in draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseOrder->id,
                    'log_module' => 'po',
                    'data' => $purchaseOrder,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::ISSUED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'issued_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);

            $purchaseOrder->load('items');

            return response()->json([
                'data' => [
                    'data' => $purchaseOrder,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order failed to marked as "Issued".';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function receive(PurchaseOrder $purchaseOrder)
    {
        try {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order successfully received and marked as "For Delivery".';

            $currentStatus = PurchaseOrderStatus::from($purchaseOrder->status);

            if ($currentStatus !== PurchaseOrderStatus::ISSUED) {
                $message =
                    'Failed to receive and set the '.($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                    ' Order to marked as "For Delivery". It may already be for delivery or processing or still in
                    draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseOrder->id,
                    'log_module' => 'po',
                    'data' => $purchaseOrder,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::FOR_DELIVERY,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'for_delivery_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);

            $purchaseOrder->load('items');

            return response()->json([
                'data' => [
                    'data' => $purchaseOrder,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order failed to received and marked as "For Delivery".';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function delivered(PurchaseOrder $purchaseOrder)
    {
        try {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order successfully set to "Delivered".';

            $currentStatus = PurchaseOrderStatus::from($purchaseOrder->status);

            if ($currentStatus !== PurchaseOrderStatus::FOR_DELIVERY) {
                $message =
                    'Failed to set the '.($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').' Order to
                    received. It may already be delivered or processing or still in draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseOrder->id,
                    'log_module' => 'po',
                    'data' => $purchaseOrder,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $purchaseOrder->load('items');

            // Save to IAR module
            $inspectionAcceptanceReport = $this->inspectionAcceptanceReportRepository->storeUpdate([
                'purchase_request_id' => $purchaseOrder->purchase_request_id,
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'items' => $purchaseOrder->items->map(function ($item) {
                    return [
                        'pr_item_id' => $item->pr_item_id,
                        'po_item_id' => $item->id,
                    ];
                }),
            ]);
            $this->logRepository->create([
                'message' => 'Inspection Acceptance Report created successfully.',
                'log_id' => $inspectionAcceptanceReport->id,
                'log_module' => 'iar',
                'data' => $inspectionAcceptanceReport,
            ]);

            $purchaseOrder->update([
                'status' => PurchaseOrderStatus::DELIVERED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'delivered_at', $purchaseOrder->status_timestamps
                ),
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseOrder,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                ' order failed to marked as "Delivered".';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseOrder->id,
                'log_module' => 'po',
                'data' => $purchaseOrder,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }
}
