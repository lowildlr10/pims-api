<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventorySupplyResource;
use App\Models\InventorySupply;
use App\Services\InventorySupplyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Inventory Supplies
 * APIs for managing inventory supplies
 */
class InventorySupplyController extends Controller
{
    public function __construct(
        protected InventorySupplyService $service
    ) {}

    /**
     * List Inventory Supplies
     *
     * Retrieve a paginated list of inventory supplies grouped by purchase order.
     *
     * @queryParam search string Search by PO number, supply name, etc.
     * @queryParam per_page int Number of items per page. Default: 10.
     * @queryParam grouped boolean Group results by purchase order. Default: true.
     * @queryParam document_type string Filter by required document type.
     * @queryParam search_by_po boolean Search by purchase order ID. Default: false.
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
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $filters = $request->only([
            'search',
            'per_page',
            'grouped',
            'document_type',
            'search_by_po',
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
     * Get Inventory Supply
     *
     * Display the specified inventory supply.
     *
     * @urlParam inventorySupply string required The inventory supply UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     */
    public function show(InventorySupply $inventorySupply): JsonResponse
    {
        $inventorySupply->load([
            'unit_issue:id,unit_name',
            'item_classification:id,classification_name',
            'issued_items:id,inventory_supply_id,inventory_issuance_id,quantity,inventory_item_no,property_no,acquired_date,estimated_useful_life',
            'issued_items.issuance',
            'issued_items.issuance.recipient:id,firstname,middlename,lastname',
        ]);

        return response()->json([
            'data' => new InventorySupplyResource($inventorySupply),
        ]);
    }

    /**
     * Update Inventory Supply
     *
     * Update the specified inventory supply in storage.
     *
     * @urlParam inventorySupply string required The inventory supply UUID.
     *
     * @bodyParam sku string nullable The SKU.
     * @bodyParam upc string nullable The UPC.
     * @bodyParam name string nullable The supply name.
     * @bodyParam description string required The supply description.
     * @bodyParam item_classification_id string required The item classification ID.
     * @bodyParam required_document string required The required document type.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Inventory supply updated successfully."
     * }
     */
    public function update(Request $request, InventorySupply $inventorySupply): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'nullable|string',
            'upc' => 'nullable|string',
            'name' => 'nullable|string',
            'description' => 'required|string',
            'item_classification_id' => 'required',
            'required_document' => 'required',
        ]);

        try {
            $inventorySupply = $this->service->update($inventorySupply->id, $validated);

            return response()->json([
                'data' => new InventorySupplyResource($inventorySupply),
                'message' => 'Inventory supply updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Inventory supply update failed.', $th, $validated);

            return response()->json([
                'message' => 'Inventory supply update failed. Please try again.',
            ], 422);
        }
    }
}
