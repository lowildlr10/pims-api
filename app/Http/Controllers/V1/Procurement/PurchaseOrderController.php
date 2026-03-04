<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * @group Purchase Orders
 * APIs for managing purchase orders
 */
class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $service
    ) {}

    /**
     * List Purchase Orders
     *
     * Retrieve a paginated list of purchase orders grouped by PR.
     *
     * @queryParam search string Search by PR number, PO number, etc.
     * @queryParam per_page int Number of items per page. Default: 50.
     * @queryParam grouped boolean Group results by PR. Default: true.
     * @queryParam has_supplies_only boolean Show only POs with supplies. Default: false.
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
            'grouped',
            'has_supplies_only',
            'show_all',
            'column_sort',
            'sort_direction',
            'paginated',
            'status',
        ]);

        $grouped = filter_var($filters['grouped'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $paginated = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $user = Auth::user();

        if (! $grouped) {
            $results = $this->service->getAllUngrouped($filters);

            return response()->json([
                'data' => PurchaseOrderResource::collection($results),
            ]);
        }

        $result = $this->service->getAll($filters, $user);

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
     * Get Purchase Order
     *
     * Display the specified purchase order.
     *
     * @urlParam purchaseOrder string required The purchase order UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     * @response 404 {
     *   "message": "Purchase order not found."
     * }
     */
    public function show(string $id): JsonResponse
    {
        $purchaseOrder = $this->service->getById($id);

        if (! $purchaseOrder) {
            return response()->json(['message' => 'Purchase order not found.'], 404);
        }

        return response()->json([
            'data' => new PurchaseOrderResource($purchaseOrder),
        ]);
    }

    /**
     * Update Purchase Order
     *
     * Update the specified purchase order in storage.
     *
     * @urlParam purchaseOrder string required The purchase order UUID.
     *
     * @bodyParam po_date date required The PO date.
     * @bodyParam place_delivery string required The place of delivery.
     * @bodyParam delivery_date date required The delivery date.
     * @bodyParam delivery_term string required The delivery term.
     * @bodyParam payment_term string required The payment term.
     * @bodyParam total_amount_words string required The total amount in words.
     * @bodyParam sig_approval_id string required The approval signatory ID.
     * @bodyParam items array required The PO items.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase order updated successfully."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
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
            $purchaseOrder = $this->service->createOrUpdate($validated, $purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource($purchaseOrder->load('items')),
                'message' => 'Purchase order updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase order update failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Set Purchase Order as Pending
     *
     * Mark the purchase order as pending for approval.
     *
     * @urlParam purchaseOrder string required The purchase order UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase order successfully marked as Pending."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function pending(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $purchaseOrder = $this->service->pending($purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource($purchaseOrder->load('items')),
                'message' => 'Purchase order successfully marked as "Pending".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase order pending failed.', $th, $purchaseOrder->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve Purchase Order
     *
     * Mark the purchase order as approved.
     *
     * @urlParam purchaseOrder string required The purchase order UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase order successfully marked as Approved."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function approve(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $purchaseOrder = $this->service->approve($purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource($purchaseOrder->load('items')),
                'message' => 'Purchase order successfully marked as "Approved".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase order approval failed.', $th, $purchaseOrder->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Issue Purchase Order
     *
     * Issue the purchase order to supplier.
     *
     * @urlParam purchaseOrder string required The purchase order UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase order successfully issued to supplier."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function issue(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $purchaseOrder = $this->service->issue($purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource($purchaseOrder->load('items')),
                'message' => 'Purchase order successfully issued to supplier.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase order issue failed.', $th, $purchaseOrder->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Receive Purchase Order
     *
     * Mark the purchase order as received/for delivery.
     *
     * @urlParam purchaseOrder string required The purchase order UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase order successfully received and marked as For Delivery."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function receive(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $purchaseOrder = $this->service->receive($purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource($purchaseOrder->load('items')),
                'message' => 'Purchase order successfully received and marked as "For Delivery".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase order receive failed.', $th, $purchaseOrder->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark Purchase Order as Delivered
     *
     * Mark the purchase order as delivered and create IAR.
     *
     * @urlParam purchaseOrder string required The purchase order UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Purchase order successfully set to Delivered."
     * }
     * @response 422 {
     *   "message": "Error message"
     * }
     */
    public function delivered(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $purchaseOrder = $this->service->delivered($purchaseOrder);

            return response()->json([
                'data' => new PurchaseOrderResource($purchaseOrder->load('items')),
                'message' => 'Purchase order successfully set to "Delivered".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Purchase order delivery failed.', $th, $purchaseOrder->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
