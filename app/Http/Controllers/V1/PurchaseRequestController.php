<?php

namespace App\Http\Controllers\V1;

use App\Enums\PurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\FundingSource;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Repositories\LogRepository;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseRequestController extends Controller
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

        $purchaseRequests = PurchaseRequest::query()->with([
            'funding_source:id,title',
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
            }
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
                $query->where('pr_no', 'ILIKE', "%{$search}%")
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
            'section_id' => 'required|string',
            'pr_date' => 'required',
            'sai_no' => 'nullable|string',
            'sai_no' => 'nullable|string',
            'sai_date' => 'nullable',
            'alobs_no' => 'nullable|string',
            'alobs_date' => 'nullable',
            'purpose' => 'required|string',
            'funding_source_id' => 'nullable|string',
            'requested_by_id' => 'required|string',
            'sig_cash_availability_id' => 'nullable|string',
            'sig_approved_by_id' => 'nullable|string',
            'items' => 'required|string'
        ]);

        try {
            $message = 'Purchase request created successfully.';

            $cantAccess = in_array(true, [
                $user->tokenCant('super:*'),
                $user->tokenCant('supply:*')
            ]);

            if ($cantAccess && $validated['requested_by_id'] !== $user->id) {
                $message = 'Purchase request creation failed. User is not authorized to create purchase requests for others.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_module' => 'pr',
                    'data' => $validated
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            $items = json_decode($validated['items']);

            $purchaseRequest = PurchaseRequest::create(array_merge(
                $validated,
                [
                    'pr_no' => $this->generateNewPrNumber(),
                    'status' => PurchaseRequestStatus::DRAFT
                ]
            ));

            $totalEstimatedCost = 0;

            foreach ($items ?? [] as $key => $item) {
                $quantity = (int) $item->quantity;
                $unitCost = (float) $item->estimated_unit_cost;
                $cost = round($quantity * $unitCost, 2);

                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_sequence' => $key,
                    'quantity' => $quantity,
                    'unit_issue_id' => $item->unit_issue_id,
                    'description' => $item->description,
                    'stock_no' => (int) $item->stock_no ?? $key + 1,
                    'estimated_unit_cost' => $unitCost,
                    'estimated_cost' => $cost
                ]);

                $totalEstimatedCost += $cost;
            }

            $purchaseRequest->update([
                'total_estimated_cost' => $totalEstimatedCost
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request creation failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_module' => 'pr',
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
    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        return response()->json([
            'data' => [
                'data' => $purchaseRequest
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'section_id' => 'required|string',
            'pr_date' => 'required',
            'sai_no' => 'nullable|string',
            'sai_no' => 'nullable|string',
            'sai_date' => 'nullable',
            'alobs_no' => 'nullable|string',
            'alobs_date' => 'nullable',
            'purpose' => 'required|string',
            'funding_source_id' => 'nullable|string',
            'requested_by_id' => 'required|string',
            'sig_cash_availability_id' => 'nullable|string',
            'sig_approved_by_id' => 'nullable|string',
            'items' => 'required|string'
        ]);

        try {
            $message = 'Purchase request updated successfully.';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*')
            ]);

            if ($canAccess) {}
            else {
                if ($purchaseRequest->requested_by_id !== $user->id) {
                    $message =
                        'Purchase request update failed.
                    User is not authorized to update purchase requests for others.' . $cantAccess;
                    $this->logRepository->create([
                        'message' => $message,
                        'log_id' => $purchaseRequest->id,
                        'log_module' => 'pr',
                        'data' => $validated
                    ], isError: true);

                    return response()->json([
                        'message' => $message
                    ], 422);
                }
            }

            $currentStatus = PurchaseRequestStatus::from($purchaseRequest->status);

            if ($currentStatus === PurchaseRequestStatus::CANCELLED
                || $currentStatus === PurchaseRequestStatus::FOR_CANVASSING
                || $currentStatus === PurchaseRequestStatus::FOR_ABSTRACT
                || $currentStatus === PurchaseRequestStatus::FOR_PO
                || $currentStatus === PurchaseRequestStatus::COMPLETED) {
                $message = 'Purchase request update failed, already processing or cancelled.';

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $validated
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            $status = $purchaseRequest->status;

            if ($currentStatus === PurchaseRequestStatus::DRAFT
                || $currentStatus === PurchaseRequestStatus::DISAPPROVED) {
                $status = PurchaseRequestStatus::DRAFT;

                $items = json_decode($validated['items']);
                $totalEstimatedCost = 0;

                PurchaseRequestItem::where('purchase_request_id', $purchaseRequest->id)
                    ->delete();

                foreach ($items ?? [] as $key => $item) {
                    $quantity = (int) $item->quantity;
                    $unitCost = (float) $item->estimated_unit_cost;
                    $cost = round($quantity * $unitCost, 2);

                    PurchaseRequestItem::create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'item_sequence' => $key,
                        'quantity' => $quantity,
                        'unit_issue_id' => $item->unit_issue_id,
                        'description' => $item->description,
                        'stock_no' => (int) $item->stock_no ?? $key + 1,
                        'estimated_unit_cost' => $unitCost,
                        'estimated_cost' => $cost
                    ]);

                    $totalEstimatedCost += $cost;
                }

                $purchaseRequest->update([
                    'total_estimated_cost' => $totalEstimatedCost
                ]);
            }

            $purchaseRequest->update(array_merge(
                $validated,
                [
                    'status' => $status,
                    'submitted_at' => NULL,
                    'approved_cash_available_at' => NULL,
                    'disapproved_at' => NULL
                ]
            ));

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request update failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function submitForApproval(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = auth()->user();

        try {
            $message = 'Purchase request has been successfully marked as "Pending".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*')
            ]);

            if ($canAccess) {}
            else {
                if ($purchaseRequest->requested_by_id !== $user->id) {
                    $message =
                        'Purchase request submit failed.
                    User is not authorized to submit purchase requests for others.';
                    $this->logRepository->create([
                        'message' => $message,
                        'log_id' => $purchaseRequest->id,
                        'log_module' => 'pr',
                        'data' => $purchaseRequest
                    ], isError: true);

                    return response()->json([
                        'message' => $message
                    ], 422);
                }
            }

            $purchaseRequest->update([
                'submitted_at' => Carbon::now(),
                'approved_cash_available_at' => NULL,
                'status' => PurchaseRequestStatus::PENDING
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request submission for approval failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function approveForCashAvailability(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = auth()->user();

        try {
            $message = 'Purchase request has been successfully marked as "Approved for Cash Availability".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
                $user->tokenCan('budget:*'),
                $user->tokenCan('accounting:*'),
                $user->tokenCan('cashier:*')
            ]);

            if ($canAccess) {}
            else {
                $message =
                    'Purchase request approval for cash availability failed.
                    User is not authorized.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            $purchaseRequest->update([
                'approved_cash_available_at' => Carbon::now(),
                'status' => PurchaseRequestStatus::APPROVED_CASH_AVAILABLE
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request approval for cash availability failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function approve(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = auth()->user();

        try {
            $message = 'Purchase request has been successfully marked as "Approved".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
                $user->tokenCan('head:*')
            ]);

            if ($canAccess) {}
            else {
                $message = 'Purchase request approve failed. User is not authorized.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            $purchaseRequest->update([
                'approved_at' => Carbon::now(),
                'status' => PurchaseRequestStatus::APPROVED
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request approval failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function disapprove(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = auth()->user();

        try {
            $message = 'Purchase request has been successfully marked as "Disapproved".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*'),
                $user->tokenCan('head:*')
            ]);

            if ($canAccess) {}
            else {
                $message = 'Purchase request disapprove failed. User is not authorized.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            $purchaseRequest->update([
                'disapproved_at' => Carbon::now(),
                'status' => PurchaseRequestStatus::DISAPPROVED
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request disapproval failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function cancel(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $user = auth()->user();

        try {
            $message = 'Purchase request successfully marked as "Cancelled".';

            $canAccess = in_array(true, [
                $user->tokenCan('super:*'),
                $user->tokenCan('supply:*')
            ]);

            if ($canAccess) {}
            else {
                if ($purchaseRequest->requested_by_id !== $user->id) {
                    $message = 'Purchase request cancel failed. User is not authorized to cancel purchase requests for others.';
                    $this->logRepository->create([
                        'message' => $message,
                        'log_id' => $purchaseRequest->id,
                        'log_module' => 'pr',
                        'data' => $purchaseRequest
                    ], isError: true);

                    return response()->json([
                        'message' => $message
                    ], 422);
                }
            }

            $purchaseRequest->update([
                'cancelled_at' => Carbon::now(),
                'status' => PurchaseRequestStatus::CANCELLED
            ]);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ]);

            return response()->json([
                'data' => [
                    'data' => $purchaseRequest,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Purchase request cancellation failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $purchaseRequest->id,
                'log_module' => 'pr',
                'data' => $purchaseRequest
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    private function generateNewPrNumber(): string
    {
        $sequence = PurchaseRequest::count() + 1;
        $month = date('m');
        $year = date('Y');

        return "{$year}-{$month}-{$sequence}";
    }
}
