<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Enums\AbstractQuotationStatus;
use App\Enums\NotificationType;
use App\Enums\PurchaseRequestStatus;
use App\Enums\RequestQuotationStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Http\Controllers\Controller;
use App\Models\AbstractQuotation;
use App\Models\AbstractQuotationDetail;
use App\Models\FundingSource;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RequestQuotation;
use App\Models\User;
use App\Repositories\AbstractQuotationRepository;
use App\Repositories\LogRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\PurchaseRequestRepository;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestController extends Controller
{
    private LogRepository $logRepository;

    private PurchaseRequestRepository $purchaseRequestRepository;

    private AbstractQuotationRepository $abstractQuotationRepository;

    private PurchaseOrderRepository $purchaseOrderRepository;

    private NotificationRepository $notificationRepository;

    public function __construct(
        LogRepository $logRepository,
        PurchaseRequestRepository $purchaseRequestRepository,
        AbstractQuotationRepository $abstractQuotationRepository,
        PurchaseOrderRepository $purchaseOrderRepository,
        NotificationRepository $notificationRepository
    ) {
        $this->logRepository = $logRepository;
        $this->purchaseRequestRepository = $purchaseRequestRepository;
        $this->abstractQuotationRepository = $abstractQuotationRepository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->notificationRepository = $notificationRepository;
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
        $columnSort = $request->get('column_sort', 'pr_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $purchaseRequests = PurchaseRequest::query()
            ->select('id', 'pr_no', 'pr_date', 'funding_source_id', 'purpose', 'status', 'requested_by_id')
            ->with([
                'funding_source:id,title',
                'requestor:id,firstname,lastname',
            ]);

        if ($user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('supply:*')
            || $user->tokenCan('budget:*')
            || $user->tokenCan('accounting:*')
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'section_id' => 'required|string',
            'pr_date' => 'required',
            'sai_no' => 'nullable|string',
            'sai_date' => 'nullable',
            'alobs_no' => 'nullable|string',
            'alobs_date' => 'nullable',
            'purpose' => 'required|string',
            'funding_source_id' => 'nullable|string',
            'requested_by_id' => 'required|string',
            'sig_cash_availability_id' => 'nullable|string',
            'sig_approved_by_id' => 'nullable|string',
            'items' => 'required|array|min:1',
        ]);

        try {
            $message = 'Purchase request created successfully.';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
            ]);

            if ($canAccess) {
            } else {
                if ($validated['requested_by_id'] !== $user->id) {
                    $message = 'Purchase request creation failed. User is not authorized to create purchase requests for others.';
                    $this->logRepository->create([
                        'message' => $message,
                        'log_module' => 'pr',
                        'data' => $validated,
                    ], isError: true);

                    return response()->json([
                        'message' => $message,
                    ], 422);
                }
            }

            $purchaseRequest = PurchaseRequest::create(array_merge(
                $validated,
                [
                    'pr_no' => $this->purchaseRequestRepository->generateNewPrNumber(),
                    'status' => PurchaseRequestStatus::DRAFT,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'draft_at', null
                    ),
                ]
            ));

            $totalEstimatedCost = 0;

            foreach ($validated['items'] ?? [] as $key => $item) {
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

            $purchaseRequest->items = $validated['items'] ?? [];

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request creation failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_module' => 'pr',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => "$message Please try again.",
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $purchaseRequest->load([
            'funding_source:id,title,location_id',
            'funding_source.location:id,location_name',
            'section:id,section_name',

            'items' => function ($query) {
                $query->orderBy('item_sequence');
            },
            'items.unit_issue:id,unit_name',

            'requestor:id,firstname,lastname,position_id,allow_signature,signature',
            'requestor.position:id,position_name',

            'signatory_cash_available:id,user_id',
            'signatory_cash_available.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_cash_available.detail' => function ($query) {
                $query->where('document', 'pr')
                    ->where('signatory_type', 'cash_availability');
            },

            'signatory_approval:id,user_id',
            'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_approval.detail' => function ($query) {
                $query->where('document', 'pr')
                    ->where('signatory_type', 'approved_by');
            },
        ]);

        return response()->json([
            'data' => [
                'data' => $purchaseRequest,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'section_id' => 'required|string',
            'pr_date' => 'required',
            'sai_no' => 'nullable|string',
            'sai_date' => 'nullable',
            'alobs_no' => 'nullable|string',
            'alobs_date' => 'nullable',
            'purpose' => 'required|string',
            'funding_source_id' => 'nullable|string',
            'requested_by_id' => 'required|string',
            'sig_cash_availability_id' => 'nullable|string',
            'sig_approved_by_id' => 'nullable|string',
            'items' => 'required|array|min:1',
        ]);

        try {
            $message = 'Purchase request updated successfully.';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
            ]);

            if ($canAccess) {
            } else {
                if ($purchaseRequest->requested_by_id !== $user->id) {
                    $message =
                        'Purchase request update failed. User is not authorized to update purchase requests for others.';
                    $this->logRepository->create([
                        'message' => $message,
                        'log_id' => $purchaseRequest->id,
                        'log_module' => 'pr',
                        'data' => $validated,
                    ], isError: true);

                    return response()->json([
                        'message' => $message,
                    ], 422);
                }
            }

            $currentStatus = PurchaseRequestStatus::from($purchaseRequest->status);

            if ($currentStatus === PurchaseRequestStatus::CANCELLED
                || $currentStatus === PurchaseRequestStatus::FOR_CANVASSING
                || $currentStatus === PurchaseRequestStatus::FOR_RECANVASSING
                || $currentStatus === PurchaseRequestStatus::FOR_ABSTRACT
                || $currentStatus === PurchaseRequestStatus::PARTIALLY_AWARDED
                || $currentStatus === PurchaseRequestStatus::AWARDED
                || $currentStatus === PurchaseRequestStatus::COMPLETED) {
                $message = 'Purchase request update failed, already processing or cancelled.';

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $validated,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $status = $currentStatus;

            if ($currentStatus === PurchaseRequestStatus::DRAFT
                || $currentStatus === PurchaseRequestStatus::DISAPPROVED) {
                $status = PurchaseRequestStatus::DRAFT;

                $totalEstimatedCost = 0;

                PurchaseRequestItem::where('purchase_request_id', $purchaseRequest->id)
                    ->delete();

                foreach ($validated['items'] ?? [] as $key => $item) {
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
            }

            $purchaseRequest->update(array_merge(
                $validated,
                [
                    'status' => $status,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'draft_at', null
                    ),
                ]
            ));

            $purchaseRequest->load('items');

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request update failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function submitForApproval(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = Auth::user();

        try {
            $message = 'Purchase request has been successfully marked as "Pending".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
            ]);

            if ($canAccess) {
            } else {
                if ($purchaseRequest->requested_by_id !== $user->id) {
                    $message =
                        'Purchase request submit failed.
                    User is not authorized to submit purchase requests for others.';
                    $this->logRepository->create([
                        'message' => $message,
                        'log_id' => $purchaseRequest->id,
                        'log_module' => 'pr',
                        'data' => $purchaseRequest,
                    ], isError: true);

                    return response()->json([
                        'message' => $message,
                    ], 422);
                }
            }

            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::PENDING,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'pending_at', $purchaseRequest->status_timestamps
                ),
            ]);

            $this->notificationRepository->notify(NotificationType::PR_PENDING, [
                'pr' => $purchaseRequest
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request submission for approval failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function approveForCashAvailability(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = Auth::user();

        try {
            $message = 'Purchase request has been successfully marked as "Approved for Cash Availability".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
                $user->tokenCan('budget:*'),
                $user->tokenCan('accounting:*'),
                $user->tokenCan('cashier:*'),
            ]);

            if ($canAccess) {
            } else {
                $message =
                    'Purchase request approval for cash availability failed.
                    User is not authorized.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::APPROVED_CASH_AVAILABLE,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'approved_cash_available_at', $purchaseRequest->status_timestamps
                ),
            ]);

            $this->notificationRepository->notify(NotificationType::PR_APPROVED_CASH_AVAILABLE, [
                'pr' => $purchaseRequest
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request approval for cash availability failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function approve(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = Auth::user();

        try {
            $message = 'Purchase request has been successfully marked as "Approved".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
                $user->tokenCan('head:*'),
            ]);

            if ($canAccess) {
            } else {
                $message = 'Purchase request approve failed. User is not authorized.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::APPROVED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'approved_at', $purchaseRequest->status_timestamps
                ),
            ]);

            $this->notificationRepository->notify(NotificationType::PR_APPROVED, [
                'pr' => $purchaseRequest
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request approval failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function disapprove(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = Auth::user();

        try {
            $message = 'Purchase request has been successfully marked as "Disapproved".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
                $user->tokenCan('head:*'),
            ]);

            if ($canAccess) {
            } else {
                $message = 'Purchase request disapprove failed. User is not authorized.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::DISAPPROVED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'disapproved_at', $purchaseRequest->status_timestamps
                ),
            ]);

            $this->notificationRepository->notify(NotificationType::PR_DISAPPROVED, [
                'pr' => $purchaseRequest
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request disapproval failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function cancel(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = Auth::user();

        try {
            $message = 'Purchase request successfully marked as "Cancelled".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
            ]);

            if ($canAccess) {
            } else {
                if ($purchaseRequest->requested_by_id !== $user->id) {
                    $message = 'Purchase request cancel failed. User is not authorized to cancel purchase requests for others.';
                    $this->logRepository->create([
                        'message' => $message,
                        'log_id' => $purchaseRequest->id,
                        'log_module' => 'pr',
                        'data' => $purchaseRequest,
                    ], isError: true);

                    return response()->json([
                        'message' => $message,
                    ], 422);
                }
            }

            $purchaseRequest->update([
                'status' => PurchaseRequestStatus::CANCELLED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'cancelled_at', $purchaseRequest->status_timestamps
                ),
            ]);

            $this->notificationRepository->notify(NotificationType::PR_CANCELLED, [
                'pr' => $purchaseRequest
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request cancellation failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function issueAllDraftRfq(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = Auth::user();

        try {
            $message = 'RFQs for this purchase request successfully marked as "Canvassing".';
            $currentStatus = PurchaseRequestStatus::from($purchaseRequest->status);

            $rfqDraft = RequestQuotation::where('purchase_request_id', $purchaseRequest->id)
                ->whereIn('status', [
                    RequestQuotationStatus::DRAFT,
                ]);
            $rfqDraftCount = $rfqDraft->count();
            $rfqDraft = $rfqDraft->get();

            if ($rfqDraftCount === 0) {
                $message = 'Nothing to issue because all RFQs have already been provided to the canvassers.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $rfqDraft,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            foreach ($rfqDraft as $key => $rfq) {
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

            if ($currentStatus === PurchaseRequestStatus::APPROVED
                || $currentStatus === PurchaseRequestStatus::FOR_ABSTRACT
                || $currentStatus === PurchaseRequestStatus::PARTIALLY_AWARDED) {
                $purchaseRequest->update([
                    'status' => PurchaseRequestStatus::FOR_CANVASSING,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'canvassing_at', $purchaseRequest->status_timestamps
                    ),
                ]);
            }

            return response()->json([
                'data' => [
                    'data' => $rfqDraft,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Failed to mark the purchase request as "For Abstract".';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function approveRequestQuotations(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = Auth::user();

        try {
            $message = 'Purchase request successfully marked as "For Abstract".';
            $currentStatus = PurchaseRequestStatus::from($purchaseRequest->status);

            if ($currentStatus === PurchaseRequestStatus::FOR_CANVASSING
                || $currentStatus === PurchaseRequestStatus::FOR_RECANVASSING) {
            } else {
                $message = 'Failed to mark the purchase request as "For Abstract" because it is already set to this status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $rfqProcessing = RequestQuotation::where('purchase_request_id', $purchaseRequest->id)
                ->whereIn('status', [
                    RequestQuotationStatus::CANVASSING,
                    RequestQuotationStatus::DRAFT,
                ])
                ->where('batch', $purchaseRequest->rfq_batch);
            $rfqProcessingCount = $rfqProcessing->count();
            $rfqProcessing = $rfqProcessing->get();

            $rfqCompleted = RequestQuotation::where('purchase_request_id', $purchaseRequest->id)
                ->where('status', RequestQuotationStatus::COMPLETED)
                ->where('batch', $purchaseRequest->rfq_batch);
            $rfqCompletedCount = $rfqCompleted->count();
            $rfqCompleted = $rfqCompleted->get();

            if ($rfqProcessingCount > 0) {
                $message = 'Failed to mark the purchase request as "For Abstract" due to pending RFQs in canvassing or draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $rfqProcessing,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            if ($rfqCompletedCount === 0) {
                $message = 'Cannot mark as "For Abstract" because no RFQ has been completed';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $rfqCompleted,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
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
                'solicitation_no' => isset($rfqCompleted[0]->rfq_no)
                    ? $rfqCompleted[0]->rfq_no : '',
                'solicitation_date' => Carbon::now()->toDateString(),
                'items' => $purchaseRequest->items->map(function (PurchaseRequestItem $item) use ($rfqCompleted) {
                    return [
                        'pr_item_id' => $item->id,
                        'included' => empty($item->awarded_to) ? true : false,
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
                'pr' => $purchaseRequest, 'rfq' => $rfqCompleted[0]
            ]);

            $this->logRepository->create([
                'message' => 'Abstract of quotation created successfully.',
                'log_id' => $abstractQuotation->id,
                'log_module' => 'aoq',
                'data' => $abstractQuotation,
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $prReturnData,
            ]);

            return response()->json([
                'data' => [
                    'data' => $prReturnData,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Failed to mark the purchase request as "For Abstract".';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function awardAbstractQuotations(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = Auth::user();

        try {
            $message = 'Purchase request successfully marked as ';
            $currentStatus = PurchaseRequestStatus::from($purchaseRequest->status);

            if ($currentStatus === PurchaseRequestStatus::FOR_ABSTRACT
                || $currentStatus === PurchaseRequestStatus::PARTIALLY_AWARDED) {
            } else {
                $message = 'Failed to award the approved Abstract of Quotation(s) because it is already set to this status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
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

                return response()->json([
                    'message' => $message,
                ], 422);
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
                    'pr' => $purchaseRequest, 'aoq' => $aoqApproved[0]
                ]);
            } else {
                $this->notificationRepository->notify(NotificationType::PR_AWARDED, [
                    'pr' => $purchaseRequest, 'aoq' => $aoqApproved[0]
                ]);
            }

            $this->logRepository->create([
                'message' => $message.($prStatus === PurchaseRequestStatus::PARTIALLY_AWARDED ? '"Partially Awarded".' : '"Awarded".'),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Failed to award the approved Abstract of Quotation(s).';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }
}
