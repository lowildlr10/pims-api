<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Purchase Requests
 * APIs for managing purchase requests
 */
class PurchaseRequestController extends Controller
{
    public function __construct(
        protected PurchaseRequestService $service
    ) {}

    /**
     * List Purchase Requests
     *
     * Retrieve a paginated list of purchase requests.
     *
     * @queryParam search string Search by PR number, purpose, etc.
     * @queryParam per_page int Number of items per page. Default: 50.
     * @queryParam show_all boolean Show all results without pagination. Default: false.
     * @queryParam column_sort string Sort field. Default: pr_no.
     * @queryParam sort_direction string Sort direction (asc/desc). Default: desc.
     * @queryParam paginated boolean Return paginated results. Default: true.
     * @queryParam status string Filter by status (comma-separated).
     *
     * @response 200 {
     *   "data": [...],
     *   "links": {...},
     *   "meta": {...}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $filters = $request->only([
            'search',
            'per_page',
            'show_all',
            'column_sort',
            'sort_direction',
            'paginated',
            'status',
        ]);

        $paginated = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $result = $this->service->getAll($filters);

        if ($paginated) {
            return PurchaseRequestResource::collection($result);
        }

        return response()->json([
            'data' => PurchaseRequestResource::collection($result),
        ]);
    }

    /**
     * Create Purchase Request
     *
     * Create a new purchase request.
     *
     * @bodyParam department_id string required The department ID.
     * @bodyParam section_id string nullable The section ID.
     * @bodyParam pr_date date required The PR date.
     * @bodyParam sai_no string nullable The SAI number.
     * @bodyParam sai_date date nullable The SAI date.
     * @bodyParam alobs_no string nullable The ALOBS number.
     * @bodyParam alobs_date date nullable The ALOBS date.
     * @bodyParam notes string nullable Additional notes.
     * @bodyParam purpose string required The purpose of the PR.
     * @bodyParam funding_source_id string nullable The funding source ID.
     * @bodyParam requested_by_id string required The requestor user ID.
     * @bodyParam sig_cash_availability_id string nullable The signatory for cash availability ID.
     * @bodyParam sig_approved_by_id string nullable The signatory for approval ID.
     * @bodyParam items array required The PR items.
     * @bodyParam items.*.quantity integer required Item quantity.
     * @bodyParam items.*.unit_issue_id string required Unit of issue ID.
     * @bodyParam items.*.description string required Item description.
     * @bodyParam items.*.stock_no string nullable Stock number.
     * @bodyParam items.*.estimated_unit_cost float required Estimated unit cost.
     *
     * @response 201 {
     *   "data": {...},
     *   "message": "Purchase request created successfully."
     * }
     * @response 422 {
     *   "message": "Purchase request creation failed."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required',
            'section_id' => 'nullable',
            'pr_date' => 'required',
            'sai_no' => 'nullable|string',
            'sai_date' => 'nullable',
            'alobs_no' => 'nullable|string',
            'alobs_date' => 'nullable',
            'notes' => 'nullable|string',
            'purpose' => 'required|string',
            'funding_source_id' => 'nullable|string',
            'requested_by_id' => 'required|string',
            'sig_cash_availability_id' => 'nullable|string',
            'sig_approved_by_id' => 'nullable|string',
            'items' => 'required|array|min:1',
        ]);

        try {
            $purchaseRequest = $this->service->create($validated);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase request created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase request creation failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Purchase Request
     *
     * Display the specified purchase request.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     */
    public function show(string $id): JsonResponse
    {
        $purchaseRequest = $this->service->getById($id);

        if (! $purchaseRequest) {
            return response()->json(['message' => 'Purchase request not found.'], 404);
        }

        return response()->json([
            'data' => new PurchaseRequestResource($purchaseRequest),
        ]);
    }

