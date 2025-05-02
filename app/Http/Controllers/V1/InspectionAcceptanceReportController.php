<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\InspectionAcceptanceReport;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequestItem;
use App\Models\Signatory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class InspectionAcceptanceReportController extends Controller
{
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
                    ->orWhereRelation('signatory_acceptance.user', function ($query) use ($search) {
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
            'items.po_item:id,description,brand_model,unit_cost,total_cost',
            'signatory_inspection:id,user_id',
            'signatory_inspection.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_inspection.detail' => function ($query) {
                $query->where('document', 'iar')
                    ->where('signatory_type', '	inspection');
            },
            'signatory_acceptance.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_acceptance.detail' => function ($query) {
                $query->where('document', 'iar')
                    ->where('signatory_type', '	acceptance');
            },
            'purchase_request:id,section_id',
            'purchase_request.section:id,section_name',
            'purchase_order:id,po_no,po_date'
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
    public function update(Request $request, InspectionAcceptanceReport $inspectionAcceptanceReport)
    {
        //
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function forInspection(InspectionAcceptanceReport $inspectionAcceptanceReport)
    {

    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function inspect(InspectionAcceptanceReport $inspectionAcceptanceReport)
    {

    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function accept(InspectionAcceptanceReport $inspectionAcceptanceReport)
    {

    }
}
