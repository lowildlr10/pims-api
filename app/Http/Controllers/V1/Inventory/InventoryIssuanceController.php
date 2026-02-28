<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryIssuanceResource;
use App\Models\InventoryIssuance;
use App\Services\InventoryIssuanceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Inventory Issuances
 * APIs for managing inventory issuances (RIS, ICS, ARE)
 */
class InventoryIssuanceController extends Controller
{
    public function __construct(
        protected InventoryIssuanceService $service
    ) {}

    /**
     * List Inventory Issuances
     *
     * Retrieve a paginated list of inventory issuances grouped by purchase order.
     *
     * @queryParam search string Search by PO number, inventory number, etc.
     * @queryParam per_page int Number of items per page. Default: 10.
     * @queryParam show_all boolean Show all results without pagination. Default: false.
     * @queryParam column_sort string Sort field. Default: po_no.
     * @queryParam sort_direction string Sort direction (asc/desc). Default: desc.
     * @queryParam paginated boolean Return paginated results. Default: true.
     *
     * @response 200 {
     *   "data": [...],
     *   "links": {...},
     *   "meta": {...}
     * }
     */
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $filters = $request->only([
            'search',
            'per_page',
            'show_all',
            'column_sort',
            'sort_direction',
            'paginated',
        ]);

        $result = $this->service->getAll($filters);