    /**
     * Update Purchase Request
     *
     * Update the specified purchase request in storage.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @bodyParam department_id string required The department ID.
     * @bodyParam section_id string nullable The section ID.
     * @bodyParam pr_date date required The PR date.
     * @bodyParam sai_no string nullable The SAI number.
     * @bodyParam sai_date date nullable The SAI date.
     * @bodyParam alobs_no string nullable The ALOBS number.
     * @bodyParam alobs_date date nullable The ALOBS date.
     * @bodyParam notes string nullable Additional notes.
     * @bodyParam purpose string required The purpose of the PR.
     * @bodyParam funding_source_id string nullable The funding source ID.
     * @bodyParam requested_by_id string required The requestor user ID.
     * @bodyParam sig_cash_availability_id string nullable The signatory for cash availability ID.
     * @bodyParam sig_approved_by_id string nullable The signatory for approval ID.
     * @bodyParam items array required The PR items.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase request updated successfully."
     * }
     * @response 422 {
     *   "message": "Purchase request update failed."
     * }
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => 'required',
            'section_id' => 'nullable',
            'pr_date' => 'required',
            'sai_no' => 'nullable|string',
            'sai_date' => 'nullable',
            'alobs_no' => 'nullable|string',
            'alobs_date' => 'nullable',
            'notes' => 'nullable|string',
            'purpose' => 'required|string',
            'funding_source_id' => 'nullable|string',
            'requested_by_id' => 'required|string',
            'sig_cash_availability_id' => 'nullable|string',
            'sig_approved_by_id' => 'nullable|string',
            'items' => 'required|array|min:1',
        ]);

        try {
            $purchaseRequest = $this->service->update($purchaseRequest, $validated);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase request updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase request update failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Submit for Approval
     *
     * Mark the purchase request as pending for approval.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase request has been successfully marked as Pending."
     * }
     */
    public function submitForApproval(PurchaseRequest $purchaseRequest): JsonResponse
    {
        try {
            $purchaseRequest = $this->service->submitForApproval($purchaseRequest);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase request has been successfully marked as "Pending".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase request submission for approval failed.', $th, $purchaseRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve for Cash Availability
     *
     * Mark the purchase request as approved for cash availability.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase request has been successfully marked as Approved for Cash Availability."
     * }
     */
    public function approveForCashAvailability(PurchaseRequest $purchaseRequest): JsonResponse
    {
        try {
            $purchaseRequest = $this->service->approveForCashAvailability($purchaseRequest);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase request has been successfully marked as "Approved for Cash Availability".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase request approval for cash availability failed.', $th, $purchaseRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve Purchase Request
     *
     * Mark the purchase request as approved.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase request has been successfully marked as Approved."
     * }
     */
    public function approve(PurchaseRequest $purchaseRequest): JsonResponse
    {
        try {
            $purchaseRequest = $this->service->approve($purchaseRequest);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase request has been successfully marked as "Approved".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase request approval failed.', $th, $purchaseRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Disapprove Purchase Request
     *
     * Mark the purchase request as disapproved.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @bodyParam disapproved_reason string nullable The reason for disapproval.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase request has been successfully marked as Disapproved."
     * }
     */
    public function disapprove(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $validated = $request->validate([
            'disapproved_reason' => 'nullable|string',
        ]);

        try {
            $purchaseRequest = $this->service->disapprove($purchaseRequest, $validated);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase request has been successfully marked as "Disapproved".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase request disapproval failed.', $th, $purchaseRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel Purchase Request
     *
     * Mark the purchase request as cancelled.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase request successfully marked as Cancelled."
     * }
     */
    public function cancel(PurchaseRequest $purchaseRequest): JsonResponse
    {
        try {
            $purchaseRequest = $this->service->cancel($purchaseRequest);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase request successfully marked as "Cancelled".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase request cancellation failed.', $th, $purchaseRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Issue All Draft RFQs
     *
     * Mark all draft RFQs for this purchase request as canvassing.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @response 200 {
     *   "data": [...],
     *   "message": "RFQs for this purchase request successfully marked as Canvassing."
     * }
     */
    public function issueAllDraftRfq(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        try {
            $rfqDraft = $this->service->issueAllDraftRfq($purchaseRequest);

            return response()->json([
                'data' => $rfqDraft,
                'message' => 'RFQs for this purchase request successfully marked as "Canvassing".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to issue draft RFQs.', $th, $purchaseRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve Request Quotations
     *
     * Mark approved request quotations and create an abstract of quotations.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @bodyParam mode_procurement_id string required The mode of procurement ID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase request successfully marked as For Abstract."
     * }
     */
    public function approveRequestQuotations(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $validated = $request->validate([
            'mode_procurement_id' => 'required|uuid',
        ]);

        try {
            $purchaseRequest = $this->service->approveRequestQuotations($purchaseRequest, $validated);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase request successfully marked as "For Abstract".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to mark the purchase request as "For Abstract".', $th, $purchaseRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Award Abstract Quotations
     *
     * Award approved abstract quotations and create purchase orders.
     *
     * @urlParam purchaseRequest string required The purchase request UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase request successfully marked as Awarded."
     * }
     */
    public function awardAbstractQuotations(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        try {
            $purchaseRequest = $this->service->awardAbstractQuotations($purchaseRequest);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase request successfully awarded.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to award the approved Abstract of Quotation(s).', $th, $purchaseRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    public function recreatePurchaseOrders(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        try {
            $purchaseRequest = $this->service->recreatePurchaseOrders($purchaseRequest);

            return response()->json([
                'data' => new PurchaseRequestResource($purchaseRequest),
                'message' => 'Purchase Orders recreated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to recreate Purchase Orders.', $th, $purchaseRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
