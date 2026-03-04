<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseRequestResource;
use App\Http\Resources\RequestQuotationResource;
use App\Models\RequestQuotation;
use App\Services\RequestQuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Request Quotations
 * APIs for managing request for quotations (RFQ)
 */
class RequestQuotationController extends Controller
{
    public function __construct(
        protected RequestQuotationService $service
    ) {}

    /**
     * List Purchase Requests with RFQs
     *
     * Retrieve a paginated list of purchase requests that have RFQs.
     *
     * @queryParam search string Search by PR number, purpose, etc.
     * @queryParam per_page int Number of items per page. Default: 50.
     * @queryParam show_all boolean Show all results without pagination. Default: false.
     * @queryParam column_sort string Sort field. Default: pr_no.
     * @queryParam sort_direction string Sort direction (asc/desc). Default: desc.
     * @queryParam paginated boolean Return paginated results. Default: true.
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
        ]);

        $paginated = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $result = $this->service->getAll($filters);

        if ($paginated) {
            return PurchaseRequestResource::collection($result);
        }

        $showAll = filter_var($filters['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $results = $showAll ? $result->get() : $result->limit($filters['per_page'] ?? 50)->get();

        return response()->json([
            'data' => PurchaseRequestResource::collection($results),
        ]);
    }

    /**
     * Create Request Quotation
     *
     * Create a new request for quotation.
     *
     * @bodyParam rfq_no string required The RFQ number.
     * @bodyParam purchase_request_id string required The purchase request ID.
     * @bodyParam copies int required Number of copies (1-10).
     * @bodyParam signed_type string required The signed type (bac/lce).
     * @bodyParam rfq_date date required The RFQ date.
     * @bodyParam supplier_id string nullable The supplier ID.
     * @bodyParam opening_dt datetime nullable The opening date/time.
     * @bodyParam sig_approval_id string required The approval signatory ID.
     * @bodyParam canvassers array nullable List of canvasser user IDs.
     * @bodyParam items array required The RFQ items.
     * @bodyParam items.*.pr_item_id string required The PR item ID.
     * @bodyParam items.*.included boolean required Whether the item is included.
     * @bodyParam vat_registered boolean nullable Whether supplier is VAT registered.
     *
     * @response 201 {
     *   "data": {...},
     *   "message": "Request for quotation created successfully."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfq_no' => 'required',
            'purchase_request_id' => 'required',
            'copies' => 'required|numeric|min:1|max:10',
            'signed_type' => 'required|string',
            'rfq_date' => 'required',
            'supplier_id' => 'nullable',
            'opening_dt' => 'nullable',
            'sig_approval_id' => 'required',
            'canvassers' => 'nullable|array',
            'items' => 'required|array|min:1',
            'vat_registered' => 'nullable|boolean',
        ]);

        try {
            $result = $this->service->create($validated);

            return response()->json([
                'data' => $result['purchase_request'],
                'message' => 'Request for quotation created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Request quotation creation failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Request Quotation
     *
     * Display the specified request for quotation.
     *
     * @urlParam requestQuotation string required The request quotation UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     * @response 404 {
     *   "message": "Request quotation not found."
     * }
     */
    public function show(string $id): JsonResponse
    {
        $requestQuotation = $this->service->getById($id);

        if (! $requestQuotation) {
            return response()->json(['message' => 'Request quotation not found.'], 404);
        }

        return response()->json([
            'data' => new RequestQuotationResource($requestQuotation),
        ]);
    }

    /**
     * Update Request Quotation
     *
     * Update an existing request for quotation.
     *
     * @urlParam requestQuotation string required The request quotation UUID.
     *
     * @bodyParam rfq_no string required The RFQ number.
     * @bodyParam signed_type string required The signed type (bac/lce).
     * @bodyParam rfq_date date required The RFQ date.
     * @bodyParam supplier_id string nullable The supplier ID.
     * @bodyParam opening_dt datetime nullable The opening date/time.
     * @bodyParam sig_approval_id string required The approval signatory ID.
     * @bodyParam canvassers array nullable List of canvasser user IDs.
     * @bodyParam items array required The RFQ items.
     * @bodyParam items.*.pr_item_id string required The PR item ID.
     * @bodyParam items.*.quantity int required Item quantity.
     * @bodyParam items.*.unit_cost float nullable Unit cost.
     * @bodyParam items.*.brand_model string nullable Brand/model.
     * @bodyParam items.*.included boolean required Whether the item is included.
     * @bodyParam vat_registered boolean nullable Whether supplier is VAT registered.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Request for quotation updated successfully."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function update(Request $request, RequestQuotation $requestQuotation): JsonResponse
    {
        $validated = $request->validate([
            'rfq_no' => 'required',
            'signed_type' => 'required|string',
            'rfq_date' => 'required',
            'supplier_id' => 'nullable',
            'opening_dt' => 'nullable',
            'sig_approval_id' => 'required',
            'canvassers' => 'nullable|array',
            'items' => 'required|array|min:1',
            'vat_registered' => 'nullable|boolean',
        ]);

        try {
            $requestQuotation = $this->service->update($requestQuotation, $validated);

            return response()->json([
                'data' => new RequestQuotationResource($requestQuotation),
                'message' => 'Request for quotation updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Request quotation update failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Issue for Canvassing
     *
     * Mark the request quotation as canvassing.
     *
     * @urlParam requestQuotation string required The request quotation UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Request for quotation successfully marked as Canvassing."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function issueCanvassing(RequestQuotation $requestQuotation): JsonResponse
    {
        try {
            $result = $this->service->issueCanvassing($requestQuotation);

            return response()->json([
                'data' => new RequestQuotationResource($result['request_quotation']->load('purchase_request')),
                'message' => 'Request for quotation successfully marked as "Canvassing".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Issue for canvassing failed.', $th, $requestQuotation->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Complete Canvassing
     *
     * Mark the request quotation as completed.
     *
     * @urlParam requestQuotation string required The request quotation UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Request for quotation successfully marked as Completed."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function canvassComplete(RequestQuotation $requestQuotation): JsonResponse
    {
        try {
            $requestQuotation = $this->service->canvassComplete($requestQuotation);

            return response()->json([
                'data' => new RequestQuotationResource($requestQuotation),
                'message' => 'Request for quotation successfully marked as "Completed".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Canvass completion failed.', $th, $requestQuotation->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel Request Quotation
     *
     * Cancel the request quotation.
     *
     * @urlParam requestQuotation string required The request quotation UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Request for quotation successfully marked as Cancelled."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function cancel(RequestQuotation $requestQuotation): JsonResponse
    {
        try {
            $requestQuotation = $this->service->cancel($requestQuotation);

            return response()->json([
                'data' => new RequestQuotationResource($requestQuotation),
                'message' => 'Request for quotation successfully marked as "Cancelled".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Cancellation failed.', $th, $requestQuotation->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
