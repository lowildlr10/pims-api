<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventorySupply;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Repositories\InventorySupplyRepository;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class InventorySupplyController extends Controller
{
    private LogRepository $logRepository;

    private InventorySupplyRepository $inventorySupplyRepository;

    public function __construct(LogRepository $logRepository, InventorySupplyRepository $inventorySupplyRepository)
    {
        $this->logRepository = $logRepository;
        $this->inventorySupplyRepository = $inventorySupplyRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 10);
        $grouped = filter_var($request->get('grouped', true), FILTER_VALIDATE_BOOLEAN);
        $docuementType = $request->get('document_type');
        $searchByPo = filter_var($request->get('search_by_po', false), FILTER_VALIDATE_BOOLEAN);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'pr_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        if (! $grouped) {
            $inventorySupplies = InventorySupply::with([
                'unit_issue:id,unit_name',
                'item_classification:id,classification_name',
            ])
                ->when($search, function ($query) use ($search, $searchByPo) {
                    if ($searchByPo) {
                        $query->where(function ($query) use ($search) {
                            $query->whereRaw('CAST(purchase_order_id AS TEXT) = ?', [$search]);
                        });
                    } else {
                        $query->where(function ($query) use ($search) {
                            $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                                ->orWhereRaw('CAST(purchase_order_id AS TEXT) = ?', [$search])
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }
                })
                ->when($docuementType, function ($query) use ($docuementType) {
                    $query->where('required_document', $docuementType);
                })
                ->orderBy('item_sequence');

            $inventorySupplies = $showAll
                ? $inventorySupplies->get()
                : $inventorySupplies = $inventorySupplies->limit($perPage)->get();

            return response()->json([
                'data' => $inventorySupplies,
            ]);
        }

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

        if (! empty($search)) {
            $purchaseOrders = $purchaseOrders->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('po_date', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('supplier', 'supplier_name', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('supplies', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                            ->orWhere('sku', 'ILIKE', "%{$search}%")
                            ->orWhere('upc', 'ILIKE', "%{$search}%")
                            ->orWhere('name', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('issuances', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search]);
                    })
                    ->orWhereRelation('inspection_acceptance_report', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search]);
                    });
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'po_no':
                    $purchaseOrders = $purchaseOrders->orderByRaw("CAST(REPLACE(po_no, '-', '') AS INTEGER) {$sortDirection}");
                    break;

                case 'funding_source_title':
                    break;

                case 'supplier_name':
                    $purchaseOrders = $purchaseOrders->orderBy(
                        Supplier::select('supplier_name')->whereColumn('suppliers.id', 'purchase_orders.supplier_id'),
                        $sortDirection
                    );
                    break;

                case 'delivery_date_formatted':
                    $purchaseOrders = $purchaseOrders->orderBy('delivery_date', $sortDirection);
                    break;

                default:
                    $purchaseOrders = $purchaseOrders->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $purchaseOrders->paginate($perPage);
        } else {
            $purchaseOrders = $showAll
                ? $purchaseOrders->get()
                : $purchaseOrders = $purchaseOrders->limit($perPage)->get();

            return response()->json([
                'data' => $purchaseOrders,
            ]);
        }
    }

    /**
     * Display the specified resource.
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
            'data' => [
                'data' => $inventorySupply,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InventorySupply $inventorySupply): JsonResponse
    {
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
                'data' => $inventorySupply,
            ]);

            return response()->json([
                'data' => [
                    'data' => $inventorySupply,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Inventory supply update failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $inventorySupply->id,
                'log_module' => 'inv-supply',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => "$message Please try again.",
            ], 422);
        }
    }
}
