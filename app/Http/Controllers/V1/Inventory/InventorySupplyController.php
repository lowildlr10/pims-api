<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\InventorySupply;
use App\Repositories\LogRepository;
use App\Repositories\InventorySupplyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class InventorySupplyController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository, InventorySupplyRepository $inventorySupplyRepository)
    {
        $this->logRepository = $logRepository;
        $this->inventorySupplyRepository = $inventorySupplyRepository;
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

        $purchaseOrders = PurchaseOrder::query()
            ->select('id', 'purchase_request_id', 'supplier_id', 'po_no', 'status_timestamps')
            ->with([
                'purchase_request:id,funding_source_id',
                'purchase_request.funding_source:id,title',

                'supplier:id,supplier_name',

                'supplies' => function ($query) {
                    $query->select(
                        'id', 'purchase_order_id', 'created_at', 'name', 'description',
                        'unit_issue_id', 'item_classification_id', 'quantity',
                        'required_document', 'item_sequence'
                    )->orderBy('item_sequence', 'asc');
                },
                'supplies.unit_issue:id,unit_name',
                'supplies.item_classification:id,classification_name',
            ])
            ->has('supplies');

        if ($paginated) {
            return $purchaseOrders->paginate($perPage);
        } else {
            $purchaseOrders = $showAll
                ? $purchaseOrders->get()
                : $purchaseOrders = $purchaseOrders->limit($perPage)->get();

            return response()->json([
                'data' => $purchaseOrders
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     //
    // }

    /**
     * Display the specified resource.
     */
    public function show(InventorySupply $inventorySupply)
    {
        $inventorySupply->load([
            'unit_issue:id,unit_name',
            'item_classification:id,classification_name',
        ]);

        return response()->json([
            'data' => [
                'data' => $inventorySupply
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InventorySupply $inventorySupply)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'sku' => 'nullable:string',
            'upc' => 'nullable:string',
            'name' => 'nullable:string',
            'description' => 'required:string',
            'item_classification_id' => 'required',
            'required_document' => 'required',
        ]);

        try {
            $message = 'Inventory supply updated successfully.';

            $this->inventorySupplyRepository->storeUpdate($validated, $inventorySupply);

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $inventorySupply->id,
                'log_module' => 'inv-supply',
                'data' => $inventorySupply
            ]);

            return response()->json([
                'data' => [
                    'data' => $inventorySupply,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Inventory supply update failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $inventorySupply->id,
                'log_module' => 'inv-supply',
                'data' => $validated
            ], isError: true);

            return response()->json([
                'message' => "$message Please try again."
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Supply $supply)
    // {
    //     //
    // }
}