        if ($result instanceof LengthAwarePaginator) {
            return $result;
        }

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Create Inventory Issuance
     *
     * Store a newly created inventory issuance.
     *
     * @bodyParam purchase_order_id string required The purchase order ID.
     * @bodyParam responsibility_center_id string nullable The responsibility center ID.
     * @bodyParam inventory_date date required The inventory date.
     * @bodyParam sai_no string nullable The SAI number.
     * @bodyParam sai_date date nullable The SAI date.
     * @bodyParam document_type string required The document type (ris, ics, are).
     * @bodyParam requested_by_id string nullable The requestor user ID.
     * @bodyParam requested_date date nullable The requested date.
     * @bodyParam sig_approved_by_id string nullable The signatory for approval ID.
     * @bodyParam approved_date date nullable The approved date.
     * @bodyParam sig_issued_by_id string required The signatory for issuance ID.
     * @bodyParam issued_date date nullable The issued date.
     * @bodyParam received_by_id string required The receiver user ID.
     * @bodyParam received_date date nullable The received date.
     * @bodyParam items array required The issuance items.
     *
     * @response 201 {
     *   "data": {...},
     *   "message": "Inventory issuance created successfully."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required',
            'responsibility_center_id' => 'nullable',
            'inventory_date' => 'required',
            'sai_no' => 'nullable|string',
            'sai_date' => 'nullable',
            'document_type' => 'required|in:ris,ics,are',
            'requested_by_id' => 'nullable',
            'requested_date' => 'nullable',
            'sig_approved_by_id' => 'nullable',
            'approved_date' => 'nullable',
            'sig_issued_by_id' => 'required',
            'issued_date' => 'nullable',
            'received_by_id' => 'required',
            'received_date' => 'nullable',
            'items' => 'required|array|min:1',
        ]);

        try {
            $inventoryIssuance = $this->service->create($validated);

            return response()->json([
                'data' => new InventoryIssuanceResource($inventoryIssuance),
                'message' => 'Inventory issuance created successfully.',
            ], 201);
        } catch (\Throwable $th) {
            $this->service->logError('Inventory issuance creation failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Get Inventory Issuance
     *
     * Display the specified inventory issuance.
     *
     * @urlParam inventoryIssuance string required The inventory issuance UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     */
    public function show(InventoryIssuance $inventoryIssuance): JsonResponse
    {
        $inventoryIssuance = $this->service->getById($inventoryIssuance->id);

        if (! $inventoryIssuance) {
            return response()->json(['message' => 'Inventory issuance not found.'], 404);
        }

        return response()->json([
            'data' => new InventoryIssuanceResource($inventoryIssuance),
        ]);
    }

    /**
     * Update Inventory Issuance
     *
     * Update the specified inventory issuance in storage.
     *
     * @urlParam inventoryIssuance string required The inventory issuance UUID.
     *
     * @bodyParam purchase_order_id string required The purchase order ID.
     * @bodyParam responsibility_center_id string nullable The responsibility center ID.
     * @bodyParam inventory_date date required The inventory date.
     * @bodyParam sai_no string nullable The SAI number.
     * @bodyParam sai_date date nullable The SAI date.
     * @bodyParam document_type string required The document type (ris, ics, are).
     * @bodyParam requested_by_id string nullable The requestor user ID.
     * @bodyParam requested_date date nullable The requested date.
     * @bodyParam sig_approved_by_id string nullable The signatory for approval ID.
     * @bodyParam approved_date date nullable The approved date.
     * @bodyParam sig_issued_by_id string required The signatory for issuance ID.
     * @bodyParam issued_date date nullable The issued date.
     * @bodyParam received_by_id string required The receiver user ID.
     * @bodyParam received_date date nullable The received date.
     * @bodyParam items array required The issuance items.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Inventory issuance updated successfully."
     * }
     */
    public function update(Request $request, InventoryIssuance $inventoryIssuance): JsonResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required',
            'responsibility_center_id' => 'nullable',
            'inventory_date' => 'required',
            'sai_no' => 'nullable|string',
            'sai_date' => 'nullable',
            'document_type' => 'required|in:ris,ics,are',
            'requested_by_id' => 'nullable',
            'requested_date' => 'nullable',
            'sig_approved_by_id' => 'nullable',
            'approved_date' => 'nullable',
            'sig_issued_by_id' => 'required',
            'issued_date' => 'nullable',
            'received_by_id' => 'required',
            'received_date' => 'nullable',
            'items' => 'required|array|min:1',
        ]);

        try {
            $inventoryIssuance = $this->service->update($inventoryIssuance, $validated);

            return response()->json([
                'data' => new InventoryIssuanceResource($inventoryIssuance),
                'message' => 'Inventory issuance updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Inventory issuance update failed.', $th, $validated);

            return response()->json([
                'message' => 'Inventory issuance update failed. Please try again.',
            ], 422);
        }
    }

    /**
     * Set Inventory Issuance as Pending
     *
     * Mark the inventory issuance as pending.
     *
     * @urlParam inventoryIssuance string required The inventory issuance UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Inventory issuance successfully marked as Pending."
     * }
     */
    public function pending(InventoryIssuance $inventoryIssuance): JsonResponse
    {
        try {
            $inventoryIssuance = $this->service->pending($inventoryIssuance);

            return response()->json([
                'data' => new InventoryIssuanceResource($inventoryIssuance),
                'message' => 'Inventory issuance successfully marked as "Pending".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to set inventory issuance to pending.', $th, $inventoryIssuance->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Issue Inventory
     *
     * Mark the inventory issuance as issued.
     *
     * @urlParam inventoryIssuance string required The inventory issuance UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Inventory issuance successfully marked as Issued."
     * }
     */
    public function issue(InventoryIssuance $inventoryIssuance): JsonResponse
    {
        try {
            $inventoryIssuance = $this->service->issue($inventoryIssuance);

            return response()->json([
                'data' => new InventoryIssuanceResource($inventoryIssuance),
                'message' => 'Inventory issuance successfully marked as "Issued".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to set inventory issuance to issued.', $th, $inventoryIssuance->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel Inventory Issuance
     *
     * Mark the inventory issuance as cancelled.
     *
     * @urlParam inventoryIssuance string required The inventory issuance UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Inventory issuance successfully marked as Cancelled."
     * }
     */
    public function cancel(InventoryIssuance $inventoryIssuance): JsonResponse
    {
        try {
            $inventoryIssuance = $this->service->cancel($inventoryIssuance);

            return response()->json([
                'data' => new InventoryIssuanceResource($inventoryIssuance),
                'message' => 'Inventory issuance successfully marked as "Cancelled".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Failed to set inventory issuance to cancelled.', $th, $inventoryIssuance->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
