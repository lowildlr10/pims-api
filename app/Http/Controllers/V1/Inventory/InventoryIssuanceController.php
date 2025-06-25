<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Enums\InventoryIssuanceStatus;
use App\Http\Controllers\Controller;
use App\Models\InventoryIssuance;
use App\Models\InventoryIssuanceItem;
use App\Models\PurchaseOrder;
use App\Models\InventorySupply;
use App\Repositories\InventoryIssuanceRepository;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryIssuanceController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository, InventoryIssuanceRepository $inventoryIssuanceRepository)
    {
        $this->logRepository = $logRepository;
        $this->inventoryIssuanceRepository = $inventoryIssuanceRepository;
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

                'issuances' => function ($query) {
                    $query->select(
                        'id', 'purchase_order_id', 'inventory_no', 'document_type',
                        'received_by_id', 'status'
                    )->orderByRaw("CAST(REPLACE(inventory_no, '-', '') AS INTEGER) desc");
                },
                'issuances.recipient:id,firstname,middlename,lastname',
            ])
            ->has('issuances');

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
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'purchase_order_id' => 'required',
            'responsibility_center_id' => 'nullable',
            'inventory_date' => 'required',
            'sai_no' => 'nullable|string',
            'sai_date' => 'nullable',
            'document_type' => 'required|in:ris,ics,are',
            'requested_by_id' => 'required',
            'requested_date' => 'nullable',
            'sig_approved_by_id' => 'required',
            'approved_date' => 'nullable',
            'sig_issued_by_id' => 'required',
            'issued_date' => 'nullable',
            'received_by_id' => 'required',
            'received_date' => 'nullable',
            'items' => 'required|array|min:1'
        ]);

        try {
            $message = 'Inventory issuance created successfully.';

            $inventoryIssuance = InventoryIssuance::create(array_merge(
                $validated,
                [
                    'inventory_no' => $this->inventoryIssuanceRepository->generateNewInventoryNumber($validated['document_type']),
                    'status' => InventoryIssuanceStatus::DRAFT,
                    'status_timestamps' => json_encode((Object)[])
                ]
            ));

            foreach ($validated['items'] ?? [] as $key => $item) {
                $quantity = intval($item['quantity']);
                $unitCost = floatval($item['unit_cost']);
                $totalCost = round($quantity * $unitCost, 2);

                InventoryIssuanceItem::create([
                    'inventory_issuance_id' => $inventoryIssuance->id,
                    'inventory_supply_id'   => $item['inventory_supply_id'],
                    'stock_no'              => (int) $item['stock_no'] ?? $key + 1,
                    'description'           => $item['description'],
                    'inventory_item_no'     => isset($item['inventory_item_no'])
                                                ? ($item['inventory_item_no'] ?? NULL)
                                                : NULL,
                    'property_no'           => isset($item['property_no'])
                                                ? ($item['property_no'] ?? NULL)
                                                : NULL,
                    'quantity'              => $quantity ?? 0,
                    'estimated_useful_life' => isset($item['estimated_useful_life'])
                                                ? ($item['estimated_useful_life'] ?? NULL)
                                                : NULL,
                    'acquired_date'         => isset($item['acquired_date'])
                                                ? ($item['acquired_date'] ?? NULL)
                                                : NULL,
                    'unit_cost'             => $unitCost,
                    'total_cost'            => $totalCost
                ]);
            }

            $inventoryIssuance->load('items');

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $inventoryIssuance->id,
                'log_module' => 'inv-issuance',
                'data' => $inventoryIssuance
            ]);

            return response()->json([
                'data' => [
                    'data' => $inventoryIssuance,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Inventory issuance creation failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_module' => 'inv-issuance',
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
    public function show(InventoryIssuance $inventoryIssuance): JsonResponse
    {
        $inventoryIssuance->load([
            'requestor',
            'signatory_approval:id,user_id',
            'signatory_approval.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_approval.detail' => function ($query) use ($inventoryIssuance) {
                $query->where('document', $inventoryIssuance->document_type)
                    ->where('signatory_type', '	approved_by');
            },
            'signatory_issuer:id,user_id',
            'signatory_issuer.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_issuer.detail' => function ($query) use ($inventoryIssuance) {
                $query->where('document', $inventoryIssuance->document_type)
                    ->where('signatory_type', '	issued_by');
            },
            'recipient',

            'items' => function($query) {
                $query->orderBy(
                    InventorySupply::select('item_sequence')
                        ->whereColumn(
                            'inventory_issuance_items.inventory_supply_id', 'inventory_supplies.id'
                        ),
                    'asc'
                );
            },
            'items.supply',

            'responsibility_center',
            'purchase_order',
            'purchase_order.purchase_request',
        ]);

        return response()->json([
            'data' => [
                'data' => $inventoryIssuance
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InventoryIssuance $inventoryIssuance)
    {
        // $user = auth()->user();

        // $validated = $request->validate([
        //     'sku' => 'nullable:string',
        //     'upc' => 'nullable:string',
        //     'name' => 'nullable:string',
        //     'description' => 'required:string',
        //     'item_classification_id' => 'required',
        //     'required_document' => 'required',
        // ]);

        // try {
        //     $message = 'Inventory supply updated successfully.';

        //     $this->inventorySupplyRepository->storeUpdate($validated, $inventorySupply);

        //     $this->logRepository->create([
        //         'message' => $message,
        //         'log_id' => $inventorySupply->id,
        //         'log_module' => 'inv-supply',
        //         'data' => $inventorySupply
        //     ]);

        //     return response()->json([
        //         'data' => [
        //             'data' => $inventorySupply,
        //             'message' => $message
        //         ]
        //     ]);
        // } catch (\Throwable $th) {
        //     $message = 'Inventory supply update failed.';

        //     $this->logRepository->create([
        //         'message' => $message,
        //         'details' => $th->getMessage(),
        //         'log_id' => $inventorySupply->id,
        //         'log_module' => 'inv-supply',
        //         'data' => $validated
        //     ], isError: true);

        //     return response()->json([
        //         'message' => "$message Please try again."
        //     ], 422);
        // }
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Supply $supply)
    // {
    //     //
    // }
}
