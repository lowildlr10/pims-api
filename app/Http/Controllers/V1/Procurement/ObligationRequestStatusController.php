<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Models\ObligationRequest;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ObligationRequestStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'obr_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $obligationRequests = ObligationRequest::query()
            ->select([
                'id',
                'purchase_order_id',
                'payee_id',
                'obr_no',
                'particulars',
                'status',
            ])
            ->with([
                'purchase_order:id,po_no',
                'payee:id,supplier_name'
            ]);

        if (! empty($search)) {
            $obligationRequests->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('obr_no', 'ILIKE', "%{$search}%")
                    ->orWhere('office', 'ILIKE', "%{$search}%")
                    ->orWhere('address', 'ILIKE', "%{$search}%")
                    ->orWhere('particulars', 'ILIKE', "%{$search}%")
                    ->orWhere('status', 'ILIKE', "%{$search}%")
                    ->orWhereRelation('purchase_request', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search]);
                    })
                    ->orWhereRelation('purchase_order', function ($query) use ($search) {
                        $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                            ->orWhere('po_no', 'ILIKE',"%$search%");
                    })
                    ->orWhereRelation('payee', function ($query) use ($search) {
                        $query->where('supplier_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('responsibility_center', function ($query) use ($search) {
                        $query->where('code', 'ILIKE', "%{$search}%")
                            ->orWhere('description', 'ILIKE',"%$search%");
                    })
                    ->orWhereRelation('signatory_budget.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('signatory_head.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    });
            });
        }

        if (in_array($sortDirection, ['asc', 'desc'])) {
            switch ($columnSort) {
                case 'obr_no':
                    $obligationRequests = $obligationRequests->orderByRaw("CAST(REPLACE(obr_no, '-', '') AS INTEGER) {$sortDirection}");
                    break;

                case 'po_no':
                    $obligationRequests = $obligationRequests->orderBy(
                        PurchaseOrder::select('po_no')->whereColumn('purchase_orders.id', 'obligation_requests.purchase_order_id'),
                        $sortDirection
                    );
                    break;

                case 'particulars_formatted':
                    $obligationRequests = $obligationRequests->orderBy('status', $sortDirection);
                    break;

                case 'payee_name':
                    $obligationRequests = $obligationRequests->orderBy(
                        Supplier::select('supplier_name')->whereColumn('suppliers.id', 'obligation_requests.payee_id'),
                        $sortDirection
                    );
                    break;

                case 'status_formatted':
                    $obligationRequests = $obligationRequests->orderBy('status', $sortDirection);
                    break;

                default:
                    $obligationRequests = $obligationRequests->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $obligationRequests->paginate($perPage);
        } else {
            $obligationRequests = $showAll
                ? $obligationRequests->get()
                : $obligationRequests = $obligationRequests->limit($perPage)->get();

            return response()->json([
                'data' => $obligationRequests,
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ObligationRequest $obligationRequest): JsonResponse
    {
        $obligationRequest->load([
            'payee:id,supplier_name',
            'purchase_order:id,po_no,total_amount',
            'signatory_budget:id,user_id',
            'signatory_budget.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_budget.detail' => function ($query) {
                $query->where('document', 'obr')
                    ->where('signatory_type', 'budget');
            },
            'signatory_head:id,user_id',
            'signatory_head.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_head.detail' => function ($query) {
                $query->where('document', 'obr')
                    ->where('signatory_type', 'head');
            },
            'fpps',
            'accounts'
        ]);

        return response()->json([
            'data' => [
                'data' => $obligationRequest,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
