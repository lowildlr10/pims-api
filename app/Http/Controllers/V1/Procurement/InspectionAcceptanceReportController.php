<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Enums\InspectionAcceptanceReportStatus;
use App\Enums\PurchaseOrderStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Http\Controllers\Controller;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequestItem;
use App\Models\Signatory;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\LogRepository;
use App\Repositories\InventorySupplyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class InspectionAcceptanceReportController extends Controller
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
        $columnSort = $request->get('column_sort', 'iar_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $inspectionAcceptanceReport = InspectionAcceptanceReport::query()
            ->select([
                'id',
                'purchase_order_id',
                'supplier_id',
                'iar_no',
                'iar_date',
                'sig_inspection_id',
                'status'
            ])
            ->with([
                'purchase_order:id,po_no,supplier_id',
                'supplier:id,supplier_name',
                'signatory_inspection.user:id,firstname,middlename,lastname',
                'signatory_inspection.detail' => function ($query) {
                    $query->where('document', 'iar')
                        ->where('signatory_type', '	inspection');
                }
            ]);

        if (!empty($search)) {
            $inspectionAcceptanceReport->where(function ($query) use ($search) {
                $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                    ->orWhere('iar_no', 'ILIKE', "%{$search}%")
                    ->orWhere('iar_date', 'ILIKE', "%{$search}%")
                    ->orWhere('invoice_no', 'ILIKE', "%{$search}%")
                    ->orWhere('inspected_date', 'ILIKE', "%{$search}%")
                    ->orWhere('inspected', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('signatory_inspection.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('acceptance', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('purchase_order', function ($query) use ($search) {
                        $query->whereRaw("CAST(id AS TEXT) = ?", [$search])
                            ->orWhere('po_no', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('supplier', function ($query) use ($search) {
                        $query->where('supplier_name', 'ILIKE', "%{$search}%");
                    });
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'iar_no':
                    $inspectionAcceptanceReport = $inspectionAcceptanceReport->orderByRaw("CAST(REPLACE(iar_no, '-', '') AS INTEGER) {$sortDirection}");
                    break;

                case 'iar_date_formatted':
                    $inspectionAcceptanceReport = $inspectionAcceptanceReport->orderBy('iar_date', $sortDirection);
                    break;

                case 'po_no':
                    $inspectionAcceptanceReport = $inspectionAcceptanceReport->orderBy(
                        PurchaseOrder::select('po_no')->whereColumn('purchase_orders.id', 'inspection_acceptance_reports.purchase_order_id'),
                        $sortDirection
                    );
                    break;

                case 'supplier':
                    $inspectionAcceptanceReport = $inspectionAcceptanceReport->orderBy(
                        Supplier::select('supplier_name')->whereColumn('suppliers.id', 'inspection_acceptance_reports.supplier_id'),
                        $sortDirection
                    );
                    break;

                case 'signatory_inspection':
                    $inspectionAcceptanceReport = $inspectionAcceptanceReport->orderBy(
                        User::select('firstname')
                            ->join('signatories', 'signatories.user_id', '=', 'users.id')
                            ->whereColumn('signatories.id', 'inspection_acceptance_reports.sig_inspection_id')
                            ->limit(1),
                        $sortDirection
                    );
                    break;

                case 'status_formatted':
                    $inspectionAcceptanceReport = $inspectionAcceptanceReport->orderBy('status', $sortDirection);
                    break;

                default:
                    $inspectionAcceptanceReport = $inspectionAcceptanceReport->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $inspectionAcceptanceReport->paginate($perPage);
        } else {
            $inspectionAcceptanceReport = $showAll
                ? $inspectionAcceptanceReport->get()
                : $inspectionAcceptanceReport = $inspectionAcceptanceReport->limit($perPage)->get();

            return response()->json([
                'data' => $inspectionAcceptanceReport
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(InspectionAcceptanceReport $inspectionAcceptanceReport)
    {
        $inspectionAcceptanceReport->load([
            'supplier:id,supplier_name',
            'items' => function($query) {
                $query->orderBy(
                    PurchaseRequestItem::select('item_sequence')
                        ->whereColumn(
                            'inspection_acceptance_report_items.pr_item_id', 'purchase_request_items.id'
                        ),
                    'asc'
                );
            },
            'items.pr_item:id,unit_issue_id,item_sequence,quantity,stock_no',
            'items.pr_item.unit_issue:id,unit_name',
            'items.po_item:id,purchase_order_id,description,brand_model,unit_cost,total_cost',
            'signatory_inspection:id,user_id',
            'signatory_inspection.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_inspection.detail' => function ($query) {
                $query->where('document', 'iar')
                    ->where('signatory_type', '	inspection');
            },
            'acceptance:id,firstname,middlename,lastname,allow_signature,signature,position_id,designation_id',
            'acceptance.position:id,position_name',
            'acceptance.designation:id,designation_name',
            'purchase_request:id,section_id',
            'purchase_request.section:id,section_name',
            'purchase_order:id,po_no,po_date,document_type'
        ]);

        return response()->json([
            'data' => [
                'data' => $inspectionAcceptanceReport
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InspectionAcceptanceReport $inspectionAcceptanceReport): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'iar_date' => 'required',
            'invoice_no' => 'required',
            'invoice_date' => 'required',
            'inspected_date' => 'nullable',
            'inspected' => 'nullable|boolean',
            'sig_inspection_id' => 'nullable|exists:signatories,id',
            'acceptance_id' => 'nullable|exists:users,id',
            'received_date' => 'nullable',
            'acceptance_completed' => 'nullable|boolean',
        ]);
        
        $validated['inspected'] = isset($validated['inspected']) 
            ? $request->boolean('inspected') 
            : NULL;
        $validated['acceptance_completed'] = isset($validated['acceptance_completed']) 
            ? $request->boolean('acceptance_completed') 
            : NULL;

        try {
            $message = 'Inspection and acceptance report updated successfully.';

            $inspectionAcceptanceReport->update($validated);

            $inspectionAcceptanceReport->load('items');

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $inspectionAcceptanceReport->id,
                'log_module' => 'iar',
                'data' => $inspectionAcceptanceReport
            ]);

            return response()->json([
                'data' => [
                    'data' => $inspectionAcceptanceReport,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Inspection and acceptance repor update failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $inspectionAcceptanceReport->id,
                'log_module' => 'iar',
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
    public function pending(InspectionAcceptanceReport $inspectionAcceptanceReport): JsonResponse
    {
        try {
            $message = 'Inspection & acceptance report successfully marked as pending for inspection.';

            $currentStatus = InspectionAcceptanceReportStatus::from($inspectionAcceptanceReport->status);

            if ($currentStatus !== InspectionAcceptanceReportStatus::DRAFT) {
                $message =
                    'Failed to set the inspection & accpetance report to pending for inspection.
                    It may already be set to pending or processing status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $inspectionAcceptanceReport->id,
                    'log_module' => 'iar',
                    'data' => $inspectionAcceptanceReport
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            $purchaseOrder = PurchaseOrder::find($inspectionAcceptanceReport->purchase_order_id);

            if ($purchaseOrder) {
                $purchaseOrder->update([
                    'status' => PurchaseOrderStatus::INSPECTION,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'inspection_at', $purchaseOrder->status_timestamps
                    )
                ]);

                $this->logRepository->create([
                    'message' => ($purchaseOrder->document_type === 'po' ? 'Purchase' : 'Job') .
                        ' order successfully marked as to inspection.',
                    'log_id' => $purchaseOrder->id,
                    'log_module' => 'po',
                    'data' => $purchaseOrder
                ]);
            }

            $inspectionAcceptanceReport->update([
                'status' => InspectionAcceptanceReportStatus::PENDING,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'pending_at', $inspectionAcceptanceReport->status_timestamps
                )
            ]);

            $inspectionAcceptanceReport->load('items');

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $inspectionAcceptanceReport->id,
                'log_module' => 'iar',
                'data' => $inspectionAcceptanceReport
            ]);

            return response()->json([
                'data' => [
                    'data' => $inspectionAcceptanceReport,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Inspection & acceptance report failed to marked as pending for inspection.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $inspectionAcceptanceReport->id,
                'log_module' => 'iar',
                'data' => $inspectionAcceptanceReport
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function inspect(Request $request, InspectionAcceptanceReport $inspectionAcceptanceReport)
    {
        $inspectionAcceptanceReport->load('purchase_order');

        if ($inspectionAcceptanceReport->purchase_order->document_type === 'po') {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
            ]);
        } else {
            $validated = [
                'items' => []
            ];
        }

        try {
            $message = 'Inspection & acceptance report successfully marked as inspected.';

            $currentStatus = InspectionAcceptanceReportStatus::from($inspectionAcceptanceReport->status);

            if ($currentStatus !== InspectionAcceptanceReportStatus::PENDING) {
                $message =
                    'Failed to set the inspection & accpetance report to inspected.
                    It may still be on draft or already on processing status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $inspectionAcceptanceReport->id,
                    'log_module' => 'iar',
                    'data' => $inspectionAcceptanceReport
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            if (empty($inspectionAcceptanceReport->sig_inspection_id)) {
                $message = 'Failed to set the inspection & acceptance report to inspected. ' .
                    'Please select a signatory for inspection.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $inspectionAcceptanceReport->id,
                    'log_module' => 'iar',
                    'data' => [
                        'iar' => $inspectionAcceptanceReport,
                        'supplies' => $request->all()
                    ]
                ], isError: true);

                return response()->json([
                    'message' => $message
                ], 422);
            }

            foreach ($validated['items'] ?? [] as $key => $item) {
                $supply = $this->inventorySupplyRepository->storeUpdate(array_merge(
                    $item,
                    ['item_sequence' => $key]
                ));

                $this->logRepository->create([
                    'message' => 'Supply created successfully.',
                    'log_id' => $supply->id,
                    'log_module' => 'inv-supply',
                    'data' => $supply
                ]);
            }

            $inspectionAcceptanceReport->update([
                'status' => InspectionAcceptanceReportStatus::INSPECTED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'inspected_at', $inspectionAcceptanceReport->status_timestamps
                )
            ]);

            $inspectionAcceptanceReport->load('items');

            $this->logRepository->create([
                'message' => $message,
                'log_id' => $inspectionAcceptanceReport->id,
                'log_module' => 'iar',
                'data' => $inspectionAcceptanceReport
            ]);

            return response()->json([
                'data' => [
                    'data' => $inspectionAcceptanceReport,
                    'message' => $message
                ]
            ]);
        } catch (\Throwable $th) {
            $message = 'Inspection & acceptance report failed to marked as inspected.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $inspectionAcceptanceReport->id,
                'log_module' => 'iar',
                'data' => $inspectionAcceptanceReport
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again."
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    // public function accept(InspectionAcceptanceReport $inspectionAcceptanceReport)
    // {

    // }
}
