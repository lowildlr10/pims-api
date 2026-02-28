<?php

namespace App\Http\Controllers\V1\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisbursementVoucherResource;
use App\Models\DisbursementVoucher;
use App\Services\DisbursementVoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Disbursement Vouchers
 * APIs for managing disbursement vouchers
 */
class DisbursementVoucherController extends Controller
{
    public function __construct(
        protected DisbursementVoucherService $service
    ) {}

    /**
     * List Disbursement Vouchers
     *
     * Retrieve a paginated list of disbursement vouchers.
     *
     * @queryParam search string Search by DV number, payee, etc.
     * @queryParam per_page int Number of items per page. Default: 50.
     * @queryParam show_all boolean Show all results without pagination. Default: false.
     * @queryParam column_sort string Sort field. Default: dv_no.
     * @queryParam sort_direction string Sort direction (asc/desc). Default: desc.
     * @queryParam paginated boolean Return paginated results. Default: true.
     * @queryParam status string Filter by status (comma-separated).
     *
     * @response 200 {
     *   "data": [...],
     *   "links": {...},
     *   "meta": {...}
     * }
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $filters = $request->only([
            'search',
            'per_page',
            'show_all',
            'column_sort',
            'sort_direction',
            'paginated',
            'status',
        ]);

        $paginated = filter_var($filters['paginated'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $result = $this->service->getAll($filters);

        if ($paginated) {
            return DisbursementVoucherResource::collection($result);
        }

        return response()->json([
            'data' => DisbursementVoucherResource::collection($result),
        ]);
    }

    /**
     * Get Disbursement Voucher
     *
     * Display the specified disbursement voucher.
     *
     * @urlParam id string required The disbursement voucher UUID.
     *
     * @response 200 {
     *   "data": {...}
     * }
     */
    public function show(DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        return response()->json([
            'data' => new DisbursementVoucherResource($disbursementVoucher->load([
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
                },
            ])),
        ]);
    }

    /**
     * Update Disbursement Voucher
     *
     * Update the specified disbursement voucher.
     *
     * @urlParam id string required The disbursement voucher UUID.
     *
     * @bodyParam mode_payment string nullable The mode of payment.
     * @bodyParam office string nullable The office.
     * @bodyParam address string nullable The address.
     * @bodyParam responsibility_center_id string required The responsibility center ID.
     * @bodyParam explanation string required The explanation.
     * @bodyParam total_amount float required The total amount.
     * @bodyParam accountant_certified_choices array nullable The accountant certified choices.
     * @bodyParam sig_accountant_id string required The accountant signatory ID.
     * @bodyParam accountant_signed_date date nullable The accountant signed date.
     * @bodyParam sig_treasurer_id string required The treasurer signatory ID.
     * @bodyParam treasurer_signed_date date nullable The treasurer signed date.
     * @bodyParam sig_head_id string required The head signatory ID.
     * @bodyParam head_signed_date date nullable The head signed date.
     * @bodyParam check_no string nullable The check number.
     * @bodyParam bank_name string nullable The bank name.
     * @bodyParam check_date date nullable The check date.
     * @bodyParam received_name string nullable The received name.
     * @bodyParam received_date date nullable The received date.
     * @bodyParam or_other_document string nullable The OR/other document.
     * @bodyParam jev_no string nullable The JEV number.
     * @bodyParam jev_date date nullable The JEV date.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Disbursement voucher updated successfully."
     * }
     * @response 422 {
     *   "message": "Disbursement voucher update failed."
     * }
     */
    public function update(Request $request, DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        $validated = $request->validate([
            'dv_no' => 'required|unique:disbursement_vouchers,dv_no,'.$disbursementVoucher->id,
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
            $dv = $this->service->update($disbursementVoucher, $validated);

            return response()->json([
                'data' => new DisbursementVoucherResource($dv),
                'message' => 'Disbursement voucher updated successfully.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Disbursement voucher update failed.', $th, $validated);

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark as Pending for Disbursement
     *
     * Mark the disbursement voucher as pending for disbursement.
     *
     * @urlParam id string required The disbursement voucher UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Disbursement voucher successfully marked as pending for disbursement."
     * }
     * @response 422 {
     *   "message": "Failed to set the disbursement voucher to pending for disbursement."
     * }
     */
    public function pending(DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        try {
            $dv = $this->service->pending($disbursementVoucher);

            return response()->json([
                'data' => new DisbursementVoucherResource($dv),
                'message' => 'Disbursement voucher successfully marked as pending for disbursement.',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Disbursement voucher failed to mark as pending for disbursement.', $th, $disbursementVoucher->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Disapprove Disbursement Voucher
     *
     * Mark the disbursement voucher as disapproved.
     *
     * @urlParam id string required The disbursement voucher UUID.
     *
     * @bodyParam disapproved_reason string nullable The reason for disapproval.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Disbursement voucher successfully marked as Disapproved."
     * }
     * @response 422 {
     *   "message": "Failed to set the disbursement voucher to Disapproved."
     * }
     */
    public function disapprove(Request $request, DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        $validated = $request->validate([
            'disapproved_reason' => 'nullable|string',
        ]);

        try {
            $dv = $this->service->disapprove($disbursementVoucher, $validated['disapproved_reason'] ?? null);

            return response()->json([
                'data' => new DisbursementVoucherResource($dv),
                'message' => 'Disbursement voucher successfully marked as "Disapproved".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Disbursement voucher disapproval failed.', $th, $disbursementVoucher->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark as For Payment
     *
     * Mark the disbursement voucher as for payment.
     *
     * @urlParam id string required The disbursement voucher UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Disbursement voucher successfully marked as For Payment."
     * }
     * @response 422 {
     *   "message": "Failed to set the Disbursement Voucher to For Payment."
     * }
     */
    public function disburse(DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        try {
            $dv = $this->service->disburse($disbursementVoucher);

            return response()->json([
                'data' => new DisbursementVoucherResource($dv),
                'message' => 'Disbursement voucher successfully marked as "For Payment".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Disbursement voucher failed to mark as For Payment.', $th, $disbursementVoucher->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark as Paid
     *
     * Mark the disbursement voucher as paid.
     *
     * @urlParam id string required The disbursement voucher UUID.
     *
     * @response 200 {
     *   "data": {...},
     *   "message": "Disbursement voucher successfully marked as Paid."
     * }
     * @response 422 {
     *   "message": "Failed to set the Disbursement Voucher to Paid."
     * }
     */
    public function paid(DisbursementVoucher $disbursementVoucher): JsonResponse
    {
        try {
            $dv = $this->service->paid($disbursementVoucher);

            return response()->json([
                'data' => new DisbursementVoucherResource($dv),
                'message' => 'Disbursement voucher successfully marked as "Paid".',
            ]);
        } catch (\Throwable $th) {
            $this->service->logError('Disbursement voucher failed to mark as Paid.', $th, $disbursementVoucher->toArray());

            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }
}
