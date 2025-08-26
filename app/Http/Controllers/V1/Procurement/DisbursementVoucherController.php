<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Enums\DisbursementVoucherStatus;
use App\Enums\PurchaseRequestStatus;
use App\Helpers\StatusTimestampsHelper;
use App\Http\Controllers\Controller;
use App\Models\DisbursementVoucher;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Repositories\DisbursementVoucherRepository;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class DisbursementVoucherController extends Controller
{
    private LogRepository $logRepository;

    private DisbursementVoucherRepository $disbursementVoucherRepository;
    
    public function __construct(
        LogRepository $logRepository,
        DisbursementVoucherRepository $disbursementVoucherRepository
    ) {
        $this->logRepository = $logRepository;
        $this->disbursementVoucherRepository = $disbursementVoucherRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|LengthAwarePaginator
    {
        $user = Auth::user();
        
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', 50);
        $showAll = filter_var($request->get('show_all', false), FILTER_VALIDATE_BOOLEAN);
        $columnSort = $request->get('column_sort', 'dv_no');
        $sortDirection = $request->get('sort_direction', 'desc');
        $paginated = filter_var($request->get('paginated', true), FILTER_VALIDATE_BOOLEAN);

        $disbursementVouchers = DisbursementVoucher::query()
            ->select([
                'id',
                'purchase_order_id',
                'payee_id',
                'dv_no',
                'explanation',
                'status',
            ])
            ->with([
                'purchase_order:id,po_no',
                'payee:id,supplier_name'
            ]);

        if ($user->tokenCan('super:*')
            || $user->tokenCan('head:*')
            || $user->tokenCan('supply:*')
            || $user->tokenCan('budget:*')
            || $user->tokenCan('accountant:*')
        ) {
        } else {
            $disbursementVouchers = $disbursementVouchers
                ->whereRelation('purchase_request', function ($query) use ($user) {
                $query->where('requested_by_id', $user->id);
            });
        }
        
        if (! empty($search)) {
            $disbursementVouchers->where(function ($query) use ($search) {
                $query->whereRaw('CAST(id AS TEXT) = ?', [$search])
                    ->orWhere('dv_no', 'ILIKE', "%{$search}%")
                    ->orWhere('office', 'ILIKE', "%{$search}%")
                    ->orWhere('address', 'ILIKE', "%{$search}%")
                    ->orWhere('explanation', 'ILIKE', "%{$search}%")
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
                    ->orWhereRelation('signatory_accountant.user', function ($query) use ($search) {
                        $query->where('firstname', 'ILIKE', "%{$search}%")
                            ->orWhere('lastname', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereRelation('signatory_treasurer.user', function ($query) use ($search) {
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
                case 'dv_no':
                    $disbursementVouchers = $disbursementVouchers->orderByRaw("CAST(REPLACE(dv_no, '-', '') AS INTEGER) {$sortDirection}");
                    break;

                case 'po_no':
                    $disbursementVouchers = $disbursementVouchers->orderBy(
                        PurchaseOrder::select('po_no')->whereColumn('purchase_orders.id', 'disbursement_vouchers.purchase_order_id'),
                        $sortDirection
                    );
                    break;

                case 'explanation_formatted':
                    $disbursementVouchers = $disbursementVouchers->orderBy('status', $sortDirection);
                    break;

                case 'payee_name':
                    $disbursementVouchers = $disbursementVouchers->orderBy(
                        Supplier::select('supplier_name')->whereColumn('suppliers.id', 'disbursement_vouchers.payee_id'),
                        $sortDirection
                    );
                    break;

                case 'status_formatted':
                    $disbursementVouchers = $disbursementVouchers->orderBy('status', $sortDirection);
                    break;

                default:
                    $disbursementVouchers = $disbursementVouchers->orderBy($columnSort, $sortDirection);
                    break;
            }
        }

        if ($paginated) {
            return $disbursementVouchers->paginate($perPage);
        } else {
            $disbursementVouchers = $showAll
                ? $disbursementVouchers->get()
                : $disbursementVouchers = $disbursementVouchers->limit($perPage)->get();

            return response()->json([
                'data' => $disbursementVouchers,
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DisbursementVoucher $disbursementVoucher): JsonResponse
    {
         $disbursementVoucher->load([
            'payee:id,supplier_name,tin_no',
            'responsibility_center:id,code',
            'purchase_order:id,po_no,total_amount',
            'obligation_request:id,obr_no',
            'signatory_accountant:id,user_id',
            'signatory_accountant.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_accountant.detail' => function ($query) {
                $query->where('document', 'dv')
                    ->where('signatory_type', 'accountant');
            },
            'signatory_treasurer:id,user_id',
            'signatory_treasurer.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_treasurer.detail' => function ($query) {
                $query->where('document', 'dv')
                    ->where('signatory_type', 'treasurer');
            },
            'signatory_head:id,user_id',
            'signatory_head.user:id,firstname,middlename,lastname,allow_signature,signature',
            'signatory_head.detail' => function ($query) {
                $query->where('document', 'dv')
                    ->where('signatory_type', 'head');
            }
        ]);

        return response()->json([
            'data' => [
                'data' => $disbursementVoucher,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        $validated = $request->validate([
            'mode_payment' => 'nullable',
            'office' => 'nullable|string',
            'address' => 'nullable|string',
            'responsibility_center_id' => 'required',
            'explanation' => 'required',
            'total_amount' => 'required',
            'accountant_certified_choices' => 'nullable',
            'sig_accountant_id' => 'required',
            'accountant_signed_date' => 'nullable',
            'sig_treasurer_id' => 'required',
            'treasurer_signed_date' => 'nullable',
            'sig_head_id' => 'required',
            'head_signed_date' => 'nullable',
            'fpps' => 'nullable|array',
            'check_no' => 'nullable',
            'bank_name' => 'nullable',
            'check_date' => 'nullable',
            'received_name' => 'nullable',
            'received_date' => 'nullable',
            'or_other_document' => 'nullable',
            'jev_no' => 'nullable',
            'jev_date' => 'nullable',
        ]);


        try {
            $currentStatus = DisbursementVoucherStatus::from($disbursementVoucher->status);
            $status = $currentStatus;

            if ($currentStatus === DisbursementVoucherStatus::DRAFT
                || $currentStatus === DisbursementVoucherStatus::DISAPPROVED) {
                $status = DisbursementVoucherStatus::DRAFT;
            }

            $this->disbursementVoucherRepository->storeUpdate(
                array_merge($validated, [
                    'status' => $status,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'draft_at', null
                    ),
                ]), 
                $disbursementVoucher
            );

            $message = 'Disbursement voucher updated successfully';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ]);

            return response()->json([
                'data' => [
                    'data' => $disbursementVoucher,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Disbursement voucher update failed.';
            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $validated,
            ], isError: true);

            return response()->json([
                'message' => "$message Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function pending(DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        try {
            $currentStatus = DisbursementVoucherStatus::from($disbursementVoucher->status);

            if ($currentStatus !== DisbursementVoucherStatus::DRAFT) {
                $message =
                    'Failed to set the disbursement voucher to pending for disbursement. '.
                    'It may already be set to pending or processing status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $disbursementVoucher->id,
                    'log_module' => 'dv',
                    'data' => $disbursementVoucher,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $disbursementVoucher->update([
                'disapproved_reason' => null,
                'status' => DisbursementVoucherStatus::PENDING,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'pending_at', $disbursementVoucher->status_timestamps
                ),
            ]);

            $message = 'Disbursement voucher successfully marked as pending for obligation.';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ]);

            return response()->json([
                'data' => [
                    'data' => $disbursementVoucher,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Disbursement voucher failed to marked as pending for disbursement.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function disapprove(Request $request, DisbursementVoucher $disbursementVoucher): JsonResponse
    {
         try {
            $validated = $request->validate([
                'disapproved_reason' => 'nullable|string',
            ]);

            $currentStatus = DisbursementVoucherStatus::from($disbursementVoucher->status);

            if ($currentStatus !== DisbursementVoucherStatus::PENDING) {
                $message =
                    'Failed to set the disbursement voucher to "Disapproved". '.
                    'It may already be obligated or still in draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $disbursementVoucher->id,
                    'log_module' => 'dv',
                    'data' => $disbursementVoucher,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $disbursementVoucher->update([
                'disapproved_reason' => $validated['disapproved_reason'] ?? null,
                'status' => DisbursementVoucherStatus::DISAPPROVED,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'disapproved_at', $disbursementVoucher->status_timestamps
                ),
            ]);

            $message = 'Disbursement voucher successfully marked as "Disapproved".';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ]);

            return response()->json([
                'data' => [
                    'data' => $disbursementVoucher,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Disbursement voucher disapproval failed.';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function disburse(DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        try {
            $currentStatus = DisbursementVoucherStatus::from($disbursementVoucher->status);

            if ($currentStatus !== DisbursementVoucherStatus::PENDING) {
                $message =
                    'Failed to set the Disbursement Voucher to "For Payment". '.
                    'It may already be set to payment or still in draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $disbursementVoucher->id,
                    'log_module' => 'dv',
                    'data' => $disbursementVoucher,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $disbursementVoucher->update([
                'status' => DisbursementVoucherStatus::FOR_PAYMENT,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'for_payment_at', $disbursementVoucher->status_timestamps
                ),
            ]);

            $message = 'Disbursement voucher successfully marked as "For Payment".';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ]);

            return response()->json([
                'data' => [
                    'data' => $disbursementVoucher,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Disbursement voucher failed to marked as "For Payment".';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }

    /**
     * Update the status of the specified resource in storage.
     */
    public function paid(DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        try {
            $currentStatus = DisbursementVoucherStatus::from($disbursementVoucher->status);

            if ($currentStatus !== DisbursementVoucherStatus::FOR_PAYMENT) {
                $message =
                    'Failed to set the Disbursement Voucher to "Paid". '.
                    'It may already be paid or still in draft status.';
                $this->logRepository->create([
                    'message' => $message,
                    'log_id' => $disbursementVoucher->id,
                    'log_module' => 'dv',
                    'data' => $disbursementVoucher,
                ], isError: true);

                return response()->json([
                    'message' => $message,
                ], 422);
            }

            $disbursementVoucher->update([
                'status' => DisbursementVoucherStatus::PAID,
                'status_timestamps' => StatusTimestampsHelper::generate(
                    'paid_at', $disbursementVoucher->status_timestamps
                ),
            ]);

            $message = 'Disbursement voucher successfully marked as "Paid".';
            $this->logRepository->create([
                'message' => $message,
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ]);

            $purchaseRequest = PurchaseRequest::find($disbursementVoucher->purchase_request_id);

            if (!empty($purchaseRequest)) {
                $purchaseRequest->update([
                    'status' => PurchaseRequestStatus::COMPLETED,
                    'status_timestamps' => StatusTimestampsHelper::generate(
                        'completed_at', $purchaseRequest->status_timestamps
                    ),
                ]);
                $this->logRepository->create([
                    'message' => 'Purchase request successfully marked as "Completed"',
                    'log_id' => $purchaseRequest->id,
                    'log_module' => 'pr',
                    'data' => $purchaseRequest,
                ]);
            }

            return response()->json([
                'data' => [
                    'data' => $disbursementVoucher,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $th) {
            $message = 'Disbursement voucher failed to marked as "Paid".';

            $this->logRepository->create([
                'message' => $message,
                'details' => $th->getMessage(),
                'log_id' => $disbursementVoucher->id,
                'log_module' => 'dv',
                'data' => $disbursementVoucher,
            ], isError: true);

            return response()->json([
                'message' => "{$message} Please try again.",
            ], 422);
        }
    }
}
