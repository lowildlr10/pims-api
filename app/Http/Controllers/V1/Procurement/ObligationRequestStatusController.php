<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Enums\ObligationRequestStatus;
use App\Enums\PurchaseOrderStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Http\Controllers\Controller;
use App\Models\ObligationRequest;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Repositories\DisbursementVoucherRepository;
use App\Repositories\LogRepository;
use App\Repositories\ObligationRequestRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ObligationRequestStatusController extends Controller
{
    private LogRepository $logRepository;

    private ObligationRequestRepository $obligationRequestRepository;

    private DisbursementVoucherRepository $disbursementVoucherRepository;
    
    public function __construct(
        LogRepository $logRepository,
        ObligationRequestRepository $obligationRequestRepository,
        DisbursementVoucherRepository $disbursementVoucherRepository
    ) {
        $this->logRepository = $logRepository;
        $this->obligationRequestRepository = $obligationRequestRepository;
        $this->disbursementVoucherRepository = $disbursementVoucherRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $user = Auth::user();
        
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'obr_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);
        $status = $request->get('status', '');
        $statusFilters = !empty($status) ? explode(',', $status) : [];

        $obligationRequests = ObligationRequest::query()
            ->select([
                'id',
                'purchase_order_id',
                'payee_id',
                'obr_no',
                'particulars',
                'status',
            ])
            ->with([
                'purchase_order:id,po_no',
                'payee:id,supplier_name'
            ]);

        if ($user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('supply:*')
            || $user->tokenCan('budget:*')
            || $user->tokenCan('accountant:*')
        ) {
        } else {
            $obligationRequests = $obligationRequests
                ->whereRelation('purchase_request', function ($query) use ($user) {
                $query->where('requested_by_id', $user->id);
            });
        }

        if (! empty($search)) {
            $obligationRequests->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('obr_no', 'ILIKE', "%{$search}%")
                    ->orWhere('office', 'ILIKE', "%{$search}%")
                    ->orWhere('address', 'ILIKE', "%{$search}%")
                    ->orWhere('particulars', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('purchase_request', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search]);
                    })
                    ->orWhereRelation('purchase_order', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                            ->orWhere('po_no', 'ILIKE',"%$search%");
                    })
                    ->orWhereRelation('payee', function ($query) use ($search) {
                        $query->where('supplier_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('responsibility_center', function ($query) use ($search) {
                        $query->where('code', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE',"%$search%");
                    })
                    ->orWhereRelation('signatory_budget.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('signatory_head.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    });
            });
        }

        if (count($statusFilters) > 0) {
            $obligationRequests = $obligationRequests->whereIn('status', $statusFilters);
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'obr_no':
                    $obligationRequests = $obligationRequests->orderByRaw("CAST(REPLACE(obr_no, '-', '') AS INTEGER) {$sortDirection}");
                    break;

                case 'po_no':
                    $obligationRequests = $obligationRequests->orderBy(
                        PurchaseOrder::select('po_no')->whereColumn('purchase_orders.id', 'obligation_requests.purchase_order_id'),
                        $sortDirection
                    );
                    break;

                case 'particulars_formatted':
                    $obligationRequests = $obligationRequests->orderBy('status', $sortDirection);
                    break;

                case 'payee_name':
                    $obligationRequests = $obligationRequests->orderBy(
                        Supplier::select('supplier_name')->whereColumn('suppliers.id', 'obligation_requests.payee_id'),
                        $sortDirection
                    );
                    break;

                case 'status_formatted':
                    $obligationRequests = $obligationRequests->orderBy('status', $sortDirection);
                    break;

                default:
                    $obligationRequests = $obligationRequests->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $obligationRequests->paginate($perPage);
        } else {
            $obligationRequests = $showAll
                ? $obligationRequests->get()
                : $obligationRequests = $obligationRequests->limit($perPage)->get();

            return response()->json([
                'data' => $obligationRequests,
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ObligationRequest $obligationRequest): JsonResponse
    {
        $obligationRequest->load([
            'payee:id,supplier_name',
            'responsibility_center:id,code',
            'purchase_order:id,po_no,total_amount',
            'signatory_budget:id,user_id',
            'signatory_budget.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_budget.detail' => function ($query) {
                $query->where('document', 'obr')
                    ->where('signatory_type', 'budget');
            },
            'signatory_head:id,user_id',
            'signatory_head.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_head.detail' => function ($query) {
                $query->where('document', 'obr')
                    ->where('signatory_type', 'head');
            },
            'fpps',
            'fpps.fpp',
            'accounts',
            'accounts.account'
        ]);

        return response()->json([
            'data' => [
                'data' => $obligationRequest,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ObligationRequest $obligationRequest): JsonResponse
    {
        $validated = $request->validate([
            'funding' => 'nullable|array',
            'office' => 'nullable|string',
            'address' => 'nullable|string',
            'responsibility_center_id' => 'required',
            'particulars' => 'required',
            'total_amount' => 'required',
            'compliance_status' => 'nullable|array',
            'sig_head_id' => 'required',
            'head_signed_date' => 'nullable',
            'sig_budget_id' => 'required',
            'budget_signed_date' => 'nullable',
            'fpps' => 'nullable|array',
            'accounts' => 'nullable|array',
        ]);

        try {
            $currentStatus = ObligationRequestStatus::from($obligationRequest->status);
            $status = $currentStatus;

            if ($currentStatus === ObligationRequestStatus::DRAFT
                || $currentStatus === ObligationRequestStatus::DISAPPROVED) {
                $status = ObligationRequestStatus::DRAFT;
            }

            $this->obligationRequestRepository->storeUpdate(
                array_merge($validated, [
                    'status' => $status,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'draft_at', null
                    ),
                ]), 
                $obligationRequest
            );

            $obligationRequest->load(['fpps', 'accounts']);

            $message = 'Obligation request updated successfully';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $obligationRequest->id,
                'log_module' => 'obr',
                'data' => $obligationRequest,
            ]);

            return response()->json([
                'data' => [
                    'data' => $obligationRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Obligation request update failed.';
            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $obligationRequest->id,
                'log_module' => 'obr',
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
    public function pending(ObligationRequest $obligationRequest): JsonResponse
    {
        try {
            $currentStatus = ObligationRequestStatus::from($obligationRequest->status);

            if ($currentStatus !== ObligationRequestStatus::DRAFT) {
                $message =
                    'Failed to set the obligation request to pending for obligation. '.
                    'It may already be set to pending or processing status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $obligationRequest->id,
                    'log_module' => 'obr',
                    'data' => $obligationRequest,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $purchaseOrder = PurchaseOrder::find($obligationRequest->purchase_order_id);

            if ($purchaseOrder) {
                $purchaseOrder->update([
                    'status' => PurchaseOrderStatus::FOR_OBLIGATION,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'for_obligation_at', $purchaseOrder->status_timestamps
                    ),
                ]);

                $this->logRepository->create([
                    'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                        ' order successfully marked as for obligation.',
                    'log_id' => $purchaseOrder->id,
                    'log_module' => 'po',
                    'data' => $purchaseOrder,
                ]);
            }

            $obligationRequest->update([
                'disapproved_reason' => null,
                'status' => ObligationRequestStatus::PENDING,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'pending_at', $obligationRequest->status_timestamps
                ),
            ]);

            $message = 'Obligation request successfully marked as pending for obligation.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $obligationRequest->id,
                'log_module' => 'obr',
                'data' => $obligationRequest,
            ]);

            $obligationRequest->load(['fpps', 'accounts']);

            return response()->json([
                'data' => [
                    'data' => $obligationRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Obligation request failed to marked as pending for obligation.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $obligationRequest->id,
                'log_module' => 'obr',
                'data' => $obligationRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function disapprove(Request $request, ObligationRequest $obligationRequest): JsonResponse
    {
        try {
            $validated = $request->validate([
                'disapproved_reason' => 'nullable|string',
            ]);

            $currentStatus = ObligationRequestStatus::from($obligationRequest->status);

            if ($currentStatus !== ObligationRequestStatus::PENDING) {
                $message =
                    'Failed to set the Obligation Request to "Disapproved". '.
                    'It may already be obligated or still in draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $obligationRequest->id,
                    'log_module' => 'obr',
                    'data' => $obligationRequest,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $obligationRequest->update([
                'disapproved_reason' => $validated['disapproved_reason'] ?? null,
                'status' => ObligationRequestStatus::DISAPPROVED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'disapproved_at', $obligationRequest->status_timestamps
                ),
            ]);

            $message = 'Obligation request successfully marked as "Disapproved".';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $obligationRequest->id,
                'log_module' => 'obr',
                'data' => $obligationRequest,
            ]);

            $obligationRequest->load(['fpps', 'accounts']);

            return response()->json([
                'data' => [
                    'data' => $obligationRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Obligation request disapproval failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $obligationRequest->id,
                'log_module' => 'obr',
                'data' => $obligationRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function obligate(ObligationRequest $obligationRequest): JsonResponse
    {
        try {
            $currentStatus = ObligationRequestStatus::from($obligationRequest->status);

            if ($currentStatus !== ObligationRequestStatus::PENDING) {
                $message =
                    'Failed to set the Obligation Request to "Obligated". '.
                    'It may already be obligated or still in draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $obligationRequest->id,
                    'log_module' => 'obr',
                    'data' => $obligationRequest,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            // Create an disbursement voucher
            $disbursementVoucher = $this->disbursementVoucherRepository->storeUpdate([
                'purchase_request_id'      => $obligationRequest->purchase_request_id,
                'purchase_order_id'        => $obligationRequest->purchase_order_id,
                'obligation_request_id'    => $obligationRequest->id,
                'payee_id'                 => $obligationRequest->payee_id,
                'office'                   => $obligationRequest->office,
                'address'                  => $obligationRequest->address ?? null,
                'responsibility_center_id' => $obligationRequest->responsibility_center_id,
                'total_amount'             => $obligationRequest->total_amount ?? 0.00,
            ]);

            $message = 'Disbursement voucher successfully created.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ]);

            $purchaseOrder = PurchaseOrder::find($obligationRequest->purchase_order_id);

            if ($purchaseOrder) {
                $purchaseOrder->update([
                    'status' => PurchaseOrderStatus::OBLIGATED,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'obligated_at', $purchaseOrder->status_timestamps
                    ),
                ]);

                $this->logRepository->create([
                    'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job').
                        ' order successfully marked as "Obligated".',
                    'log_id' => $purchaseOrder->id,
                    'log_module' => 'po',
                    'data' => $purchaseOrder,
                ]);
            }

            $obligationRequest->update([
                'status' => ObligationRequestStatus::OBLIGATED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'obligated_at', $obligationRequest->status_timestamps
                ),
            ]);

            $message = 'Obligation request successfully marked as "Obligated".';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $obligationRequest->id,
                'log_module' => 'obr',
                'data' => $obligationRequest,
            ]);

            $obligationRequest->load(['fpps', 'accounts']);

            return response()->json([
                'data' => [
                    'data' => $obligationRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Obligation request failed to marked as "Obligated".';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $obligationRequest->id,
                'log_module' => 'obr',
                'data' => $obligationRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }
}
