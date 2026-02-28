<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\ObligationRequestResource;
use App\Models\ObligationRequest;
use App\Services\ObligationRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Obligation Requests
 * APIs for managing obligation requests
 */
class ObligationRequestStatusController extends Controller
{
    public function __construct(
        protected ObligationRequestService $service
    ) {}

    /**
     * List Obligation Requests
     *
     * Retrieve a paginated list of obligation requests.
     *
     * @queryParam search string Search by OBR number, payee, etc.
     * @queryParam per_page int Number of items per page. Default: 50.
     * @queryParam show_all boolean Show all results without pagination. Default: false.
     * @queryParam column_sort string Sort field. Default: obr_no.
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
            return ObligationRequestResource::collection($result);
        }

        return response()->json([
            'data' => ObligationRequestResource::collection($result),
        ]);
    }

    /**
     * Get Obligation Request
     *
     * Display the specified obligation request.
     *
     * @urlParam id string required The obligation request UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     */
    public function show(ObligationRequest $obligationRequest): JsonResponse
    {
        return response()->json([
            'data' => new ObligationRequestResource($obligationRequest->load([
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
                'accounts.account',
            ])),
        ]);
    }

    /**
     * Update Obligation Request
     *
     * Update the specified obligation request.
     *
     * @urlParam id string required The obligation request UUID.
     *
     * @bodyParam funding array nullable The funding details.
     * @bodyParam office string nullable The office.
     * @bodyParam address string nullable The address.
     * @bodyParam responsibility_center_id string required The responsibility center ID.
     * @bodyParam particulars string required The particulars.
     * @bodyParam total_amount float required The total amount.
     * @bodyParam compliance_status array nullable The compliance status.
     * @bodyParam sig_head_id string required The head signatory ID.
     * @bodyParam head_signed_date date nullable The head signed date.
     * @bodyParam sig_budget_id string required The budget signatory ID.
     * @bodyParam budget_signed_date date nullable The budget signed date.
     * @bodyParam fpps array nullable The FPPs.
     * @bodyParam accounts array nullable The accounts.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Obligation request updated successfully."
     * }
     * @response 422 {
     *   "message": "Obligation request update failed."
     * }
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
            $obr = $this->service->update($obligationRequest, $validated);

            return response()->json([
                'data' => new ObligationRequestResource($obr),
                'message' => 'Obligation request updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Obligation request update failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark as Pending for Obligation
     *
     * Mark the obligation request as pending for obligation.
     *
     * @urlParam id string required The obligation request UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Obligation request successfully marked as pending for obligation."
     * }
     * @response 422 {
     *   "message": "Failed to set the obligation request to pending for obligation."
     * }
     */
    public function pending(ObligationRequest $obligationRequest): JsonResponse
    {
        try {
            $obr = $this->service->pending($obligationRequest);

            return response()->json([
                'data' => new ObligationRequestResource($obr),
                'message' => 'Obligation request successfully marked as pending for obligation.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Obligation request failed to mark as pending for obligation.', $th, $obligationRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Disapprove Obligation Request
     *
     * Mark the obligation request as disapproved.
     *
     * @urlParam id string required The obligation request UUID.
     *
     * @bodyParam disapproved_reason string nullable The reason for disapproval.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Obligation request successfully marked as Disapproved."
     * }
     * @response 422 {
     *   "message": "Failed to set the Obligation Request to Disapproved."
     * }
     */
    public function disapprove(Request $request, ObligationRequest $obligationRequest): JsonResponse
    {
        $validated = $request->validate([
            'disapproved_reason' => 'nullable|string',
        ]);

        try {
            $obr = $this->service->disapprove($obligationRequest, $validated['disapproved_reason'] ?? null);

            return response()->json([
                'data' => new ObligationRequestResource($obr),
                'message' => 'Obligation request successfully marked as "Disapproved".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Obligation request disapproval failed.', $th, $obligationRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark as Obligated
     *
     * Mark the obligation request as obligated and create disbursement voucher.
     *
     * @urlParam id string required The obligation request UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Obligation request successfully marked as Obligated."
     * }
     * @response 422 {
     *   "message": "Failed to set the Obligation Request to Obligated."
     * }
     */
    public function obligate(ObligationRequest $obligationRequest): JsonResponse
    {
        try {
            $obr = $this->service->obligate($obligationRequest);

            return response()->json([
                'data' => new ObligationRequestResource($obr),
                'message' => 'Obligation request successfully marked as "Obligated".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Obligation request failed to mark as Obligated.', $th, $obligationRequest->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
