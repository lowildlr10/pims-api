<?php

namespace App\Http\Controllers\V1\Inventory;

use App\Enums\InventoryIssuanceStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Http\Controllers\Controller;
use App\Models\InventoryIssuance;
use App\Models\InventoryIssuanceItem;
use App\Models\PurchaseOrder;
use App\Models\InventorySupply;
use App\Models\Supplier;
use App\Repositories\InventoryIssuanceRepository;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\Undefined;

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
        $perPage = $request->get('per_page', 10);
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

        if (!empty($search)) {
            $purchaseOrders = $purchaseOrders->where(function($query) use ($search){
                $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                    ->orWhere('po_date', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('supplier', 'supplier_name', 'ILIKE' , "%{$search}%")
                    ->orWhereRelation('issuances', function ($query) use ($search) {
                        $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                            ->orWhere('document_type', 'ILIKE', "%{$search}%")
                            ->orWhere('inventory_no', 'ILIKE', "%{$search}%")
                            ->orWhere('inventory_date', 'ILIKE', "%{$search}%")
                            ->orWhere('status', 'ILIKE', "%{$search}%")
                            ->orWhereRelation('recipient', function ($query) use ($search) {
                                $query->where('firstname', 'ILIKE', "%{$search}%")
                                    ->orWhere('middlename', 'ILIKE', "%{$search}%")
                                    ->orWhere('lastname', 'ILIKE', "%{$search}%");
                            });
                    })
                    ->orWhereRelation('supplies', function ($query) use ($search) {
                        $query->whereRaw("CAST(id AS TEXT) = ?", [$search]);
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
            'requested_by_id' => 'nullable',
            'requested_date' => 'nullable',
            'sig_approved_by_id' => 'nullable',
            'approved_date' => 'nullable',
            'sig_issued_by_id' => 'required',
            'issued_date' => 'nullable',
            'received_by_id' => 'required',
            'received_date' => 'nullable',
            'items' => 'required|array|min:1'
        ]);

        try {
            $message = 'Inventory issuance created successfully.';

            $inventoryIssuance = $this->inventoryIssuanceRepository->storeUpdate($validated, NULL);

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
            'items.supply.unit_issue:id,unit_name',

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
    public function update(Request $request, InventoryIssuance $inventoryIssuance): JsonResponse
    {
        $user = auth()->user();

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
            'items' => 'required|array|min:1'
        ]);

        try {
            $message = 'Inventory issuance update successfully.';

            $this->inventoryIssuanceRepository->storeUpdate($validated, $inventoryIssuance);

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
            $message = 'Inventory issuance update failed.';

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
     * Update the status of the specified resource in storage.
     */
    public function pending(InventoryIssuance $inventoryIssuance): JsonResponse
    {
        try {
            $message = 'Inventory issuance successfully marked as "Pending".';
            $issuanceCurrentStatus = InventoryIssuanceStatus::from($inventoryIssuance->status);
            $newStatus = InventoryIssuanceStatus::PENDING;

            if ($inventoryIssuance && $issuanceCurrentStatus !== InventoryIssuanceStatus::DRAFT) {
                $message = 'Failed to set inventory issuance to pending. This document has already been issued or is still processing.';

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $inventoryIssuance->id,
                    'log_module' => 'inv-issuance',
                    'data' => $inventoryIssuance
                ]);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            if (empty($inventoryIssuance->received_by_id) || empty($inventoryIssuance->sig_issued_by_id)) {
                $message = 'Failed to set inventory issuance to pending. Both a receiver and an issuer must be specified.';

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $inventoryIssuance->id,
                    'log_module' => 'inv-issuance',
                    'data' => $inventoryIssuance
                ]);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            $inventoryIssuance->update([
                'status' => $newStatus,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'pending_at', $inventoryIssuance->status_timestamps
                )
            ]);

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
            $message = 'Failed to set inventory issuance to pending.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $inventoryIssuance->id,
                'log_module' => 'inv-issuance',
                'data' => $inventoryIssuance
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function issue(InventoryIssuance $inventoryIssuance): JsonResponse
    {
        try {
            $message = 'Inventory issuance successfully marked as "Issued".';
            $issuanceCurrentStatus = InventoryIssuanceStatus::from($inventoryIssuance->status);
            $newStatus = InventoryIssuanceStatus::ISSUED;

            if ($inventoryIssuance && $issuanceCurrentStatus !== InventoryIssuanceStatus::PENDING) {
                $message = 'Failed to set inventory issuance to issued. This document has already been issued or is still draft.';

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $inventoryIssuance->id,
                    'log_module' => 'inv-issuance',
                    'data' => $inventoryIssuance
                ]);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            if (empty($inventoryIssuance->received_by_id) || empty($inventoryIssuance->sig_issued_by_id)) {
                $message = 'Failed to set inventory issuance to pending. Both a receiver and an issuer must be specified.';

                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $inventoryIssuance->id,
                    'log_module' => 'inv-issuance',
                    'data' => $inventoryIssuance
                ]);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            $inventoryIssuance->update([
                'status' => $newStatus,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'issued_at', $inventoryIssuance->status_timestamps
                )
            ]);

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
            $message = 'Failed to set inventory issuance to issued.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $inventoryIssuance->id,
                'log_module' => 'inv-issuance',
                'data' => $inventoryIssuance
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function cancel(InventoryIssuance $inventoryIssuance): JsonResponse
    {
        try {
            $message = 'Inventory issuance successfully marked as "Cancelled".';
            $issuanceCurrentStatus = InventoryIssuanceStatus::from($inventoryIssuance->status);
            $newStatus = InventoryIssuanceStatus::CANCELLED;

            $inventoryIssuance->update([
                'status' => $newStatus,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'cancelled_at', $inventoryIssuance->status_timestamps
                )
            ]);

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
            $message = 'Failed to set inventory issuance to cancelled.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $inventoryIssuance->id,
                'log_module' => 'inv-issuance',
                'data' => $inventoryIssuance
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
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
